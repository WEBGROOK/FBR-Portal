<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceBatch;
use App\Services\AuditService;
use App\Services\FbrService;
use Illuminate\Http\Request;

class FbrSubmissionController extends Controller
{
    public function show(Request $request)
    {
        $userId = auth()->id();
        $batchId = $request->query('batch_id');

        $batch = null;
        if ($batchId) {
            $batch = InvoiceBatch::where('user_id', $userId)->find($batchId);
        } else {
            $batch = InvoiceBatch::where('user_id', $userId)->latest()->first();
        }

        if (!$batch) {
            return redirect()->route('dashboard')->with('error', 'No batch selected for submission.');
        }

        $invoices = Invoice::where('user_id', $userId)
            ->where('batch_id', $batch->id)
            ->with(['items', 'submissions'])
            ->get();

        $acceptedCount = $invoices->where('fbr_status', 'ACCEPTED')->count();
        $failedCount = $invoices->where('fbr_status', 'FAILED')->count();
        $readyCount = $invoices->whereIn('fbr_status', ['READY', 'PENDING'])->count();

        return view('submissions.index', compact('batch', 'invoices', 'acceptedCount', 'failedCount', 'readyCount'));
    }

    public function submit(Request $request)
    {
        $userId = auth()->id();
        $batchId = $request->input('batch_id');
        $batch = InvoiceBatch::where('user_id', $userId)->findOrFail($batchId);

        // Fetch valid invoices for submission
        $invoices = Invoice::where('user_id', $userId)
            ->where('batch_id', $batch->id)
            ->where('validation_status', 'VALID')
            ->whereIn('fbr_status', ['READY', 'PENDING', 'FAILED'])
            ->get();

        if ($invoices->isEmpty()) {
            return back()->with('info', 'No pending valid invoices available for FBR submission.');
        }

        $accepted = 0;
        $failed = 0;

        foreach ($invoices as $inv) {
            $res = FbrService::submitInvoice($inv);
            if ($res['status'] === 'ACCEPTED') {
                $accepted++;
            } else {
                $failed++;
            }
        }

        AuditService::log('SUBMIT_BATCH_FBR', 'InvoiceBatch', $batch->id, [
            'totalSubmitted' => count($invoices),
            'accepted' => $accepted,
            'failed' => $failed,
        ]);

        return redirect()->route('invoices.submit', ['batch_id' => $batch->id])
            ->with('success', "Batch FBR Submission complete: {$accepted} accepted, {$failed} failed.");
    }

    public function retry(Request $request)
    {
        $userId = auth()->id();
        $invoiceIds = $request->input('invoice_ids', []);
        if (empty($invoiceIds) && $request->has('invoice_id')) {
            $invoiceIds = [$request->input('invoice_id')];
        }

        if (empty($invoiceIds)) {
            return back()->with('error', 'Please select at least one failed invoice to retry.');
        }

        $invoices = Invoice::where('user_id', $userId)->whereIn('id', $invoiceIds)->where('fbr_status', 'FAILED')->get();

        $retriedSuccess = 0;
        $retriedFailed = 0;

        foreach ($invoices as $inv) {
            $res = FbrService::submitInvoice($inv);
            if ($res['status'] === 'ACCEPTED') {
                $retriedSuccess++;
            } else {
                $retriedFailed++;
            }
        }

        AuditService::log('RETRY_FBR', 'Invoice', null, [
            'count' => count($invoices),
            'success' => $retriedSuccess,
            'failed' => $retriedFailed,
        ]);

        return back()->with('success', "Retry complete: {$retriedSuccess} succeeded, {$retriedFailed} failed.");
    }
}
