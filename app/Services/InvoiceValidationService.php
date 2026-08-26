<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceValidationService
{
    /**
     * Validates raw extracted invoice array, calculates financial fields,
     * checks duplicate database records, and assigns validation status.
     */
    public static function validateBatch(array $rawInvoices, array $defaultSeller): array
    {
        // 1. Gather all invoice numbers to check intra-batch duplicates
        $numberCounts = [];
        foreach ($rawInvoices as $inv) {
            $num = trim($inv['invoiceNumber'] ?? '');
            if ($num !== '') {
                $numberCounts[$num] = ($numberCounts[$num] ?? 0) + 1;
            }
        }

        // 2. Query DB to detect duplicates
        $numbers = array_keys($numberCounts);
        $dbDuplicates = Invoice::whereIn('invoice_number', $numbers)->pluck('invoice_number')->toArray();
        $dbDuplicateSet = array_flip($dbDuplicates);

        $processedList = [];

        foreach ($rawInvoices as $raw) {
            $errors = [];

            $sellerNTN = trim($raw['sellerNTN'] ?? $defaultSeller['ntn'] ?? '7890123-4');
            $sellerName = trim($raw['sellerName'] ?? $defaultSeller['name'] ?? 'AL-MADINA TRADERS LTD');
            $sellerPOSID = trim($raw['sellerPOSID'] ?? $defaultSeller['pos_id'] ?? 'POS-100234');

            // Required field checks
            $invNum = trim($raw['invoiceNumber'] ?? '');
            if (empty($invNum)) {
                $errors[] = [
                    'field' => 'invoiceNumber',
                    'message' => 'Invoice number is missing or empty.',
                    'code' => 'MISSING_FIELD',
                ];
            }

            $invDateStr = trim($raw['invoiceDate'] ?? '');
            $invTimestamp = strtotime($invDateStr);
            if (!$invTimestamp) {
                $errors[] = [
                    'field' => 'invoiceDate',
                    'message' => 'Invoice date is invalid or improperly formatted.',
                    'code' => 'INVALID_FORMAT',
                ];
            }

            if (empty($sellerNTN)) {
                $errors[] = [
                    'field' => 'sellerNTN',
                    'message' => 'Seller NTN is required by FBR.',
                    'code' => 'FBR_REQUIREMENT',
                ];
            }

            if (empty($sellerPOSID)) {
                $errors[] = [
                    'field' => 'sellerPOSID',
                    'message' => 'POS Registration ID is required.',
                    'code' => 'FBR_REQUIREMENT',
                ];
            }

            $buyerName = trim($raw['buyerName'] ?? '');
            if (empty($buyerName)) {
                $errors[] = [
                    'field' => 'buyerName',
                    'message' => 'Buyer / Customer name is missing.',
                    'code' => 'MISSING_FIELD',
                ];
            }

            // NTN & CNIC Formats
            $buyerNTN = isset($raw['buyerNTN']) ? trim($raw['buyerNTN']) : null;
            if ($buyerNTN && !preg_match('/^[0-9]{7}-[0-9]{1}$/', $buyerNTN)) {
                $errors[] = [
                    'field' => 'buyerNTN',
                    'message' => 'Buyer NTN format should be 7 digits followed by check digit (e.g. 1234567-8).',
                    'code' => 'INVALID_FORMAT',
                ];
            }

            $buyerCNIC = isset($raw['buyerCNIC']) ? trim($raw['buyerCNIC']) : null;
            if ($buyerCNIC && !preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/', $buyerCNIC)) {
                $errors[] = [
                    'field' => 'buyerCNIC',
                    'message' => 'Buyer CNIC format should be 13 digits with dashes (e.g. 12345-1234567-1).',
                    'code' => 'INVALID_FORMAT',
                ];
            }

            // Duplicate Checks
            if (!empty($invNum)) {
                if (($numberCounts[$invNum] ?? 0) > 1) {
                    $errors[] = [
                        'field' => 'invoiceNumber',
                        'message' => "Duplicate invoice number \"{$invNum}\" detected within the same uploaded batch.",
                        'code' => 'DUPLICATE_NUMBER',
                    ];
                }

                if (isset($dbDuplicateSet[$invNum])) {
                    $errors[] = [
                        'field' => 'invoiceNumber',
                        'message' => "Invoice number \"{$invNum}\" already exists in the system database.",
                        'code' => 'DUPLICATE_NUMBER',
                    ];
                }
            }

            // Items Validation & Calculations
            $rawItems = $raw['items'] ?? [];
            if (empty($rawItems)) {
                $errors[] = [
                    'field' => 'items',
                    'message' => 'Invoice must contain at least one line item.',
                    'code' => 'MISSING_FIELD',
                ];
            }

            $processedItems = [];
            $totalSaleValue = 0.0;
            $totalQuantity = 0.0;
            $totalTaxAmount = 0.0;

            foreach ($rawItems as $idx => $it) {
                $itemCode = trim($it['itemCode'] ?? '');
                if (empty($itemCode)) {
                    $errors[] = [
                        'field' => "items[{$idx}].itemCode",
                        'message' => "Item #" . ($idx + 1) . " code is missing.",
                        'code' => 'MISSING_FIELD',
                    ];
                }

                $quantity = (float) ($it['quantity'] ?? 0);
                if ($quantity <= 0) {
                    $errors[] = [
                        'field' => "items[{$idx}].quantity",
                        'message' => "Item #" . ($idx + 1) . " quantity must be greater than zero.",
                        'code' => 'INVALID_FORMAT',
                    ];
                }

                $unitPrice = (float) ($it['unitPrice'] ?? 0);
                if ($unitPrice < 0) {
                    $errors[] = [
                        'field' => "items[{$idx}].unitPrice",
                        'message' => "Item #" . ($idx + 1) . " unit price cannot be negative.",
                        'code' => 'INVALID_FORMAT',
                    ];
                }

                $pctCode = trim($it['PCTCode'] ?? '9901.0000');
                $discount = max(0.0, (float) ($it['discount'] ?? 0));
                $taxRate = (float) ($it['taxRate'] ?? 18.0);

                $saleValue = round(($quantity * $unitPrice) - $discount, 2);
                $taxCharged = round($saleValue * ($taxRate / 100.0), 2);
                $totalAmount = round($saleValue + $taxCharged, 2);

                $totalQuantity += $quantity;
                $totalSaleValue += $saleValue;
                $totalTaxAmount += $taxCharged;

                $processedItems[] = [
                    'item_code' => !empty($itemCode) ? $itemCode : ('SKU-' . ($idx + 1)),
                    'item_name' => trim($it['itemName'] ?? ('Item ' . ($idx + 1))),
                    'pct_code' => $pctCode,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'sale_value' => $saleValue,
                    'tax_rate' => $taxRate,
                    'tax_charged' => $taxCharged,
                    'total_amount' => $totalAmount,
                ];
            }

            $totalSaleValue = round($totalSaleValue, 2);
            $totalTaxAmount = round($totalTaxAmount, 2);
            $furtherTax = round((float) ($raw['furtherTax'] ?? 0), 2);
            $invoiceDiscount = round((float) ($raw['discount'] ?? 0), 2);
            $calculatedTotalBill = round($totalSaleValue + $totalTaxAmount + $furtherTax - $invoiceDiscount, 2);

            // Assign status
            $validationStatus = 'VALID';
            $hasDuplicate = false;
            $hasMissing = false;

            foreach ($errors as $e) {
                if ($e['code'] === 'DUPLICATE_NUMBER') $hasDuplicate = true;
                if ($e['code'] === 'MISSING_FIELD') $hasMissing = true;
            }

            if ($hasDuplicate) {
                $validationStatus = 'DUPLICATE';
            } elseif ($hasMissing) {
                $validationStatus = 'MISSING_REQUIRED_FIELD';
            } elseif (!empty($errors)) {
                $validationStatus = 'INVALID';
            }

            $processedList[] = [
                'invoice_number' => !empty($invNum) ? $invNum : ('INV-' . time()),
                'invoice_date' => $invTimestamp ? date('Y-m-d H:i:s', $invTimestamp) : date('Y-m-d H:i:s'),
                'seller_ntn' => $sellerNTN,
                'seller_name' => $sellerName,
                'seller_pos_id' => $sellerPOSID,
                'buyer_ntn' => $buyerNTN,
                'buyer_name' => !empty($buyerName) ? $buyerName : 'Walk-in Customer',
                'buyer_cnic' => $buyerCNIC,
                'buyer_phone' => isset($raw['buyerPhone']) ? trim($raw['buyerPhone']) : null,
                'payment_mode' => (string) ($raw['paymentMode'] ?? '1'),
                'total_sale_value' => $totalSaleValue,
                'total_quantity' => $totalQuantity,
                'total_tax_amount' => $totalTaxAmount,
                'discount' => $invoiceDiscount,
                'further_tax' => $furtherTax,
                'total_bill' => $calculatedTotalBill,
                'items' => $processedItems,
                'validation_status' => $validationStatus,
                'validation_errors' => $errors,
            ];
        }

        return $processedList;
    }
}
