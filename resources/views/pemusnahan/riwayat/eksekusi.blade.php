@extends('layouts.app')

@section('title', 'Eksekusi Pemusnahan')

@section('content')
<div class="container-fluid py-4">

    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="bi bi-fire text-danger me-2"></i>
                Eksekusi Pemusnahan Arsip
            </h4>
            <p class="text-muted mb-0">
                <i class="bi bi-shield-exclamation me-1"></i>
                Tahap akhir pemusnahan arsip - Pastikan semua dokumen sudah lengkap
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-danger bg-opacity-10 text-danger p-2">
                <i class="bi bi-exclamation-triangle me-1"></i> Proses Final
            </span>
        </div>
    </div>

    {{-- ================= INFO KEGIATAN ================= --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3">
                            <i class="bi bi-calendar-week text-primary"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Tahun Pemusnahan</small>
                            <h5 class="mb-0 fw-semibold">{{ $pemusnahan->tahun }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                            <i class="bi bi-archive text-info"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Total Arsip yang Dimusnahkan</small>
                            <h5 class="mb-0 fw-semibold">{{ $pemusnahan->details->count() }} Arsip</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3">
                            <i class="bi bi-building text-success"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Unit Pengusul</small>
                            <h5 class="mb-0 fw-semibold">{{ $pemusnahan->unitKerja->nama_unit ?? 'Administrator' }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>

    {{-- ================= DAFTAR ARSIP CARD ================= --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 pt-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3">
                        <i class="bi bi-list-ul text-info"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-semibold">Daftar Arsip yang Akan Dimusnahkan</h5>
                        <p class="text-muted small mb-0 mt-1">Pastikan daftar arsip sudah sesuai sebelum melanjutkan</p>
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-fire me-1"></i> Total: {{ $pemusnahan->details->count() }} Arsip
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="30%">Uraian Arsip</th>
                            <!-- <th width="15%">Kode Arsip</th> -->
                            <th width="10%" class="text-center">Tahun</th>
                            <th width="15%" class="text-center">Jumlah</th>
                            <!-- <th width="15%" class="text-center">Retensi</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemusnahan->details as $index => $detail)
                        <tr>
                            <td class="text-center fw-semibold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-text text-muted me-2"></i>
                                    <span>{{ $detail->arsip->uraian_arsip ?? '-' }}</span>
                                </div>
                            </td>
                            <!-- <td>
                                <code class="small">{{ $detail->arsip->kode_arsip ?? '-' }}</code>
                            </td> -->
                            <td class="text-center">
                                <span class="px-2 py-1 rounded" style="background-color: #f1f5f9; font-size: 0.8rem;">
                                    {{ $detail->arsip->tahun_arsip ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark fw-normal">
                                    {{ $detail->arsip->jumlah ?? 1 }} berkas
                                </span>
                            </td>
                            <!-- <td class="text-center">
                                <span class="small text-muted">
                                    {{ $detail->arsip->retensi ?? '-' }} tahun
                                </span>
                            </td> -->
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                <span class="text-muted">Tidak ada arsip yang akan dimusnahkan</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================= ALERT PERINGATAN ================= --}}
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <div class="d-flex align-items-start">
            <div class="me-3">
                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
            </div>
            <div>
                <strong class="d-block mb-1">PERHATIAN!</strong>
                <p class="mb-0">
                    Proses ini akan menandai arsip sebagai <strong>SUDAH DIMUSNAHKAN</strong> dan <strong>TIDAK DAPAT DIKEMBALIKAN</strong>.
                    Pastikan semua dokumen persetujuan sudah lengkap sebelum melanjutkan.
                </p>
            </div>
        </div>
    </div>

    {{-- ================= FORM EKSEKUSI ================= --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 pt-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3">
                    <i class="bi bi-file-earmark-text text-danger"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0 fw-semibold">Upload Dokumen Pemusnahan</h5>
                    <p class="text-muted small mb-0 mt-1">Unggah dokumen legal sebagai bukti pemusnahan arsip</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('pemusnahan.eksekusi.simpan', $pemusnahan->id) }}" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  id="eksekusiForm">
                @csrf

                {{-- Berita Acara Pemusnahan --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-file-pdf text-danger me-1"></i>
                        Berita Acara Pemusnahan
                        <span class="text-danger">*</span>
                    </label>
                    <div class="upload-area border rounded-3 p-4 text-center bg-light" id="uploadAreaBA">
                        <input type="file" 
                               name="file_berita_acara" 
                               id="file_berita_acara"
                               class="d-none"
                               accept=".pdf"
                               required>
                        <i class="bi bi-cloud-upload fs-1 text-muted mb-2 d-block"></i>
                        <p class="mb-1">Klik atau drag & drop file di sini</p>
                        <small class="text-muted">Format PDF maksimal 50MB</small>
                        <div id="fileNameBA" class="mt-2 small text-success d-none">
                            <i class="bi bi-check-circle"></i> <span></span>
                        </div>
                    </div>
                    @error('file_berita_acara')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- SK Pemusnahan --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-file-earmark-text text-danger me-1"></i>
                        SK Pemusnahan
                        <span class="text-danger">*</span>
                    </label>
                    <div class="upload-area border rounded-3 p-4 text-center bg-light" id="uploadAreaSK">
                        <input type="file" 
                               name="file_sk_pemusnahan" 
                               id="file_sk_pemusnahan"
                               class="d-none"
                               accept=".pdf"
                               required>
                        <i class="bi bi-cloud-upload fs-1 text-muted mb-2 d-block"></i>
                        <p class="mb-1">Klik atau drag & drop file di sini</p>
                        <small class="text-muted">Format PDF maksimal 100MB</small>
                        <div id="fileNameSK" class="mt-2 small text-success d-none">
                            <i class="bi bi-check-circle"></i> <span></span>
                        </div>
                    </div>
                    @error('file_sk_pemusnahan')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Checklist Konfirmasi --}}
                <div class="alert alert-warning border-0 mb-4">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                        <label class="form-check-label fw-semibold" for="confirmCheck">
                            Saya telah memeriksa daftar arsip dan memastikan semua dokumen sudah lengkap
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmRisk" required>
                        <label class="form-check-label fw-semibold" for="confirmRisk">
                            Saya memahami bahwa tindakan ini TIDAK DAPAT DIBATALKAN
                        </label>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                    <a href="{{ route('pemusnahan.usulan.show', $pemusnahan->id) }}" 
                       class="btn btn-outline-secondary px-4">
                        <i class="bi bi-arrow-left me-2"></i>
                        Kembali
                    </a>
                    <button type="submit" class="btn btn-danger px-4" id="submitBtn" disabled>
                        <i class="bi bi-fire me-2"></i>
                        Eksekusi Pemusnahan
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .upload-area {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .upload-area:hover {
        background-color: #e9ecef !important;
        border-color: #dc3545 !important;
    }
    
    .upload-area.drag-over {
        background-color: #fff3f3 !important;
        border-color: #dc3545 !important;
        border-style: dashed !important;
    }
    
    .btn {
        transition: all 0.2s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
    }
    
    .card {
        transition: transform 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
    
    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    code {
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    
    .table tbody tr:hover {
        background-color: rgba(220, 53, 69, 0.03);
    }
</style>
@endpush

@push('scripts')
<script>
    // Upload area functionality for Berita Acara
    const uploadAreaBA = document.getElementById('uploadAreaBA');
    const fileInputBA = document.getElementById('file_berita_acara');
    const fileNameSpanBA = document.getElementById('fileNameBA');
    
    uploadAreaBA.addEventListener('click', () => fileInputBA.click());
    
   fileInputBA.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const fileName = file.name;
        const fileSize = (file.size / 1024 / 1024).toFixed(2);

        if (file.type === 'application/pdf' && fileSize <= 50) {
            fileNameSpanBA.querySelector('span').textContent = `${fileName} (${fileSize} MB)`;
            fileNameSpanBA.classList.remove('d-none');
            uploadAreaBA.style.borderColor = '#28a745';
            uploadAreaBA.style.backgroundColor = '#f8fff8';
        } else {
            alert('File harus berformat PDF dan maksimal 50MB');
            fileInputBA.value = '';
            fileNameSpanBA.classList.add('d-none');
            uploadAreaBA.style.borderColor = '#dee2e6';
            uploadAreaBA.style.backgroundColor = '#f8f9fa';
        }
    }

    checkFormComplete();
});
    
    // Upload area functionality for SK Pemusnahan
    const uploadAreaSK = document.getElementById('uploadAreaSK');
    const fileInputSK = document.getElementById('file_sk_pemusnahan');
    const fileNameSpanSK = document.getElementById('fileNameSK');
    
    uploadAreaSK.addEventListener('click', () => fileInputSK.click());
    
    fileInputSK.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            if (file.type === 'application/pdf' && fileSize <= 10) {
                fileNameSpanSK.querySelector('span').textContent = `${fileName} (${fileSize} MB)`;
                fileNameSpanSK.classList.remove('d-none');
                uploadAreaSK.style.borderColor = '#28a745';
                uploadAreaSK.style.backgroundColor = '#f8fff8';
            } else {
                alert('File harus berformat PDF dan maksimal 100MB');
                fileInputSK.value = '';
                fileNameSpanSK.classList.add('d-none');
                uploadAreaSK.style.borderColor = '#dee2e6';
                uploadAreaSK.style.backgroundColor = '#f8f9fa';
            }
        }
        checkFormComplete();
    });
    
    // Drag & drop functionality
    [uploadAreaBA, uploadAreaSK].forEach(area => {
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('drag-over');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('drag-over');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            const input = area.querySelector('input[type="file"]');
            input.files = e.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        });
    });
    
    // Checkbox validation
    const confirmCheck = document.getElementById('confirmCheck');
    const confirmRisk = document.getElementById('confirmRisk');
    const submitBtn = document.getElementById('submitBtn');
    
    function checkFormComplete() {
        const hasFileBA = fileInputBA.files.length > 0;
        const hasFileSK = fileInputSK.files.length > 0;
        const isConfirmed = confirmCheck.checked && confirmRisk.checked;
        
        submitBtn.disabled = !(hasFileBA && hasFileSK && isConfirmed);
    }
    
    confirmCheck.addEventListener('change', checkFormComplete);
    confirmRisk.addEventListener('change', checkFormComplete);
    
    // Loading state on submit
    const form = document.getElementById('eksekusiForm');
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Memproses...';
    });
</script>
@endpush