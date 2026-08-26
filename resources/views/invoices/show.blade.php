@extends('layouts.app')

@section('title', 'Invoice Details - ' . $invoice->invoice_number)

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0">Invoice #{{ $invoice->invoice_number }}</h3>
            <x-status-badge type="val" :status="$invoice->validation_status" />
            <x-status-badge type="fbr" :status="$invoice->fbr_status" />
        </div>
        <p class="text-muted small mb-0 mt-1">Issued Date: {{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d H:i:s') : '' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        @if($invoice->fbr_status === 'FAILED')
        <form action="{{ route('invoices.retry') }}" method="POST">
            @csrf
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <button type="submit" class="btn btn-danger fw-bold shadow-sm">
                <i class="bi bi-arrow-clockwise me-1"></i> Retry FBR Submission
            </button>
        </form>
        @endif
    </div>
</div>

@if($invoice->fbr_invoice_number)
<div class="alert alert-success border-success-subtle rounded-3 p-3 mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-patch-check-fill fs-2 text-success"></i>
        <div>
            <div class="fw-bold text-success">FBR POS Digital Receipt Issued</div>
            <div class="font-monospace text-dark">USIN Reference: <strong>{{ $invoice->fbr_invoice_number }}</strong></div>
        </div>
    </div>
    <span class="badge bg-success px-3 py-2">VERIFIED BY FBR</span>
</div>
@endif

<div class="row g-4">
    <!-- Invoice Breakdown & Line Items -->
    <div class="col-12 col-lg-8">
        <!-- Seller & Buyer Card -->
        <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
            <div class="row">
                <div class="col-6 border-end">
                    <div class="text-uppercase text-muted small fw-bold mb-2">Seller Information</div>
                    <div class="fw-bold text-dark fs-6">{{ $invoice->seller_name }}</div>
                    <div class="small text-muted font-monospace mt-1">NTN: {{ $invoice->seller_ntn }}</div>
                    <div class="small text-muted font-monospace">POS Registration ID: {{ $invoice->seller_pos_id }}</div>
                </div>
                <div class="col-6 ps-4">
                    <div class="text-uppercase text-muted small fw-bold mb-2">Buyer / Customer</div>
                    <div class="fw-bold text-dark fs-6">{{ $invoice->buyer_name }}</div>
                    @if($invoice->buyer_ntn)<div class="small text-muted font-monospace mt-1">NTN: {{ $invoice->buyer_ntn }}</div>@endif
                    @if($invoice->buyer_cnic)<div class="small text-muted font-monospace">CNIC: {{ $invoice->buyer_cnic }}</div>@endif
                    @if($invoice->buyer_phone)<div class="small text-muted">Phone: {{ $invoice->buyer_phone }}</div>@endif
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-seam me-1"></i> Line Items Breakdown</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>PCT Tariff</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Sales Tax (18%)</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $it)
                        <tr>
                            <td class="font-monospace fw-semibold">{{ $it->item_code }}</td>
                            <td>{{ $it->item_name }}</td>
                            <td class="font-monospace small">{{ $it->pct_code }}</td>
                            <td class="text-end font-monospace">{{ $it->quantity }}</td>
                            <td class="text-end">PKR {{ number_format($it->unit_price, 2) }}</td>
                            <td class="text-end">PKR {{ number_format($it->tax_charged, 2) }}</td>
                            <td class="text-end fw-bold">PKR {{ number_format($it->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Totals & FBR Response Logs Sidebar -->
    <div class="col-12 col-lg-4">
        <!-- Totals Card -->
        <div class="card border-0 shadow-sm rounded-3 p-4 mb-4 bg-white">
            <h6 class="fw-bold mb-3 border-bottom pb-2">Financial Totals</h6>
            <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Total Sale Value:</span>
                <span class="fw-semibold">PKR {{ number_format($invoice->total_sale_value, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Total Tax Amount:</span>
                <span class="fw-semibold text-info">PKR {{ number_format($invoice->total_tax_amount, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Discount:</span>
                <span class="fw-semibold text-danger">- PKR {{ number_format($invoice->discount, 2) }}</span>
            </div>
            <div class="d-flex justify-content-between pt-3 fs-5 fw-bold text-success">
                <span>Total Bill:</span>
                <span>PKR {{ number_format($invoice->total_bill, 2) }}</span>
            </div>
        </div>

        <!-- Transmission History Logs -->
        <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
            <h6 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-journal-text me-1"></i> FBR Transmission Logs</h6>
            @forelse($invoice->submissions as $sub)
            <div class="mb-3 p-3 bg-light rounded-3 border small">
                <div class="d-flex justify-content-between fw-bold mb-1">
                    <span>Status: {{ $sub->status }}</span>
                    <span class="text-muted font-monospace">{{ $sub->submission_time->format('H:i:s') }}</span>
                </div>
                <div class="font-monospace text-muted mb-1" style="font-size:11px;">Key: {{ substr($sub->idempotency_key, 0, 20) }}...</div>
                @if($sub->error_message)
                    <div class="text-danger mt-1">&bull; {{ $sub->error_message }}</div>
                @endif
            </div>
            @empty
            <div class="text-muted small">No FBR transmission logs recorded yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
