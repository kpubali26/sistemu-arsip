{{-- resources/views/berita-acara/show.blade.php --}}

@extends('layouts.app')

@section('page-title', 'Detail Berita Acara')
@section('page-subtitle', 'Informasi Lengkap Berita Acara Pemindahan')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Tombol Kembali -->
        <div class="mb-3">
            <a href="{{ route('berita-acara.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <!-- Card Detail BAP -->
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $berita_acara->nomor_bap }}</h5>
                    <span class="badge bg-{{ 
                        $berita_acara->status == 'DRAFT' ? 'secondary' : 
                        ($berita_acara->status == 'DIAJUKAN' ? 'warning' : 
                        ($berita_acara->status == 'DISETUJUI' ? 'success' : 'danger')) 
                    }}">
                        {{ $berita_acara->status }}
                    </span>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Informasi BAP -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Nomor BAP</label>
                        <p class="mb-0">{{ $berita_acara->nomor_bap }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Tanggal BAP</label>
                        <p class="mb-0">{{ \Carbon\Carbon::parse($berita_acara->tanggal_bap)->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Jumlah Arsip</label>
                        <p class="mb-0">{{ $berita_acara->details->count() }} arsip</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Tanggal Kirim</label>
                        <p class="mb-0">{{ $berita_acara->tanggal_kirim ? \Carbon\Carbon::parse($berita_acara->tanggal_kirim)->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    
                    @if($berita_acara->status == 'DITERIMA')
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Tanggal Diterima</label>
                        <p class="mb-0">{{ \Carbon\Carbon::parse($berita_acara->tanggal_diterima)->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small fw-bold text-uppercase d-block">Diterima Oleh</label>
                        <p class="mb-0">{{ $berita_acara->diterima_by ? $berita_acara->diterimaBy->name : '-' }}</p>
                    </div>
                    @endif
                    
                    @if($berita_acara->status == 'DITOLAK')
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <strong>Alasan Ditolak:</strong>
                            <p class="mb-0 mt-1">{{ $berita_acara->alasan_ditolak }}</p>
                            @if($berita_acara->tanggal_ditolak)
                                <small class="text-muted">Ditolak pada: {{ \Carbon\Carbon::parse($berita_acara->tanggal_ditolak)->format('d/m/Y H:i') }}</small>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Tombol Aksi -->
                <div class="mt-3 pt-3 border-top">
                    @if($berita_acara->file_bap)
                        <a href="{{ asset('storage/berita_acara/'.$berita_acara->file_bap) }}"
                           target="_blank"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-file-pdf me-1"></i> Lihat File
                        </a>
                    @endif
                    
                    @if($berita_acara->status == 'DIAJUKAN' || $berita_acara->status == 'DITERIMA')
                        <a href="{{ route('berita-acara.exportLampiran', $berita_acara->id) }}"
                           class="btn btn-sm btn-success">
                            <i class="bi bi-file-excel me-1"></i> Export Lampiran
                        </a>
                    @endif

                    @if($berita_acara->canSend())
                        <button type="button" 
                                class="btn btn-sm btn-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#kirimModal">
                            <i class="bi bi-send me-1"></i> Kirim
                        </button>
                    @endif

                    @if($berita_acara->status == 'DRAFT')
                        <a href="{{ route('berita-acara.edit', $berita_acara->id) }}" 
                           class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                        <form action="{{ route('berita-acara.destroy', $berita_acara->id) }}" 
                              method="POST" 
                              data-confirm="delete"
                              class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash me-1"></i> Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Daftar Arsip -->
        <div class="card mt-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Daftar Arsip</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Judul Arsip</th>
                                <th>Tahun</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($berita_acara->details as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><span class="badge bg-secondary">{{ $detail->arsip->kodeKlasifikasi->kode ?? '-' }}</span></td>
                                <td>{{ Str::limit($detail->arsip->uraian_arsip, 80) }}</td>
                                <td>{{ $detail->arsip->tahun_arsip }}</td>
                              <td>
                                <span class="badge bg-{{ 
                                    $detail->arsip->status_pindah == 'DIAJUKAN' ? 'warning' :
                                    ($detail->arsip->status_pindah == 'DITERIMA' ? 'success' :
                                    ($detail->arsip->status_pindah == 'DITOLAK' ? 'danger' : 'secondary'))
                                }}">
                                    {{ $detail->arsip->status_pindah ?? 'BELUM' }}
                                </span>
                            </td>

                                <td class="text-center">
    <a href="{{ route('subbagian.arsip.show', $detail->arsip->id) }}"
       class="btn btn-sm btn-outline-info">
        <i class="bi bi-eye"></i>
    </a>

    @if($berita_acara->status == 'DRAFT')
    <form action="{{ route('berita-acara.removeArsip', [$berita_acara->id, $detail->arsip->id]) }}"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('Yakin ingin mengeluarkan arsip dari Berita Acara ini?')">
        @csrf
        @method('DELETE')

        <button type="submit"
                class="btn btn-sm btn-outline-danger">
            <i class="bi bi-x-circle"></i>
        </button>
    </form>
    @endif
</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                    Belum ada arsip
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($berita_acara->details->count() > 0)
            <div class="card-footer bg-white">
                <small class="text-muted">Total {{ $berita_acara->details->count() }} arsip</small>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Kirim -->
<div class="modal fade" id="kirimModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('berita-acara.kirim', $berita_acara->id) }}" 
                  method="POST" 
                  enctype="multipart/form-data"
                  id="formKirimBAP">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Kirim BAP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold d-block">File BAP <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-2">Upload file BAP yang sudah ditandatangani</small>
                        <input type="file" 
                               name="file_bap" 
                               class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png" 
                               required>
                        <small class="text-muted">Format: PDF, JPG, JPEG, PNG (Max 100 MB)</small>
                        <div id="fileInfo" class="mt-2"></div>
                    </div>
                    <div class="alert alert-warning">
                        <small>Setelah dikirim, BAP tidak dapat diedit atau dihapus.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="btnKirimBAP">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 8px;
    border: 1px solid #e9ecef;
}
.card-header {
    border-bottom: 1px solid #e9ecef;
    padding: 12px 20px;
}
.card-body {
    padding: 20px;
}
.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #6c757d;
    border-top: none;
}
.table td {
    vertical-align: middle;
    padding: 10px 12px;
}
.btn-sm {
    padding: 4px 12px;
    font-size: 0.8rem;
}
.badge {
    font-weight: 500;
    padding: 4px 10px;
}
.alert {
    border-radius: 6px;
    padding: 12px 16px;
}
.modal-content {
    border-radius: 8px;
}
.modal-header {
    border-bottom: 1px solid #e9ecef;
    padding: 12px 20px;
}
.modal-body {
    padding: 20px;
}
.modal-footer {
    border-top: 1px solid #e9ecef;
    padding: 12px 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Preview file
    const fileInput = document.querySelector('input[name="file_bap"]');
    const fileInfo = document.getElementById('fileInfo');
    const submitBtn = document.getElementById('btnKirimBAP');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                if (file.size > 10 * 1024 * 1024) {
                    fileInfo.innerHTML = `<div class="text-danger small">File terlalu besar (${sizeMB} MB). Maksimal 100 MB.</div>`;
                    this.value = '';
                    submitBtn.disabled = true;
                    return;
                }
                fileInfo.innerHTML = `<div class="text-success small"><i class="bi bi-check-circle"></i> ${file.name} (${sizeMB} MB)</div>`;
                submitBtn.disabled = false;
            }
        });
    }

    // Konfirmasi kirim
    const form = document.getElementById('formKirimBAP');
    if (form) {
        form.addEventListener('submit', function(e) {
            const file = document.querySelector('input[name="file_bap"]');
            if (!file.files || !file.files.length) {
                e.preventDefault();
                alert('Silakan upload file BAP terlebih dahulu.');
                return;
            }
            if (!confirm('Yakin akan mengirim BAP ini?')) {
                e.preventDefault();
                return;
            }
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';
            submitBtn.disabled = true;
        });
    }

    // Konfirmasi hapus
    document.querySelectorAll('form[data-confirm="delete"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Yakin ingin menghapus BAP ini?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endsection