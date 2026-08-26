@extends('layouts.app')

@section('title', 'Reports & Export')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">FBR Financial Reports & CSV Export</h3>
        <p class="text-muted small mb-0">Summary analytics, compliance success rate, and one-click data export</p>
    </div>
    <a href="{{ route('reports.export') }}" class="btn btn-success shadow-sm px-4 py-2 fw-bold">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Invoices to CSV
    </a>
</div>

<!-- Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold">TOTAL INVOICES</div>
            <div class="fs-3 fw-bold text-dark mt-1">{{ number_format($totalInvoices) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold text-success">ACCEPTED BY FBR</div>
            <div class="fs-3 fw-bold text-success mt-1">{{ number_format($acceptedInvoices) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold text-danger">FAILED / REJECTED</div>
            <div class="fs-3 fw-bold text-danger mt-1">{{ number_format($failedInvoices) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold text-primary">FBR SUCCESS RATE</div>
            <div class="fs-3 fw-bold text-primary mt-1">{{ $successRate }}%</div>
        </div>
    </div>
</div>

<!-- Financial Totals Row -->
<div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
    <h6 class="fw-bold mb-3"><i class="bi bi-graph-up text-primary me-1"></i> Financial Performance Metrics</h6>
    <div class="row text-center g-3">
        <div class="col-4 border-end">
            <div class="text-muted small">Total Sales Volume</div>
            <div class="fw-bold fs-4 text-dark mt-1">PKR {{ number_format($totalSales, 2) }}</div>
        </div>
        <div class="col-4 border-end">
            <div class="text-muted small">Total GST Tax Collected</div>
            <div class="fw-bold fs-4 text-info mt-1">PKR {{ number_format($totalTax, 2) }}</div>
        </div>
        <div class="col-4">
            <div class="text-muted small">Total Revenue (Bill Amount)</div>
            <div class="fw-bold fs-4 text-success mt-1">PKR {{ number_format($totalBill, 2) }}</div>
        </div>
    </div>
</div>

<!-- Daily Trends Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-1"></i> Daily Submission Breakdown (Last 14 Days)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th class="text-center">Total Invoices</th>
                    <th class="text-center">Accepted</th>
                    <th class="text-center">Failed</th>
                    <th class="text-end">Daily Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyStats as $stat)
                <tr>
                    <td class="fw-bold font-monospace">{{ $stat->date }}</td>
                    <td class="text-center font-monospace">{{ $stat->total }}</td>
                    <td class="text-center text-success font-monospace fw-bold">{{ $stat->accepted }}</td>
                    <td class="text-center text-danger font-monospace fw-bold">{{ $stat->failed }}</td>
                    <td class="text-end fw-bold text-dark">PKR {{ number_format($stat->revenue, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">No daily transaction records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
