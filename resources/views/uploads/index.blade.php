@extends('layouts.app')

@section('title', 'Upload Invoice File')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">Upload Daily Invoices</h3>
        <p class="text-muted small mb-0">Select or drop PDF, Excel, CSV, or JSON invoice files to parse and validate</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <form action="{{ route('invoices.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <div class="upload-dropzone border-2 border-dashed border-primary-subtle rounded-4 p-5 text-center bg-light" id="dropzone">
                    <div class="mb-3">
                        <i class="bi bi-cloud-arrow-up-fill text-primary" style="font-size:3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Drag and drop your invoice file here</h5>
                    <p class="text-muted small mb-3">Supported file formats: <strong>PDF, XLSX, XLS, CSV, JSON</strong> (Max file size 10MB)</p>

                    <input type="file" name="file" id="fileInput" class="d-none" accept=".pdf,.xlsx,.xls,.csv,.json" required onchange="handleFileSelect(this)">

                    <button type="button" class="btn btn-outline-primary px-4 py-2 fw-semibold" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-folder2-open me-1"></i> Browse File
                    </button>

                    <div id="fileSelectedInfo" class="mt-3 text-success fw-bold d-none">
                        <i class="bi bi-file-earmark-check me-1"></i> <span id="fileNameDisplay"></span>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary px-5 py-2.5 fw-bold shadow-sm" id="submitBtn">
                        <i class="bi bi-gear-wide-connected me-1"></i> Process & Extract Invoice Data
                    </button>
                </div>
            </form>
        </div>

        <!-- Sample Download File Hints -->
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <h6 class="fw-bold mb-2"><i class="bi bi-download me-1 text-primary"></i> Download Sample Files for Testing</h6>
            <p class="text-muted small mb-3">You can use these sample files to test extraction and validation workflows:</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="/samples/sample_invoices.json" download class="btn btn-sm btn-light border text-dark font-monospace">
                    <i class="bi bi-filetype-json text-warning me-1"></i> sample_invoices.json
                </a>
                <a href="/samples/sample_invoices.csv" download class="btn btn-sm btn-light border text-dark font-monospace">
                    <i class="bi bi-filetype-csv text-success me-1"></i> sample_invoices.csv
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('fileNameDisplay').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
            document.getElementById('fileSelectedInfo').classList.remove('d-none');
        }
    }

    const dropzone = document.getElementById('dropzone');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.add('bg-primary-subtle'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('bg-primary-subtle'), false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length) {
            document.getElementById('fileInput').files = files;
            handleFileSelect(document.getElementById('fileInput'));
        }
    });
</script>
@endpush
