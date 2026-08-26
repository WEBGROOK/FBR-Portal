<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceBatch;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $user = auth()->user();
        $today = now()->startOfDay();

        $totalInvoicesToday = Invoice::where('user_id', $userId)->where('created_at', '>=', $today)->count();
        $pendingInvoices = Invoice::where('user_id', $userId)->whereIn('fbr_status', ['PENDING', 'READY'])->count();
        $processingInvoices = Invoice::where('user_id', $userId)->where('fbr_status', 'PROCESSING')->count();
        $submittedCount = Invoice::where('user_id', $userId)->where('fbr_status', 'ACCEPTED')->count();
        $failedCount = Invoice::where('user_id', $userId)->where('fbr_status', 'FAILED')->count();

        $totalSales = Invoice::where('user_id', $userId)->sum('total_sale_value');
        $totalTax = Invoice::where('user_id', $userId)->sum('total_tax_amount');
        $totalRevenue = Invoice::where('user_id', $userId)->sum('total_bill');

        $recentBatches = InvoiceBatch::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        $recentInvoices = Invoice::where('user_id', $userId)
            ->with('batch')
            ->latest()
            ->take(8)
            ->get();

        $fbrEnv = config('services.fbr.environment', env('FBR_ENVIRONMENT', 'mock'));
        $fbrStatus = [
            'mode' => strtoupper($fbrEnv),
            'pos_id' => $user->pos_id ?? env('FBR_POS_ID', 'POS-100234'),
            'seller_ntn' => $user->seller_ntn ?? env('FBR_SELLER_NTN', '7890123-4'),
            'connected' => true,
        ];

        return view('dashboard.index', compact(
            'totalInvoicesToday',
            'pendingInvoices',
            'processingInvoices',
            'submittedCount',
            'failedCount',
            'totalSales',
            'totalTax',
            'totalRevenue',
            'recentBatches',
            'recentInvoices',
            'fbrStatus'
        ));
    }
}
