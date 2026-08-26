@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Invoice Control Dashboard</h3>
        <p class="text-muted small mb-0">Overview of today's digital invoices and FBR submission states</p>
    </div>
    <a href="{{ route('invoices.upload') }}" class="btn btn-primary shadow-sm px-3 py-2 fw-semibold">
        <i class="bi bi-cloud-arrow-up me-1"></i> Upload Invoices
    </a>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-summary border-0 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Invoices Today</div>
                    <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalInvoicesToday) }}</h3>
                </div>
                <div class="bg-primary-subtle text-primary p-3 rounded-3 fs-4">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-summary border-0 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Pending / Ready</div>
                    <h3 class="fw-bold text-warning mt-1 mb-0">{{ number_format($pendingInvoices) }}</h3>
                </div>
                <div class="bg-warning-subtle text-warning p-3 rounded-3 fs-4">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-summary border-0 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Submitted & Accepted</div>
                    <h3 class="fw-bold text-success mt-1 mb-0">{{ number_format($submittedCount) }}</h3>
                </div>
                <div class="bg-success-subtle text-success p-3 rounded-3 fs-4">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-summary border-0 bg-white p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Failed Invoices</div>
                    <h3 class="fw-bold text-danger mt-1 mb-0">{{ number_format($failedCount) }}</h3>
                </div>
                <div class="bg-danger-subtle text-danger p-3 rounded-3 fs-4">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Stats & FBR Status Info -->
<div class="row g-3 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-currency-dollar text-primary me-1"></i> Financial Overview</h6>
            <div class="row text-center g-3">
                <div class="col-4 border-end">
                    <div class="text-muted small">Total Sales</div>
                    <div class="fw-bold fs-5 text-dark mt-1">PKR {{ number_format($totalSales, 2) }}</div>
                </div>
                <div class="col-4 border-end">
                    <div class="text-muted small">18% Sales Tax</div>
                    <div class="fw-bold fs-5 text-info mt-1">PKR {{ number_format($totalTax, 2) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fw-bold fs-5 text-success mt-1">PKR {{ number_format($totalRevenue, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
            <h6 class="fw-bold mb-2"><i class="bi bi-shield-check text-success me-1"></i> FBR Gateway Parameters</h6>
            <div class="small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Mode:</span>
                    <span class="fw-bold text-primary">{{ $fbrStatus['mode'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">POS ID:</span>
                    <span class="font-monospace text-dark">{{ $fbrStatus['pos_id'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Seller NTN:</span>
                    <span class="font-monospace text-dark">{{ $fbrStatus['seller_ntn'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Invoices Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-1"></i> Recent Uploaded Invoices</h6>
        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">View All Archive</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Buyer Name</th>
                    <th>Validation</th>
                    <th>FBR Status</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentInvoices as $inv)
                <tr>
                    <td class="fw-semibold font-monospace">{{ $inv->invoice_number }}</td>
                    <td class="text-muted">{{ $inv->invoice_date ? $inv->invoice_date->format('Y-m-d H:i') : '' }}</td>
                    <td>{{ $inv->buyer_name }}</td>
                    <td><x-status-badge type="val" :status="$inv->validation_status" /></td>
                    <td><x-status-badge type="fbr" :status="$inv->fbr_status" /></td>
                    <td class="text-end fw-bold">PKR {{ number_format($inv->total_bill, 2) }}</td>
                    <td class="text-end">
                        <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i> Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No invoices found in database. <a href="{{ route('invoices.upload') }}">Upload your first file</a>.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
