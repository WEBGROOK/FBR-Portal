<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $totalInvoices = Invoice::where('user_id', $userId)->count();
        $acceptedInvoices = Invoice::where('user_id', $userId)->where('fbr_status', 'ACCEPTED')->count();
        $failedInvoices = Invoice::where('user_id', $userId)->where('fbr_status', 'FAILED')->count();
        $pendingInvoices = Invoice::where('user_id', $userId)->whereIn('fbr_status', ['PENDING', 'READY', 'PROCESSING'])->count();

        $totalSales = Invoice::where('user_id', $userId)->sum('total_sale_value');
        $totalTax = Invoice::where('user_id', $userId)->sum('total_tax_amount');
        $totalBill = Invoice::where('user_id', $userId)->sum('total_bill');

        $successRate = $totalInvoices > 0 ? round(($acceptedInvoices / $totalInvoices) * 100, 1) : 0;

        $dailyStats = Invoice::where('user_id', $userId)
            ->selectRaw("DATE(invoice_date) as date, COUNT(*) as total, SUM(CASE WHEN fbr_status='ACCEPTED' THEN 1 ELSE 0 END) as accepted, SUM(CASE WHEN fbr_status='FAILED' THEN 1 ELSE 0 END) as failed, SUM(total_bill) as revenue")
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(14)
            ->get();

        return view('reports.index', compact(
            'totalInvoices',
            'acceptedInvoices',
            'failedInvoices',
            'pendingInvoices',
            'totalSales',
            'totalTax',
            'totalBill',
            'successRate',
            'dailyStats'
        ));
    }

    public function exportCsv()
    {
        $userId = auth()->id();
        AuditService::log('EXPORT_REPORTS_CSV', 'Invoice');

        $invoices = Invoice::where('user_id', $userId)->with('items')->latest()->get();

        $filename = "fbr_invoices_report_" . date('Y_m_d_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = [
            'Invoice Number',
            'Invoice Date',
            'Buyer Name',
            'Buyer NTN',
            'Buyer CNIC',
            'Validation Status',
            'FBR Status',
            'FBR USIN',
            'Total Items',
            'Total Quantity',
            'Sale Value (PKR)',
            'Tax Amount (PKR)',
            'Total Bill (PKR)'
        ];

        $callback = function () use ($invoices, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->invoice_date ? $inv->invoice_date->format('Y-m-d H:i:s') : '',
                    $inv->buyer_name,
                    $inv->buyer_ntn ?? 'N/A',
                    $inv->buyer_cnic ?? 'N/A',
                    $inv->validation_status,
                    $inv->fbr_status,
                    $inv->fbr_invoice_number ?? 'N/A',
                    $inv->items->count(),
                    $inv->total_quantity,
                    number_format($inv->total_sale_value, 2, '.', ''),
                    number_format($inv->total_tax_amount, 2, '.', ''),
                    number_format($inv->total_bill, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
