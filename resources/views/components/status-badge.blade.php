@props(['type' => 'fbr', 'status' => 'PENDING'])

@if($type === 'fbr')
    @switch(strtoupper($status))
        @case('ACCEPTED')
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                <i class="bi bi-check-all me-1"></i> ACCEPTED
            </span>
            @break
        @case('SUBMITTED')
        @case('PROCESSING')
            <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 rounded-pill">
                <i class="bi bi-arrow-repeat spin me-1"></i> PROCESSING
            </span>
            @break
        @case('FAILED')
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill">
                <i class="bi bi-x-circle me-1"></i> FAILED
            </span>
            @break
        @case('READY')
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill">
                <i class="bi bi-clock-history me-1"></i> READY TO SUBMIT
            </span>
            @break
        @default
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1 rounded-pill">
                <i class="bi bi-dash-circle me-1"></i> PENDING
            </span>
    @endswitch
@else
    @switch(strtoupper($status))
        @case('VALID')
            <span class="badge bg-success text-white px-2 py-1">
                <i class="bi bi-shield-check me-1"></i> VALID
            </span>
            @break
        @case('DUPLICATE')
            <span class="badge bg-warning text-dark px-2 py-1">
                <i class="bi bi-copy me-1"></i> DUPLICATE
            </span>
            @break
        @case('MISSING_REQUIRED_FIELD')
            <span class="badge bg-warning text-dark px-2 py-1">
                <i class="bi bi-exclamation-square me-1"></i> MISSING FIELD
            </span>
            @break
        @default
            <span class="badge bg-danger text-white px-2 py-1">
                <i class="bi bi-exclamation-octagon me-1"></i> INVALID
            </span>
    @endswitch
@endif
