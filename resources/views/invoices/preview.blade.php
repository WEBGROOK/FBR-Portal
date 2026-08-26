@extends('layouts.app')

@section('title', 'Invoice Batch Preview')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Extracted Invoices Preview</h3>
        <p class="text-muted small mb-0">Batch File: <strong>{{ $batch->file_name }}</strong> (Extracted {{ $batch->total_count }} records)</p>
    </div>
    @if($validCount > 0)
    <form action="{{ route('invoices.submit') }}" method="POST">
        @csrf
        <input type="hidden" name="batch_id" value="{{ $batch->id }}">
        <button type="submit" class="btn btn-success shadow-sm px-4 py-2 fw-bold">
            <i class="bi bi-send-check me-1"></i> Submit {{ $validCount }} Valid Invoices to FBR
        </button>
    </form>
    @endif
</div>

<!-- Filter & Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold">TOTAL RECORDS</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $batch->total_count }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold text-success">VALID RECS</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ $validCount }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold text-danger">INVALID / DUPLICATES</div>
            <div class="fs-4 fw-bold text-danger mt-1">{{ $invalidCount }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3 text-center bg-white">
            <div class="text-muted small fw-semibold">BATCH TOTAL VALUE</div>
            <div class="fs-4 fw-bold text-primary mt-1">PKR {{ number_format($totalBillSum, 2) }}</div>
        </div>
    </div>
</div>

<!-- Table Filter Buttons -->
<div class="d-flex align-items-center gap-2 mb-3">
    <span class="text-muted small me-2">Filter by Status:</span>
    <a href="{{ route('invoices.preview', ['batch_id' => $batch->id, 'status' => 'ALL']) }}" class="btn btn-sm {{ $statusFilter === 'ALL' ? 'btn-primary' : 'btn-light border' }}">All ({{ $batch->total_count }})</a>
    <a href="{{ route('invoices.preview', ['batch_id' => $batch->id, 'status' => 'VALID']) }}" class="btn btn-sm {{ $statusFilter === 'VALID' ? 'btn-success' : 'btn-light border' }}">Valid Only ({{ $validCount }})</a>
    <a href="{{ route('invoices.preview', ['batch_id' => $batch->id, 'status' => 'INVALID']) }}" class="btn btn-sm {{ $statusFilter === 'INVALID' ? 'btn-danger' : 'btn-light border' }}">Invalid</a>
    <a href="{{ route('invoices.preview', ['batch_id' => $batch->id, 'status' => 'DUPLICATE']) }}" class="btn btn-sm {{ $statusFilter === 'DUPLICATE' ? 'btn-warning text-dark' : 'btn-light border' }}">Duplicates</a>
</div>

<!-- Extracted Invoices Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Buyer / Customer</th>
                    <th>NTN / CNIC</th>
                    <th>Validation</th>
                    <th class="text-end">Items</th>
                    <th class="text-end">Tax (18%)</th>
                    <th class="text-end">Total Bill</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr class="{{ $inv->validation_status !== 'VALID' ? 'table-warning' : '' }}">
                    <td class="fw-bold font-monospace">{{ $inv->invoice_number }}</td>
                    <td class="text-muted">{{ $inv->invoice_date ? $inv->invoice_date->format('Y-m-d') : '' }}</td>
                    <td>
                        <div class="fw-semibold text-dark">{{ $inv->buyer_name }}</div>
                        <div class="small text-muted">{{ $inv->buyer_phone }}</div>
                    </td>
                    <td class="font-monospace small">
                        @if($inv->buyer_ntn) NTN: {{ $inv->buyer_ntn }} <br> @endif
                        @if($inv->buyer_cnic) CNIC: {{ $inv->buyer_cnic }} @endif
                        @if(!$inv->buyer_ntn && !$inv->buyer_cnic) <span class="text-muted">Walk-in</span> @endif
                    </td>
                    <td>
                        <x-status-badge type="val" :status="$inv->validation_status" />
                        @if(!empty($inv->validation_errors))
                            <div class="small text-danger mt-1">
                                @foreach($inv->validation_errors as $err)
                                    <div>&bull; {{ $err['message'] }}</div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-end font-monospace">{{ $inv->items->count() }}</td>
                    <td class="text-end">PKR {{ number_format($inv->total_tax_amount, 2) }}</td>
                    <td class="text-end fw-bold text-dark">PKR {{ number_format($inv->total_bill, 2) }}</td>
                    <td class="text-end">
                        <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye me-1"></i> Inspect
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">No invoices matching the selected filter.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
