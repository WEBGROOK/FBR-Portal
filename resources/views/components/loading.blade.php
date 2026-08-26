@props(['text' => 'Processing...'])

<div class="d-flex align-items-center gap-2 text-primary my-2">
    <div class="spinner-border spinner-border-sm" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <span class="fw-medium" style="font-size:0.9rem;">{{ $text }}</span>
</div>
