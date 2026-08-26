<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceBatch;
use Illuminate\Http\Request;

class InvoicePreviewController extends Controller
{
    public function show(Request $request)
    {
        $userId = auth()->id();
        $batchId = $request->query('batch_id');
        $statusFilter = $request->query('status', 'ALL');

        $batch = null;
        if ($batchId) {
            $batch = InvoiceBatch::where('user_id', $userId)->find($batchId);
        } else {
            $batch = InvoiceBatch::where('user_id', $userId)->latest()->first();
        }

        if (!$batch) {
            return redirect()->route('invoices.upload')->with('info', 'Please upload an invoice batch first to preview.');
        }

        $query = Invoice::where('user_id', $userId)->where('batch_id', $batch->id)->with('items');

        if ($statusFilter !== 'ALL') {
            $query->where('validation_status', $statusFilter);
        }

        $invoices = $query->paginate(20)->withQueryString();

        $validCount = Invoice::where('user_id', $userId)->where('batch_id', $batch->id)->where('validation_status', 'VALID')->count();
        $invalidCount = Invoice::where('user_id', $userId)->where('batch_id', $batch->id)->where('validation_status', '!=', 'VALID')->count();
        $totalBillSum = Invoice::where('user_id', $userId)->where('batch_id', $batch->id)->sum('total_bill');

        return view('invoices.preview', compact(
            'batch',
            'invoices',
            'statusFilter',
            'validCount',
            'invalidCount',
            'totalBillSum'
        ));
    }
}
