@extends('layouts.app')

@section('title', 'Failed Submissions')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Failed Submissions & Retry Console</h3>
        <p class="text-muted small mb-0">Inspect transient errors or payload rejections and trigger server-side retries</p>
    </div>
    @if($failedInvoices->count() > 0)
    <form action="{{ route('invoices.retry') }}" method="POST">
        @csrf
        @foreach($failedInvoices as $inv)
            <input type="hidden" name="invoice_ids[]" value="{{ $inv->id }}">
        @endforeach
        <button type="submit" class="btn btn-danger shadow-sm px-4 py-2 fw-bold">
            <i class="bi bi-arrow-clockwise me-1"></i> Retry All {{ $failedInvoices->total() }} Failed Invoices
        </button>
    </form>
    @endif
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Attempts</th>
                    <th>Last Error Message</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($failedInvoices as $inv)
                <tr class="table-danger">
                    <td class="fw-bold font-monospace">{{ $inv->invoice_number }}</td>
                    <td class="text-muted">{{ $inv->invoice_date ? $inv->invoice_date->format('Y-m-d H:i') : '' }}</td>
                    <td>{{ $inv->buyer_name }}</td>
                    <td>
                        <span class="badge bg-secondary rounded-pill">{{ $inv->retry_count }} retries</span>
                    </td>
                    <td class="text-danger fw-semibold">
                        {{ $inv->last_error ?? 'FBR API Gateway Connection Error' }}
                    </td>
                    <td class="text-end fw-bold">PKR {{ number_format($inv->total_bill, 2) }}</td>
                    <td class="text-end">
                        <form action="{{ route('invoices.retry') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger me-1">
                                <i class="bi bi-arrow-clockwise"></i> Retry
                            </button>
                        </form>
                        <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i> Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-success">
                        <i class="bi bi-check-circle-fill fs-2 d-block mb-2 text-success"></i>
                        <div class="fw-bold">No Failed Submissions Found</div>
                        <div class="small text-muted">All valid invoices have been successfully accepted by FBR.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($failedInvoices->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $failedInvoices->links() }}
    </div>
    @endif
</div>
@endsection
