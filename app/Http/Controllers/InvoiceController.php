<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $search = $request->query('search');
        $fbrStatus = $request->query('fbr_status', 'ALL');
        $valStatus = $request->query('validation_status', 'ALL');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = Invoice::where('user_id', $userId)->with(['items', 'batch'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhere('buyer_name', 'LIKE', "%{$search}%")
                    ->orWhere('buyer_ntn', 'LIKE', "%{$search}%")
                    ->orWhere('fbr_invoice_number', 'LIKE', "%{$search}%");
            });
        }

        if ($fbrStatus !== 'ALL') {
            $query->where('fbr_status', $fbrStatus);
        }

        if ($valStatus !== 'ALL') {
            $query->where('validation_status', $valStatus);
        }

        if ($fromDate) {
            $query->where('invoice_date', '>=', $fromDate . ' 00:00:00');
        }

        if ($toDate) {
            $query->where('invoice_date', '<=', $toDate . ' 23:59:59');
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices', 'search', 'fbrStatus', 'valStatus', 'fromDate', 'toDate'));
    }

    public function show(Invoice $invoice)
    {
        if ($invoice->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to invoice record.');
        }

        $invoice->load(['items', 'batch', 'submissions', 'fbrResponses']);

        return view('invoices.show', compact('invoice'));
    }

    public function failed()
    {
        $failedInvoices = Invoice::where('user_id', auth()->id())
            ->where('fbr_status', 'FAILED')
            ->with(['items', 'submissions'])
            ->latest()
            ->paginate(15);

        return view('invoices.failed', compact('failedInvoices'));
    }
}
