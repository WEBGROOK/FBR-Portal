@extends('layouts.app')

@section('title', 'FBR Batch Submissions')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">FBR Digital Transmission Results</h3>
        <p class="text-muted small mb-0">Batch File: <strong>{{ $batch->file_name }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.preview', ['batch_id' => $batch->id]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Preview
        </a>
        @if($readyCount > 0)
        <form action="{{ route('invoices.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="batch_id" value="{{ $batch->id }}">
            <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-send me-1"></i> Submit Remaining ({{ $readyCount }})
            </button>
        </form>
        @endif
    </div>
</div>

<!-- Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3 text-center bg-white border-start border-4 border-success">
            <div class="text-muted small fw-semibold text-uppercase">ACCEPTED BY FBR</div>
            <div class="fs-3 fw-bold text-success mt-1">{{ number_format($acceptedCount) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3 text-center bg-white border-start border-4 border-danger">
            <div class="text-muted small fw-semibold text-uppercase">FAILED / REJECTED</div>
            <div class="fs-3 fw-bold text-danger mt-1">{{ number_format($failedCount) }}</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3 text-center bg-white border-start border-4 border-warning">
            <div class="text-muted small fw-semibold text-uppercase">READY / PENDING</div>
            <div class="fs-3 fw-bold text-warning mt-1">{{ number_format($readyCount) }}</div>
        </div>
    </div>
</div>

<!-- Invoices Submission Table -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff me-1"></i> Submission Status Feed</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Buyer</th>
                    <th>FBR Status</th>
                    <th>FBR USIN Reference</th>
                    <th>Error Message</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td class="fw-bold font-monospace">{{ $inv->invoice_number }}</td>
                    <td>{{ $inv->buyer_name }}</td>
                    <td><x-status-badge type="fbr" :status="$inv->fbr_status" /></td>
                    <td class="font-monospace text-primary fw-bold">
                        {{ $inv->fbr_invoice_number ?? '—' }}
                    </td>
                    <td class="small text-danger">
                        {{ $inv->last_error ?? '—' }}
                    </td>
                    <td class="text-end fw-bold">PKR {{ number_format($inv->total_bill, 2) }}</td>
                    <td class="text-end">
                        @if($inv->fbr_status === 'FAILED')
                        <form action="{{ route('invoices.retry') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger me-1">
                                <i class="bi bi-arrow-clockwise"></i> Retry
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i> Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No invoices submitted in this batch yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
