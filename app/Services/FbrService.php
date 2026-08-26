<?php

namespace App\Services;

use App\Models\FbrResponse;
use App\Models\FbrSubmission;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FbrService
{
    /**
     * Submits a single valid invoice to FBR POS Digital Invoicing API
     */
    public static function submitInvoice(Invoice $invoice): array
    {
        // 1. Prevent duplicate submission of already ACCEPTED invoices
        if ($invoice->fbr_status === 'ACCEPTED' && !empty($invoice->fbr_invoice_number)) {
            return [
                'status' => 'ACCEPTED',
                'fbrInvoiceNumber' => $invoice->fbr_invoice_number,
                'message' => 'Invoice has already been submitted and accepted by FBR.',
            ];
        }

        // Update status to PROCESSING
        $invoice->update([
            'fbr_status' => 'PROCESSING',
            'last_error' => null,
        ]);

        $environment = config('services.fbr.environment', env('FBR_ENVIRONMENT', 'mock'));
        $apiUrl = config('services.fbr.api_url', env('FBR_API_URL', 'https://ims.fbr.gov.pk/api/Live/PostData'));
        $bearerToken = config('services.fbr.bearer_token', env('FBR_BEARER_TOKEN', 'fbr_token_12345'));
        $posId = config('services.fbr.pos_id', env('FBR_POS_ID', 'POS-100234'));

        // Load itemized breakdown
        $invoice->loadMissing('items');

        // 2. Format official FBR JSON payload structure
        $fbrItems = [];
        foreach ($invoice->items as $item) {
            $fbrItems[] = [
                'ItemCode' => $item->item_code,
                'ItemName' => $item->item_name,
                'PCTCode' => $item->pct_code,
                'Quantity' => (float) $item->quantity,
                'TaxRate' => (float) $item->tax_rate,
                'SaleValue' => (float) $item->sale_value,
                'Discount' => (float) $item->discount,
                'TaxCharged' => (float) $item->tax_charged,
                'TotalAmount' => (float) $item->total_amount,
                'InvoiceType' => 1, // Standard Sales Invoice
            ];
        }

        $payload = [
            'InvoiceNumber' => $invoice->invoice_number,
            'POSID' => (int) preg_replace('/[^0-9]/', '', $posId),
            'USIN' => $invoice->invoice_number,
            'DateTime' => $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'BuyerNTN' => $invoice->buyer_ntn ?? '',
            'BuyerCNIC' => $invoice->buyer_cnic ?? '',
            'BuyerName' => $invoice->buyer_name,
            'BuyerPhoneNumber' => $invoice->buyer_phone ?? '',
            'PaymentMode' => (int) ($invoice->payment_mode ?? 1),
            'TotalQuantity' => (float) $invoice->total_quantity,
            'TotalSaleValue' => (float) $invoice->total_sale_value,
            'TotalTaxCharged' => (float) $invoice->total_tax_amount,
            'Discount' => (float) $invoice->discount,
            'FurtherTax' => (float) $invoice->further_tax,
            'TotalBillAmount' => (float) $invoice->total_bill,
            'Items' => $fbrItems,
        ];

        $idempotencyKey = 'FBR-IDEM-' . $invoice->id . '-' . time();
        $startTime = now();

        // 3. Handle Mock / Sandbox vs Live Mode
        if ($environment === 'mock') {
            // Simulated response
            $generatedUsin = 'FBR-' . date('Ymd') . '-' . strtoupper(Str::random(8));

            $submission = FbrSubmission::create([
                'invoice_id' => $invoice->id,
                'batch_id' => $invoice->batch_id,
                'submission_time' => $startTime,
                'status' => 'SUCCESS',
                'request_payload' => json_encode($payload, JSON_PRETTY_PRINT),
                'response_payload' => json_encode([
                    'Response' => '100',
                    'InvoiceNumber' => $generatedUsin,
                    'Code' => '100',
                    'Message' => 'Invoice Successfully Verified and Posted to FBR POS Digital Portal (Mock Mode)',
                ], JSON_PRETTY_PRINT),
                'http_status' => 200,
                'fbr_invoice_number' => $generatedUsin,
                'idempotency_key' => $idempotencyKey,
            ]);

            FbrResponse::create([
                'submission_id' => $submission->id,
                'invoice_id' => $invoice->id,
                'status' => 'ACCEPTED',
                'response_code' => '100',
                'response_message' => 'Invoice Successfully Verified and Posted to FBR POS Digital Portal (Mock Mode)',
                'fbr_usin' => $generatedUsin,
                'raw_response' => json_encode($payload),
            ]);

            $invoice->update([
                'fbr_status' => 'ACCEPTED',
                'fbr_invoice_number' => $generatedUsin,
                'last_error' => null,
            ]);

            AuditService::log('SUBMIT_FBR_SUCCESS', 'Invoice', $invoice->id, [
                'fbrInvoiceNumber' => $generatedUsin,
                'mode' => 'mock',
            ]);

            return [
                'status' => 'ACCEPTED',
                'fbrInvoiceNumber' => $generatedUsin,
                'message' => 'Invoice accepted by FBR POS gateway (Mock)',
                'responseCode' => '100',
            ];
        }

        // Live or Sandbox HTTP Call with Exponential Retries
        $attempt = 0;
        $maxAttempts = 3;
        $response = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $bearerToken,
                    'X-Idempotency-Key' => $idempotencyKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(15)->post($apiUrl, $payload);

                if ($response->successful()) {
                    break;
                }
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    $invoice->update([
                        'fbr_status' => 'FAILED',
                        'retry_count' => $invoice->retry_count + 1,
                        'last_error' => 'Connection to FBR server timed out: ' . $e->getMessage(),
                    ]);

                    FbrSubmission::create([
                        'invoice_id' => $invoice->id,
                        'batch_id' => $invoice->batch_id,
                        'submission_time' => $startTime,
                        'status' => 'FAILED',
                        'request_payload' => json_encode($payload),
                        'response_payload' => json_encode(['error' => $e->getMessage()]),
                        'http_status' => 500,
                        'error_code' => 'TIMEOUT',
                        'error_message' => $e->getMessage(),
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    return [
                        'status' => 'FAILED',
                        'message' => 'FBR submission failed due to network timeout.',
                        'error' => $e->getMessage(),
                    ];
                }
                usleep(pow(2, $attempt) * 200000); // Exponential backoff (400ms, 800ms, 1600ms)
            }
        }

        $resBody = $response ? $response->json() : null;
        $httpStatus = $response ? $response->status() : 500;
        $isSuccess = $response && $response->successful() && isset($resBody['Code']) && (string)$resBody['Code'] === '100';

        $assignedUsin = $resBody['InvoiceNumber'] ?? ($isSuccess ? ('FBR-' . date('Ymd') . '-' . strtoupper(Str::random(8))) : null);

        $submission = FbrSubmission::create([
            'invoice_id' => $invoice->id,
            'batch_id' => $invoice->batch_id,
            'submission_time' => $startTime,
            'status' => $isSuccess ? 'SUCCESS' : 'FAILED',
            'request_payload' => json_encode($payload, JSON_PRETTY_PRINT),
            'response_payload' => json_encode($resBody ?? ['body' => $response?->body()]),
            'http_status' => $httpStatus,
            'error_code' => $isSuccess ? null : (string)($resBody['Code'] ?? 'FBR_ERROR'),
            'error_message' => $isSuccess ? null : ($resBody['Message'] ?? 'FBR response rejected.'),
            'fbr_invoice_number' => $assignedUsin,
            'idempotency_key' => $idempotencyKey,
        ]);

        FbrResponse::create([
            'submission_id' => $submission->id,
            'invoice_id' => $invoice->id,
            'status' => $isSuccess ? 'ACCEPTED' : 'REJECTED',
            'response_code' => (string)($resBody['Code'] ?? $httpStatus),
            'response_message' => $resBody['Message'] ?? ($isSuccess ? 'Accepted' : 'FBR Gateway Error'),
            'fbr_usin' => $assignedUsin,
            'raw_response' => json_encode($resBody ?? []),
        ]);

        if ($isSuccess) {
            $invoice->update([
                'fbr_status' => 'ACCEPTED',
                'fbr_invoice_number' => $assignedUsin,
                'last_error' => null,
            ]);

            return [
                'status' => 'ACCEPTED',
                'fbrInvoiceNumber' => $assignedUsin,
                'message' => $resBody['Message'] ?? 'Invoice accepted by FBR',
                'responseCode' => '100',
            ];
        }

        $invoice->update([
            'fbr_status' => 'FAILED',
            'retry_count' => $invoice->retry_count + 1,
            'last_error' => $resBody['Message'] ?? ('FBR API Error (HTTP ' . $httpStatus . ')'),
        ]);

        return [
            'status' => 'FAILED',
            'message' => $resBody['Message'] ?? 'FBR rejected the invoice transmission.',
            'responseCode' => (string)($resBody['Code'] ?? $httpStatus),
        ];
    }
}
