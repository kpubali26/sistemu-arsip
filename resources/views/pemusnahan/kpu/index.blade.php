@extends('layouts.app')

@section('title', 'Persetujuan KPU RI')

@section('content')
<div class="container-fluid py-4">

    {{-- HEADER SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold text-primary">
                <i class="bi bi-check2-circle me-2"></i>Persetujuan KPU RI
            </h4>
            <p class="text-muted mb-0">Upload dan kelola persetujuan pemusnahan arsip</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                <i class="bi bi-file-earmark-text me-1"></i> {{ $pemusnahan->details->count() }} Arsip
            </span>
        </div>
    </div>

    {{-- LIST ARSIP CARD --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                    <i class="bi bi-archive text-info"></i>
                </div>
                <h5 class="card-title mb-0 fw-semibold">Daftar Arsip yang Diajukan</h5>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="55%">Uraian Arsip</th>
                            <th width="15%">Tahun</th>
                            <th width="25%">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemusnahan->details as $i => $d)
                        <tr>
                            <td class="text-center fw-semibold">{{ $i+1 }}</td>
                            <td>{{ $d->arsip->uraian_arsip }}</td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ $d->arsip->tahun_arsip }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($d->keputusan) {
                                        'Setuju' => 'success',
                                        'Tolak' => 'danger',
                                        'Revisi' => 'warning',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }} bg-opacity-10 text-{{ $badgeClass }} px-3 py-2">
                                    <i class="bi bi-{{ $d->keputusan == 'Setuju' ? 'check-circle' : ($d->keputusan == 'Tolak' ? 'x-circle' : 'clock-history') }} me-1"></i>
                                    {{ $d->keputusan }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <span class="text-muted">Tidak ada data arsip</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- UPLOAD CARD --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                    <i class="bi bi-cloud-upload text-success"></i>
                </div>
                <h5 class="card-title mb-0 fw-semibold">Upload Persetujuan KPU</h5>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('pemusnahan.kpu.simpan', $pemusnahan->id) }}" id="uploadForm">
                @csrf
                
                {{-- FILE INPUT --}}
                <div class="mb-4">
                    <label for="file_persetujuan_kpu" class="form-label fw-semibold">
                        <i class="bi bi-file-pdf me-1"></i> File Persetujuan
                    </label>
                    <input type="file" 
                           name="file_persetujuan_kpu" 
                           id="file_persetujuan_kpu" 
                           class="form-control @error('file_persetujuan_kpu') is-invalid @enderror" 
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           required>
                    <div class="form-text mt-2">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> 
                            Format: PDF, DOC, DOCX, JPG, PNG | Maksimal 100MB
                        </small>
                    </div>
                    @error('file_persetujuan_kpu')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Preview Area --}}
                <div id="filePreview" class="mb-4 d-none">
                    <div class="alert alert-light border bg-light">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-file-earmark-check fs-4 text-success me-3"></i>
                            <div>
                                <strong>File siap diupload:</strong><br>
                                <span id="fileName" class="text-muted small"></span>
                                <span id="fileSize" class="text-muted small ms-2"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUBMIT BUTTON --}}
                <div class="d-grid gap-2 d-md-block">
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" id="submitBtn">
                        <i class="bi bi-upload me-2"></i> Upload Persetujuan KPU
                    </button>
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary px-4 py-2">
                        <i class="bi bi-arrow-left me-2"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // File preview functionality
    const fileInput = document.getElementById('file_persetujuan_kpu');
    const preview = document.getElementById('filePreview');
    const fileNameSpan = document.getElementById('fileName');
    const fileSizeSpan = document.getElementById('fileSize');
    
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            fileNameSpan.textContent = file.name;
            const fileSizeKB = (file.size / 1024).toFixed(2);
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            fileSizeSpan.textContent = fileSizeMB > 1 ? `${fileSizeMB} MB` : `${fileSizeKB} KB`;
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    });

    // Loading state on submit
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    
    uploadForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Mengupload...';
    });
</script>
@endpush