{{-- resources/views/berita-acara/index.blade.php --}}

@extends('layouts.app')

@section('page-title', 'Kelola Berita Acara Pemindahan')
@section('page-subtitle', 'Manajemen Berita Acara Pemindahan Arsip')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-2 mb-md-0 fw-bold text-primary">
                <i class="bi bi-file-text-fill me-2"></i> Berita Acara Pemindahan
            </h4>
            <div class="action-buttons d-flex gap-2">
                <a href="{{ route('berita-acara.create') }}" class="btn btn-orange d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Tambah BAP</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter & Search -->
        <div class="mb-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Cari Nomor BAP</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Cari nomor BAP..." 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Draft</option>
                        <option value="DIAJUKAN" {{ request('status') == 'DIAJUKAN' ? 'selected' : '' }}>Diajukan</option>
                        <option value="DITERIMA" {{ request('status') == 'DITERIMA' ? 'selected' : '' }}>Diterima</option>
                        <option value="DITOLAK" {{ request('status') == 'DITOLAK' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <a href="{{ route('berita-acara.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Nomor BAP</th>
                        <th style="width: 120px;">Tanggal</th>
                        <th style="width: 100px; text-align: center;">Jumlah Arsip</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 150px;">Tanggal Kirim</th>
                        <th style="width: 150px;">Tanggal Diterima</th>
                        <th style="width: 250px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($baps as $bap)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $bap->nomor_bap }}</strong>
                            @if(request('search'))
                                <span class="badge bg-info ms-1">Pencarian</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($bap->tanggal_bap)->format('d/m/Y') }}</td>
                        <td class="text-center"><span class="badge bg-primary">{{ $bap->details->count() }}</span></td>
                        <td>
                            @php
                                $statusColors = [
                                    'DRAFT' => 'secondary',
                                    'DIAJUKAN' => 'warning',
                                    'DITERIMA' => 'success',
                                    'DITOLAK' => 'danger'
                                ];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$bap->status] ?? 'secondary' }}">
                                {{ $bap->status }}
                            </span>
                        </td>
                        <td>{{ $bap->tanggal_kirim ? \Carbon\Carbon::parse($bap->tanggal_kirim)->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $bap->tanggal_diterima ? \Carbon\Carbon::parse($bap->tanggal_diterima)->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                <!-- Tombol Detail -->
                                <a href="{{ route('berita-acara.show', $bap->id) }}" 
                                   class="btn btn-sm btn-info" 
                                   title="Detail BAP">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @if($bap->canEdit())
                                    <!-- Tombol Edit -->
                                    <a href="{{ route('berita-acara.edit', $bap->id) }}" 
                                       class="btn btn-sm btn-warning" 
                                       title="Edit BAP">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <!-- Tombol Hapus -->
                                    <form action="{{ route('berita-acara.destroy', $bap->id) }}" 
                                          method="POST" 
                                          data-confirm="delete"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus BAP">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($bap->canSend())
                                    <!-- Tombol Kirim -->
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            title="Kirim ke Unit Kearsipan"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#kirimModal{{ $bap->id }}">
                                        <i class="bi bi-send"></i>
                                    </button>
                                @endif

                                @if($bap->status == 'DIAJUKAN' || $bap->status == 'DITERIMA')
                                    <!-- Tombol Export Lampiran -->
                                    <a href="{{ route('berita-acara.exportLampiran', $bap->id) }}" 
                                       class="btn btn-sm btn-excel" 
                                       title="Export Lampiran BAP ke Excel">
                                        <i class="bi bi-file-earmark-excel"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-file-text fa-2x text-muted mb-2 d-block"></i>
                            @if(request('search') || request('status'))
                                <p class="text-muted">Tidak ada data yang sesuai dengan pencarian</p>
                                <a href="{{ route('berita-acara.index') }}" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise"></i> Reset Filter
                                </a>
                            @else
                                <p class="text-muted">Belum ada Berita Acara Pemindahan</p>
                                <a href="{{ route('berita-acara.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Buat BAP Pertama
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div class="text-muted">
                Menampilkan {{ $baps->firstItem() }} - {{ $baps->lastItem() }} dari {{ $baps->total() }} BAP
                @if(request('search'))
                    <span class="badge bg-info ms-2">Pencarian: "{{ request('search') }}"</span>
                @endif
                @if(request('status'))
                    <span class="badge bg-secondary ms-1">Status: {{ request('status') }}</span>
                @endif
            </div>
            <nav aria-label="Page navigation">
                {{ $baps->withQueryString()->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    </div>
</div>

<!-- MODALS - DILUAR CARD DAN TABLE -->
@foreach($baps as $bap)
    @if($bap->canSend())
    <!-- Modal Kirim -->
    <div class="modal fade" id="kirimModal{{ $bap->id }}" tabindex="-1" aria-labelledby="kirimModalLabel{{ $bap->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
               <form action="{{ route('berita-acara.kirim', $bap->id) }}" 
      method="POST" 
      enctype="multipart/form-data"
      id="formKirimBAP{{ $bap->id }}">
    @csrf
            
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="kirimModalLabel{{ $bap->id }}">
                            <i class="bi bi-send me-2"></i>
                            Kirim Berita Acara
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>BAP:</strong> {{ $bap->nomor_bap }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Jumlah Arsip:</strong> {{ $bap->details->count() }} arsip
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Upload File BAP yang sudah ditandatangani
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" 
                                   name="file_bap" 
                                   class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png" 
                                   required>
                            <small class="text-muted">
                                Format: PDF, JPG, JPEG, PNG (Max 100 MB)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send me-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

<style>
    .btn-orange {
        background-color: #fd7e14;
        border-color: #fd7e14;
        color: white;
    }
    .btn-orange:hover {
        background-color: #e66a00;
        border-color: #e66a00;
        color: white;
    }
    
    /* Tombol Export Lampiran - warna hijau tua khas Excel */
    .btn-excel {
        background-color: #1e7e34;
        border-color: #1e7e34;
        color: white;
    }
    .btn-excel:hover {
        background-color: #166b2a;
        border-color: #166b2a;
        color: white;
    }
    
    /* Perbaikan tampilan badge */
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    
    /* Perbaikan tombol aksi */
    .btn-sm {
        padding: 0.3rem 0.6rem;
        font-size: 0.75rem;
        border-radius: 4px;
        min-width: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-sm i {
        font-size: 0.9rem;
    }
    
    /* Spasi antar tombol */
    .gap-1 {
        gap: 4px;
    }
    
    /* Perbaikan tabel */
    .table th, .table td {
        vertical-align: middle;
        padding: 0.6rem 0.5rem;
    }
    
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    
    /* Perbaikan modal */
    .modal-content {
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    
    /* Tooltip style */
    [title] {
        position: relative;
    }
    
    /* Input group search */
    .input-group-text {
        border-right: none;
    }
    .input-group .form-control {
        border-left: none;
    }
    .input-group .form-control:focus {
        box-shadow: none;
        border-color: #dee2e6;
    }
    .input-group .form-control:focus + .input-group-text {
        border-color: #dee2e6;
    }
    
    /* Responsive untuk tablet */
    @media (max-width: 992px) {
        .table th, .table td {
            padding: 0.4rem 0.3rem;
            font-size: 0.8rem;
        }
        .btn-sm {
            padding: 0.2rem 0.4rem;
            font-size: 0.7rem;
            min-width: 26px;
        }
        .btn-sm i {
            font-size: 0.8rem;
        }
    }
    
    /* Responsive untuk mobile */
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            min-width: 750px;
        }
        .d-flex.gap-1 {
            gap: 2px !important;
        }
        .btn-sm {
            padding: 0.15rem 0.35rem;
            font-size: 0.65rem;
            min-width: 24px;
        }
        .btn-sm i {
            font-size: 0.7rem;
        }
        .row.g-2 > .col-md-4,
        .row.g-2 > .col-md-3,
        .row.g-2 > .col-md-2 {
            margin-bottom: 8px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Konfirmasi hapus
    const deleteForms = document.querySelectorAll('form[data-confirm="delete"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus Berita Acara ini?')) {
                e.preventDefault();
            }
        });
    });
    
    // Preview file di modal
    document.querySelectorAll('input[name="file_bap"]').forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const parent = this.closest('.modal-body');
            let info = parent.querySelector('.file-info');
            
            // Remove existing info
            if (info) {
                info.remove();
            }
            
            if (file) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
                const maxSize = 100 * 1024 * 1024;
                
                if (file.size > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 100MB.');
                    this.value = '';
                    return;
                }
                
                if (!validTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan PDF, JPG, atau PNG.');
                    this.value = '';
                    return;
                }
                
                // Tampilkan info file
                info = document.createElement('div');
                info.className = 'alert alert-success file-info mt-2 d-flex align-items-center';
                info.innerHTML = `
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div>
                        <strong>File siap diupload:</strong> ${file.name}
                        <span class="ms-2 text-muted">(${sizeMB} MB)</span>
                    </div>
                `;
                parent.querySelector('.mb-3').appendChild(info);
            }
        });
    });
    
    // Auto close modal after submit
    document.querySelectorAll('form[action*="kirim"]').forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Mengirim...
            `;
            btn.disabled = true;
        });
    });
});
</script>
@endsection