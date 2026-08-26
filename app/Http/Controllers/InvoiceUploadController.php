<?php

namespace App\Http\Controllers;

use App\Models\InvoiceBatch;
use App\Services\AuditService;
use App\Services\InvoiceParserService;
use App\Services\InvoiceValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceUploadController extends Controller
{
    public function show()
    {
        return view('uploads.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,xlsx,xls,csv,json,txt', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileExt = strtolower($file->getClientOriginalExtension());
        $fileSize = $file->getSize();

        // Secure file storage
        $storedName = Str::uuid()->toString() . '.' . $fileExt;
        $path = $file->storeAs('invoices', $storedName, 'local');
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $path);
        }

        try {
            $user = auth()->user();

            // 1. Create Batch record
            $batch = InvoiceBatch::create([
                'user_id' => $user->id,
                'file_name' => $originalName,
                'file_type' => $fileExt,
                'file_size' => $fileSize,
                'status' => 'PROCESSING',
            ]);

            $defaultSeller = [
                'ntn' => $user->seller_ntn ?? env('FBR_SELLER_NTN', '7890123-4'),
                'name' => $user->name ?? env('FBR_SELLER_NAME', 'AL-MADINA TRADERS LTD'),
                'pos_id' => $user->pos_id ?? env('FBR_POS_ID', 'POS-100234'),
            ];

            // 2. Parse Invoice Data
            $rawInvoices = InvoiceParserService::parseFile($fullPath, $fileExt);

            if (empty($rawInvoices)) {
                $batch->update(['status' => 'FAILED']);
                return back()->with('error', 'No invoice records could be found in the uploaded file.');
            }

            // 3. Validate Invoice Batch
            $validatedList = InvoiceValidationService::validateBatch($rawInvoices, $defaultSeller);

            $validCount = 0;
            $invalidCount = 0;

            DB::transaction(function () use ($batch, $user, $validatedList, &$validCount, &$invalidCount) {
                foreach ($validatedList as $item) {
                    $items = $item['items'];
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

                    foreach ($items as $it) {
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

            AuditService::log('UPLOAD_SUCCESS', 'InvoiceBatch', $batch->id, [
                'fileName' => $originalName,
                'totalCount' => count($validatedList),
                'validCount' => $validCount,
            ]);

            return redirect()->route('invoices.preview', ['batch_id' => $batch->id])
                ->with('success', "File \"{$originalName}\" processed successfully. Extracted {$batch->total_count} invoices ({$validCount} valid).");

        } catch (\Throwable $e) {
            AuditService::log('UPLOAD_FAILED', 'InvoiceBatch', null, ['error' => $e->getMessage()]);
            return back()->with('error', 'File processing failed: ' . $e->getMessage());
        }
    }
}
