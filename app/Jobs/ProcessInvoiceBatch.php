<?php

namespace App\Jobs;

use App\Models\InvoiceBatch;

use App\Services\InvoiceParserService;
use App\Services\InvoiceValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessInvoiceBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $batchId, public string $filePath, public string $fileType)
    {
    }

    public function handle(): void
    {
        $batch = InvoiceBatch::find($this->batchId);
        if (!$batch) return;

        $batch->update(['status' => 'PROCESSING']);

        try {
            $user = $batch->user;
            $defaultSeller = [
                'ntn' => $user->seller_ntn ?? env('FBR_SELLER_NTN', '7890123-4'),
                'name' => $user->name ?? env('FBR_SELLER_NAME', 'AL-MADINA TRADERS LTD'),
                'pos_id' => $user->pos_id ?? env('FBR_POS_ID', 'POS-100234'),
            ];

            // 1. Parse File
            $rawInvoices = InvoiceParserService::parseFile($this->filePath, $this->fileType);

            // 2. Validate Invoices
            $validatedList = InvoiceValidationService::validateBatch($rawInvoices, $defaultSeller);

            $validCount = 0;
            $invalidCount = 0;

            DB::transaction(function () use ($batch, $user, $validatedList, &$validCount, &$invalidCount) {
                foreach ($validatedList as $item) {
                    $invoiceItems = $item['items'];
                    unset($item['items']);

                    $item['batch_id'] = $batch->id;
                    $item['user_id'] = $user->id;
                    $item['fbr_status'] = $item['validation_status'] === 'VALID' ? 'READY' : 'PENDING';

                    if ($item['validation_status'] === 'VALID') {
                        $validCount++;
                    } else {
                        $invalidCount++;
                    }

                    $createdInvoice = $batch->invoices()->create($item);

                    foreach ($invoiceItems as $it) {
                        $createdInvoice->items()->create($it);
                    }
                }

                $batch->update([
                    'total_count' => count($validatedList),
                    'valid_count' => $validCount,
                    'invalid_count' => $invalidCount,
                    'status' => 'COMPLETED',
                ]);
            });

        } catch (\Throwable $e) {
            logger()->error('Batch processing job failed: ' . $e->getMessage());
            $batch->update(['status' => 'FAILED']);
        }
    }
}
