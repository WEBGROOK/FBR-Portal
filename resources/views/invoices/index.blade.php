@extends('layouts.app')

@section('title', 'Invoice Archive')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Invoice Archive & Search</h3>
        <p class="text-muted small mb-0">Search historical invoices by invoice number, buyer name, or FBR USIN</p>
    </div>
</div>

<!-- Search & Filters Form -->
<div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
    <form action="{{ route('invoices.index') }}" method="GET" class="row g-3">
        <div class="col-12 col-md-4">
            <label for="search" class="form-label small fw-semibold">Search Keywords</label>
            <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Invoice #, Buyer, USIN..." value="{{ $search }}">
        </div>

        <div class="col-6 col-md-2">
            <label for="fbr_status" class="form-label small fw-semibold">FBR Status</label>
            <select name="fbr_status" id="fbr_status" class="form-select form-select-sm">
                <option value="ALL" {{ $fbrStatus === 'ALL' ? 'selected' : '' }}>All Statuses</option>
                <option value="ACCEPTED" {{ $fbrStatus === 'ACCEPTED' ? 'selected' : '' }}>ACCEPTED</option>
                <option value="FAILED" {{ $fbrStatus === 'FAILED' ? 'selected' : '' }}>FAILED</option>
                <option value="READY" {{ $fbrStatus === 'READY' ? 'selected' : '' }}>READY</option>
                <option value="PENDING" {{ $fbrStatus === 'PENDING' ? 'selected' : '' }}>PENDING</option>
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label for="validation_status" class="form-label small fw-semibold">Validation</label>
            <select name="validation_status" id="validation_status" class="form-select form-select-sm">
                <option value="ALL" {{ $valStatus === 'ALL' ? 'selected' : '' }}>All Validations</option>
                <option value="VALID" {{ $valStatus === 'VALID' ? 'selected' : '' }}>VALID</option>
                <option value="INVALID" {{ $valStatus === 'INVALID' ? 'selected' : '' }}>INVALID</option>
                <option value="DUPLICATE" {{ $valStatus === 'DUPLICATE' ? 'selected' : '' }}>DUPLICATE</option>
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label for="from_date" class="form-label small fw-semibold">From Date</label>
            <input type="date" name="from_date" id="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
        </div>

        <div class="col-6 col-md-2 d-flex align-items-end gap-2">
            <div class="w-100">
                <label for="to_date" class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to_date" id="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">Filter</button>
        </div>
    </form>
</div>

<!-- Invoices Archive Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Buyer / Customer</th>
                    <th>Validation</th>
                    <th>FBR Status</th>
                    <th>FBR USIN</th>
                    <th class="text-end">Total Amount</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td class="fw-bold font-monospace">{{ $inv->invoice_number }}</td>
                    <td class="text-muted">{{ $inv->invoice_date ? $inv->invoice_date->format('Y-m-d H:i') : '' }}</td>
                    <td>
                        <div class="fw-semibold text-dark">{{ $inv->buyer_name }}</div>
                        <div class="small text-muted">{{ $inv->buyer_ntn ?? $inv->buyer_cnic }}</div>
                    </td>
                    <td><x-status-badge type="val" :status="$inv->validation_status" /></td>
                    <td><x-status-badge type="fbr" :status="$inv->fbr_status" /></td>
                    <td class="font-monospace text-primary fw-bold">{{ $inv->fbr_invoice_number ?? '—' }}</td>
                    <td class="text-end fw-bold">PKR {{ number_format($inv->total_bill, 2) }}</td>
                    <td class="text-end">
                        <a href="{{ route('invoices.show', $inv->id) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No invoices found matching criteria.</td>
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
