<?php

namespace App\Services;

use Exception;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelIOFactory;

class InvoiceParserService
{
    /**
     * Parse uploaded file depending on format
     */
    public static function parseFile(string $filePath, string $fileType): array
    {
        return match ($fileType) {
            'json' => self::parseJSON($filePath),
            'csv' => self::parseCSV($filePath),
            'xlsx', 'xls' => self::parseExcel($filePath),
            'pdf' => self::parsePDF($filePath),
            default => throw new Exception("Unsupported file format: {$fileType}"),
        };
    }

    private static function parseJSON(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON invoice structure: " . json_last_error_msg());
        }

        $itemsList = is_array($data) && isset($data[0]) ? $data : [$data];

        return array_map(function ($item, $idx) {
            return [
                'invoiceNumber' => (string) ($item['invoiceNumber'] ?? $item['InvoiceNumber'] ?? ('INV-JSON-' . (1001 + $idx))),
                'invoiceDate' => (string) ($item['invoiceDate'] ?? $item['InvoiceDate'] ?? date('Y-m-d')),
                'sellerNTN' => $item['sellerNTN'] ?? $item['SellerNTN'] ?? null,
                'sellerName' => $item['sellerName'] ?? $item['SellerName'] ?? null,
                'sellerPOSID' => $item['sellerPOSID'] ?? $item['POSID'] ?? null,
                'buyerNTN' => $item['buyerNTN'] ?? $item['BuyerNTN'] ?? null,
                'buyerName' => (string) ($item['buyerName'] ?? $item['BuyerName'] ?? 'Walk-in Customer'),
                'buyerCNIC' => $item['buyerCNIC'] ?? $item['BuyerCNIC'] ?? null,
                'buyerPhone' => $item['buyerPhone'] ?? $item['BuyerPhone'] ?? null,
                'paymentMode' => (string) ($item['paymentMode'] ?? $item['PaymentMode'] ?? '1'),
                'discount' => (float) ($item['discount'] ?? 0),
                'furtherTax' => (float) ($item['furtherTax'] ?? 0),
                'totalBill' => isset($item['totalBill']) ? (float) $item['totalBill'] : null,
                'items' => isset($item['items']) && is_array($item['items'])
                    ? array_map(function ($it, $iIdx) {
                        return [
                            'itemCode' => (string) ($it['itemCode'] ?? $it['ItemCode'] ?? ('ITEM-' . ($iIdx + 1))),
                            'itemName' => (string) ($it['itemName'] ?? $it['ItemName'] ?? 'General Goods'),
                            'PCTCode' => (string) ($it['PCTCode'] ?? $it['pctCode'] ?? '9901.0000'),
                            'quantity' => (float) ($it['quantity'] ?? $it['Quantity'] ?? 1),
                            'unitPrice' => (float) ($it['unitPrice'] ?? $it['UnitPrice'] ?? 0),
                            'discount' => (float) ($it['discount'] ?? $it['Discount'] ?? 0),
                            'taxRate' => (float) ($it['taxRate'] ?? $it['TaxRate'] ?? 18),
                        ];
                    }, $item['items'], array_keys($item['items']))
                    : [
                        [
                            'itemCode' => 'ITEM-1',
                            'itemName' => (string) ($item['description'] ?? 'General Merchandise'),
                            'PCTCode' => '9901.0000',
                            'quantity' => (float) ($item['quantity'] ?? 1),
                            'unitPrice' => (float) ($item['unitPrice'] ?? $item['amount'] ?? 0),
                            'discount' => 0.0,
                            'taxRate' => 18.0,
                        ],
                    ],
            ];
        }, $itemsList, array_keys($itemsList));
    }

    private static function parseCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open CSV file.");
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new Exception("CSV file is empty.");
        }

        $cleanHeaders = array_map(function ($h) {
            return strtolower(preg_replace('/[^a-z0-9]/', '', trim($h)));
        }, $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($cleanHeaders)) {
                $rows[] = array_combine($cleanHeaders, $row);
            }
        }
        fclose($handle);

        return self::groupFlatRowsToInvoices($rows);
    }

    private static function parseExcel(string $filePath): array
    {
        $spreadsheet = ExcelIOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rawRows = $worksheet->toArray();

        if (empty($rawRows)) {
            throw new Exception("Excel worksheet contains no data.");
        }

        $headers = array_shift($rawRows);
        $cleanHeaders = array_map(function ($h) {
            return strtolower(preg_replace('/[^a-z0-9]/', '', trim((string) $h)));
        }, $headers);

        $rows = [];
        foreach ($rawRows as $row) {
            if (count($row) === count($cleanHeaders)) {
                $rows[] = array_combine($cleanHeaders, $row);
            }
        }

        return self::groupFlatRowsToInvoices($rows);
    }

    private static function groupFlatRowsToInvoices(array $rows): array
    {
        $invoiceMap = [];

        foreach ($rows as $idx => $row) {
            $invNum = trim((string) ($row['invoicenumber'] ?? $row['invoiceno'] ?? $row['invno'] ?? $row['id'] ?? ('INV-' . (1000 + $idx))));
            $invDate = trim((string) ($row['invoicedate'] ?? $row['date'] ?? $row['invdate'] ?? date('Y-m-d')));
            $buyerName = trim((string) ($row['buyername'] ?? $row['customername'] ?? $row['client'] ?? $row['buyer'] ?? 'Walk-in Customer'));
            $buyerNTN = isset($row['buyerntn']) && !empty($row['buyerntn']) ? (string) $row['buyerntn'] : null;
            $buyerCNIC = isset($row['buyercnic']) && !empty($row['buyercnic']) ? (string) $row['buyercnic'] : null;
            $buyerPhone = isset($row['buyerphone']) && !empty($row['buyerphone']) ? (string) $row['buyerphone'] : null;
            $paymentMode = (string) ($row['paymentmode'] ?? $row['paymentmethod'] ?? '1');

            $itemCode = (string) ($row['itemcode'] ?? $row['code'] ?? ('SKU-' . ($idx + 1)));
            $itemName = (string) ($row['itemname'] ?? $row['description'] ?? $row['item'] ?? 'Item');
            $pctCode = (string) ($row['pctcode'] ?? $row['hscode'] ?? '9901.0000');
            $quantity = (float) ($row['quantity'] ?? $row['qty'] ?? 1);
            $unitPrice = (float) ($row['unitprice'] ?? $row['rate'] ?? $row['price'] ?? 0);
            $itemDiscount = (float) ($row['discount'] ?? $row['disc'] ?? 0);
            $taxRate = (float) ($row['taxrate'] ?? $row['gst'] ?? $row['tax'] ?? 18);

            $item = [
                'itemCode' => $itemCode,
                'itemName' => $itemName,
                'PCTCode' => $pctCode,
                'quantity' => $quantity,
                'unitPrice' => $unitPrice,
                'discount' => $itemDiscount,
                'taxRate' => $taxRate,
            ];

            if (isset($invoiceMap[$invNum])) {
                $invoiceMap[$invNum]['items'][] = $item;
            } else {
                $invoiceMap[$invNum] = [
                    'invoiceNumber' => $invNum,
                    'invoiceDate' => $invDate,
                    'buyerName' => $buyerName,
                    'buyerNTN' => $buyerNTN,
                    'buyerCNIC' => $buyerCNIC,
                    'buyerPhone' => $buyerPhone,
                    'paymentMode' => $paymentMode,
                    'items' => [$item],
                ];
            }
        }

        return array_values($invoiceMap);
    }

    private static function parsePDF(string $filePath): array
    {
        try {
            $pdfParser = new PdfParser();
            $pdf = $pdfParser->parseFile($filePath);
            $text = $pdf->getText();

            if (empty(trim($text))) {
                throw new Exception("PDF file contains no extractable text or is a scanned image.");
            }

            $blocks = preg_split('/(?=Invoice\s*(?:#|Number|No\.?):)/i', $text);
            $blocks = array_filter(array_map('trim', $blocks));

            if (count($blocks) <= 1) {
                return [self::parseSinglePDFBlock($text, 1)];
            }

            $invoices = [];
            foreach ($blocks as $i => $block) {
                $invoices[] = self::parseSinglePDFBlock($block, $i + 1);
            }
            return $invoices;
        } catch (\Throwable $e) {
            throw new Exception("PDF Extraction Error: " . $e->getMessage());
        }
    }

    private static function parseSinglePDFBlock(string $blockText, int $index): array
    {
        preg_match('/(?:Invoice\s*(?:#|Number|No\.?):?\s*)([A-Z0-9_-]+)/i', $blockText, $invNumMatch);
        $invoiceNumber = $invNumMatch[1] ?? ('INV-PDF-' . (1000 + $index));

        preg_match('/(?:Date:?\s*)([0-9]{4}-[0-9]{2}-[0-9]{2}|[0-9]{2}\/[0-9]{2}\/[0-9]{4}|[0-9]{2}-[A-Za-z]{3}-[0-9]{4})/i', $blockText, $dateMatch);
        $invoiceDate = date('Y-m-d');
        if (!empty($dateMatch[1])) {
            $parsedTs = strtotime($dateMatch[1]);
            if ($parsedTs) {
                $invoiceDate = date('Y-m-d', $parsedTs);
            }
        }

        preg_match('/(?:Buyer|Customer|Bill To):?\s*([^\n\r]+)/i', $blockText, $buyerMatch);
        $buyerName = isset($buyerMatch[1]) ? trim($buyerMatch[1]) : 'Walk-in Customer';

        preg_match('/(?:NTN|Buyer NTN):?\s*([0-9]{7}-[0-9]{1})/i', $blockText, $ntnMatch);
        $buyerNTN = $ntnMatch[1] ?? null;

        preg_match('/(?:CNIC|Buyer CNIC):?\s*([0-9]{5}-[0-9]{7}-[0-9]{1})/i', $blockText, $cnicMatch);
        $buyerCNIC = $cnicMatch[1] ?? null;

        $items = [];
        preg_match_all('/(?:([A-Z0-9_-]+)\s+)?([A-Za-z0-9\s-]+?)\s+([0-9]{4}\.[0-9]{4})?\s+([0-9]+)\s+(?:Rs\.?|PKR)?\s*([0-9]+(?:\.[0-9]+)?)/i', $blockText, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $itemCode = !empty($m[1]) ? $m[1] : ('ITEM-' . (count($items) + 1));
            $itemName = trim($m[2]);
            $pctCode = !empty($m[3]) ? $m[3] : '9901.0000';
            $quantity = (float) ($m[4] ?? 1);
            $unitPrice = (float) ($m[5] ?? 100);

            if (strlen($itemName) > 2 && !preg_match('/total|subtotal|tax|discount|invoice|date/i', $itemName)) {
                $items[] = [
                    'itemCode' => $itemCode,
                    'itemName' => $itemName,
                    'PCTCode' => $pctCode,
                    'quantity' => $quantity,
                    'unitPrice' => $unitPrice,
                    'discount' => 0.0,
                    'taxRate' => 18.0,
                ];
            }
        }

        if (empty($items)) {
            $items[] = [
                'itemCode' => 'ITEM-PDF-1',
                'itemName' => 'Standard Products',
                'PCTCode' => '9901.0000',
                'quantity' => 1.0,
                'unitPrice' => 500.0,
                'discount' => 0.0,
                'taxRate' => 18.0,
            ];
        }

        return [
            'invoiceNumber' => $invoiceNumber,
            'invoiceDate' => $invoiceDate,
            'buyerName' => $buyerName,
            'buyerNTN' => $buyerNTN,
            'buyerCNIC' => $buyerCNIC,
            'paymentMode' => '1',
            'items' => $items,
        ];
    }
}
