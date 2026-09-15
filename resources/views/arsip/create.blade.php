@extends('layouts.app')

@section('page-title', 'Tambah Arsip Baru')
@section('page-subtitle', 'Form Tambah Arsip Digital')

@section('content')
@push('styles')
<style>
    .searchable-select-wrapper {
        position: relative;
    }

    .searchable-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 250px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 0.375rem 0.375rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }

    .searchable-select-option {
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .searchable-select-option:hover,
    .searchable-select-option.active {
        background-color: #f0f4ff;
    }

    .searchable-select-option.d-none {
        display: none;
    }

    .searchable-select-empty {
        padding: 0.5rem 0.75rem;
        color: #6c757d;
        font-size: 0.85rem;
        font-style: italic;
    }
</style>
@endpush

@if($legacyDocument)
    <div class="alert alert-info">
        <strong>Registrasi dari SINAR V1.</strong> Data dasar telah diisi dari dokumen historis
        <a href="{{ route('sinar-v1.show', $legacyDocument) }}" class="alert-link">{{ $legacyDocument->nomor_dokumen ?: 'Legacy ID '.$legacyDocument->legacy_id }}</a>.
        Lengkapi klasifikasi, retensi, kondisi, serta lokasi fisik berdasarkan hasil verifikasi aktual.
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Terjadi kesalahan:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">Form Tambah Arsip</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('arsip.store') }}" method="POST" enctype="multipart/form-data" id="arsipForm">
            @csrf
            @if($legacyDocument)
                <input type="hidden" name="sinar_v1_document_id" value="{{ $legacyDocument->id }}">
            @endif
            
            <!-- BAGIAN DATA DASAR (WAJIB) -->
            <h6 class="mb-3 text-primary">Data Dasar Arsip (Wajib)</h6>
            <div class="row">
                <!-- Kode Klasifikasi -->
               <div class="col-md-6 mb-3">
                    <label for="kode_klasifikasi_search" class="form-label">Kode Klasifikasi <span class="text-danger">*</span></label>

                    <div class="searchable-select-wrapper position-relative">
                        <input type="text"
                            id="kode_klasifikasi_search"
                            class="form-control @error('kode_klasifikasi_id') is-invalid @enderror"
                            placeholder="Ketik untuk mencari kode/uraian..."
                            autocomplete="off">

                        <input type="hidden" id="kode_klasifikasi_id" name="kode_klasifikasi_id"
                            value="{{ old('kode_klasifikasi_id') }}" required>

                        <div id="kode_klasifikasi_dropdown" class="searchable-select-dropdown d-none">
                            @foreach($kodeKlasifikasiOptions as $kode)
                                <div class="searchable-select-option"
                                    data-value="{{ $kode->id }}"
                                    data-label="{{ $kode->kode }} - {{ $kode->uraian }}">
                                    {{ $kode->kode }} - {{ $kode->uraian }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @error('kode_klasifikasi_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <!-- Sub Bagian -->
                <div class="col-md-6 mb-3">
                    <label for="sub_bagian_id" class="form-label">Sub Bagian <span class="text-danger">*</span></label>
                    <select class="form-control @error('sub_bagian_id') is-invalid @enderror" 
                            id="sub_bagian_id" name="sub_bagian_id" required>
                        <option value="">Pilih Sub Bagian</option>
                        @foreach($subBagianOptions as $subBagian)
                            <option value="{{ $subBagian->id }}" {{ old('sub_bagian_id', $legacyDocument?->sub_bagian_id) == $subBagian->id ? 'selected' : '' }}>
                                {{ $subBagian->nama_sub_bagian}}
                            </option>
                        @endforeach
                    </select>
                    @error('sub_bagian_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Judul/Uraian Arsip -->
                <div class="col-md-12 mb-3">
                   <label for="uraian_arsip" class="form-label">
    Uraian/Judul Arsip <span class="text-danger">*</span>
    <i class="bi bi-info-circle text-primary"
       data-bs-toggle="tooltip"
       data-bs-html="true"
       title="
       Isi uraian arsip sesuai dengan judul, maksud, atau tujuan dokumen berdasarkan ketentuan naskah dinas KPU.<br><br>
       <b>Minimal 30 karakter.</b><br><br>
       <b>Contoh Benar:</b><br>
       Surat Undangan KPU Provinsi Bali Nomor: 1245/RT.01.2-Und/51/1/2026, perihal Rapat Koordinasi Pengelolaan Aset Tanah melalui Sistem Informasi Manajemen Tanah Pemerintah.<br><br>
       <b>Contoh Salah:</b><br>
       Surat Undangan tentang Rapat Koordinasi.
       ">
    </i>
</label>
                    <input type="text" class="form-control @error('uraian_arsip') is-invalid @enderror" 
                        id="uraian_arsip" name="uraian_arsip" 
                        value="{{ old('uraian_arsip', $legacyDocument ? trim(($legacyDocument->nomor_dokumen ? $legacyDocument->nomor_dokumen.' — ' : '').$legacyDocument->perihal) : null) }}" required>
                    @error('uraian_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                  <div class="col-md-3 mb-3">
                    <label for="tahun_arsip" class="form-label">Tahun Arsip <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('tahun_arsip') is-invalid @enderror"
                        id="tahun_arsip" name="tahun_arsip" value="{{ old('tahun_arsip', $legacyDocument?->tanggal_dokumen?->format('Y') ?? date('Y')) }}"
                        min="2000" max="{{ date('Y') + 1 }}" required>
                    <small class="text-muted" id="tahun-info"></small>
                    @error('tahun_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="tanggal_arsip" class="form-label">Tanggal Arsip <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('tanggal_arsip') is-invalid @enderror"
                        id="tanggal_arsip" name="tanggal_arsip" value="{{ old('tanggal_arsip', $legacyDocument?->tanggal_dokumen?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                    @error('tanggal_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Jumlah Berkas -->
                <div class="col-md-3 mb-3">
                    <label for="jumlah_berkas" class="form-label">Jumlah Berkas <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('jumlah_berkas') is-invalid @enderror" 
                        id="jumlah_berkas" name="jumlah_berkas" 
                        value="{{ old('jumlah_berkas', 1) }}" min="1" required>
                    @error('jumlah_berkas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Satuan Arsip -->
                <div class="col-md-3 mb-3">
                    <label for="satuan_arsip" class="form-label">Satuan Arsip <span class="text-danger">*</span></label>
                    <select class="form-control @error('satuan_arsip') is-invalid @enderror" 
                            id="satuan_arsip" name="satuan_arsip" required>
                        <option value="">Pilih Satuan</option>
                        <option value="BENDEL" {{ old('satuan_arsip', 'LEMBAR') == 'BENDEL' ? 'selected' : '' }}>BENDEL</option>
                        <option value="LEMBAR" {{ old('satuan_arsip', 'LEMBAR') == 'LEMBAR' ? 'selected' : '' }}>LEMBAR</option>
                    </select>
                    @error('satuan_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Kondisi Fisik -->
                <div class="col-md-6 mb-3">
                    <label for="keterangan" class="form-label">Kondisi Fisik <span class="text-danger">*</span></label>
                    <select class="form-control @error('keterangan') is-invalid @enderror" 
                            id="keterangan" name="keterangan">
                        <option value="">Pilih Kondisi</option>
                        <option value="BAIK" {{ old('keterangan') == 'BAIK' ? 'selected' : '' }}>Baik</option>
                        <option value="RUSAK" {{ old('keterangan') == 'RUSAK' ? 'selected' : '' }}>Rusak</option>
                        <option value="HILANG" {{ old('keterangan') == 'HILANG' ? 'selected' : '' }}>Hilang</option>
                    </select>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Media Arsip -->
                <div class="col-md-6 mb-3">
                    <label for="media_arsip" class="form-label">Media Arsip <span class="text-danger">*</span></label>
                    <select name="media_arsip" id="media_arsip" class="form-control">
                        <option value="">Pilih Media</option>
                        <option value="TEKSTUAL" {{ old('media_arsip') == 'TEKSTUAL' ? 'selected' : '' }}>Tekstual</option>
                        <option value="DIGITAL" {{ old('media_arsip') == 'DIGITAL' ? 'selected' : '' }}>Digital</option>
                    </select>
                </div>

                <!-- Klasifikasi Keamanan -->
                <div class="col-md-6 mb-3">
                    <label for="klasifikasi_keamanan" class="form-label">Klasifikasi Keamanan <span class="text-danger">*</span></label>
                    <select class="form-control @error('klasifikasi_keamanan') is-invalid @enderror"
                            id="klasifikasi_keamanan" name="klasifikasi_keamanan" required>
                        <option value="">Pilih Klasifikasi Keamanan</option>
                        <option value="Biasa/Terbuka" {{ old('klasifikasi_keamanan','Biasa/Terbuka') == 'Biasa/Terbuka' ? 'selected' : '' }}>Biasa/Terbuka</option>
                        <option value="Terbatas" {{ old('klasifikasi_keamanan') == 'Terbatas' ? 'selected' : '' }}>Terbatas</option>
                        <option value="Rahasia" {{ old('klasifikasi_keamanan') == 'Rahasia' ? 'selected' : '' }}>Rahasia</option>
                    </select>
                    @error('klasifikasi_keamanan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tingkat Perkembangan -->
                <div class="col-md-6 mb-3">
                    <label for="tingkat_perkembangan" class="form-label">Tingkat Perkembangan <span class="text-danger">*</span></label>
                    <select class="form-control @error('tingkat_perkembangan') is-invalid @enderror" 
                            id="tingkat_perkembangan" name="tingkat_perkembangan">
                        <option value="">Pilih Tingkat</option>
                        <option value="ASLI" {{ old('tingkat_perkembangan') == 'ASLI' ? 'selected' : '' }}>ASLI</option>
                        <option value="COPY" {{ old('tingkat_perkembangan') == 'COPY' ? 'selected' : '' }}>COPY</option>
                        <option value="SALINAN" {{ old('tingkat_perkembangan') == 'SALINAN' ? 'selected' : '' }}>SALINAN</option>
                    </select>
                    @error('tingkat_perkembangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- MASA RETENSI -->
            <h6 class="mb-3 text-primary">Masa Retensi Arsip</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Masa Retensi Aktif</label>
                    <input type="text" id="aktif_tahun" name="aktif_tahun" class="form-control" 
                           placeholder="Contoh: 2 TAHUN / 2 TAHUN SETELAH KEGIATAN" 
                           value="{{ old('aktif_tahun') }}">
                    <small class="text-muted">Gunakan kata <strong>SETELAH</strong> jika berbasis tanggal referensi</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Masa Retensi Inaktif</label>
                    <input type="text" id="inaktif_tahun" name="inaktif_tahun" class="form-control" 
                           placeholder="Contoh: 3 TAHUN / 3 TAHUN SETELAH KEGIATAN" 
                           value="{{ old('inaktif_tahun') }}">
                </div>

                <div class="col-md-4 mb-3" id="tanggal_referensi_wrapper" style="display:none;">
                    <label class="form-label">Tanggal Referensi</label>
                    <input type="date" id="tanggal_referensi" name="tanggal_referensi" class="form-control" 
                           value="{{ old('tanggal_referensi') }}">
                    <small class="text-muted">Diisi jika masa retensi mengandung kata <strong>SETELAH</strong></small>
                </div>
                
                <!-- Keterangan JRA -->
                <div class="col-md-4 mb-3">
                    <label for="keterangan_jra" class="form-label">Keterangan JRA</label>
                    <select class="form-control @error('keterangan_jra') is-invalid @enderror" 
                            id="keterangan_jra" name="keterangan_jra">
                        <option value="">Pilih Keterangan</option>
                        <option value="PERMANEN" {{ old('keterangan_jra') == 'PERMANEN' ? 'selected' : '' }}>Permanen</option>
                        <option value="MUSNAH" {{ old('keterangan_jra') == 'MUSNAH' ? 'selected' : '' }}>Musnah</option>
                    </select>
                    @error('keterangan_jra')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- HASIL PERHITUNGAN RETENSI (HIDDEN) -->
            <input type="hidden" name="aktif_sampai" id="aktif_sampai">
            <input type="hidden" name="inaktif_sampai" id="inaktif_sampai">
            <input type="hidden" name="status_arsip" id="status_arsip_final" value="AKTIF">

            <!-- PREVIEW PERHITUNGAN -->
            <div class="alert alert-info" id="previewRetensi" style="display:none;">
                <h6>Preview Perhitungan Retensi:</h6>
                <p id="previewText"></p>
            </div>

            <hr class="my-4">
            
            <!-- INFORMASI LOKASI & TAMBAHAN -->
            <h6 class="mb-3 text-primary">Informasi Lokasi & Tambahan</h6>
            
            <div class="row">
                <!-- Lokasi Arsip (1 baris penuh) -->
                <div class="col-md-12 mb-3">
                    <label for="lokasi_arsip" class="form-label">Lokasi Arsip</label>
                    <select name="lokasi_arsip" id="lokasi_arsip" class="form-control @error('lokasi_arsip') is-invalid @enderror" required>
                        <option value="">Pilih Lokasi</option>
                        <option value="RECORD_CENTER_PERMANEN" {{ old('lokasi_arsip') == 'RECORD_CENTER_PERMANEN' ? 'selected' : '' }}>
                            Record Center (Arsip Permanen)
                        </option>
                        <option value="RECORD_CENTER_INAKTIF" {{ old('lokasi_arsip') == 'RECORD_CENTER_INAKTIF' ? 'selected' : '' }}>
                            Record Center (Arsip Inaktif)
                        </option>
                    </select>
                    <small class="text-muted">Pilih lokasi penyimpanan arsip</small>
                    @error('lokasi_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Rak, Box, Nomor Sampul (3 kolom) -->
                <div class="col-md-4 mb-3">
                    <label for="rak_id" class="form-label">Rak</label>
                    <select class="form-control @error('rak_id') is-invalid @enderror" id="rak_id" name="rak_id" required>
                        <option value="">Pilih Rak</option>
                        @forelse($rakOptions as $rak)
                            <option value="{{ $rak->id }}" 
                                    data-lokasi="{{ $rak->lokasi_arsip }}"
                                    {{ old('rak_id') == $rak->id ? 'selected' : '' }}>
                                {{ $rak->nomor_rak }}
                            </option>
                        @empty
                            <option value="" disabled>-- Tidak ada rak tersedia --</option>
                        @endforelse
                    </select>
                    <small class="text-muted" id="rak-info">Pilih lokasi terlebih dahulu</small>
                    @error('rak_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="box_id" class="form-label">Box</label>
                    <select class="form-control @error('box_id') is-invalid @enderror" id="box_id" name="box_id" required>
                        <option value="">Pilih Box</option>
                        @forelse($boxOptions as $box)
                            <option value="{{ $box->id }}" 
                                    data-rak-id="{{ $box->rak_id }}"
                                    {{ old('box_id') == $box->id ? 'selected' : '' }}>
                                {{ $box->nomor_box }}
                            </option>
                        @empty
                            <option value="" disabled>-- Tidak ada box tersedia --</option>
                        @endforelse
                    </select>
                    <small class="text-muted" id="box-info">Pilih rak terlebih dahulu</small>
                    @error('box_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="nomor_sampul" class="form-label">Nomor Sampul</label>
                    <input type="text" class="form-control @error('nomor_sampul') is-invalid @enderror" 
                        id="nomor_sampul" name="nomor_sampul" 
                        value="{{ old('nomor_sampul') }}" 
                        placeholder="Contoh: 1">
                    <small class="text-muted">Isi jika ada nomor sampul pada arsip</small>
                    @error('nomor_sampul')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- File Upload & Link Foto (2 kolom) -->
                <div class="col-md-6 mb-3">
                    <label for="file_dokumen" class="form-label">File Dokumen</label>
                    <input type="file" class="form-control @error('file_dokumen') is-invalid @enderror"
                           id="file_dokumen" name="file_dokumen"
                           accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG (Maks: 100MB) - Opsional</small>
                    @error('file_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="link_foto" class="form-label">Link Foto / URL Dokumen</label>
                    <input type="url" class="form-control @error('link_foto') is-invalid @enderror"
                           id="link_foto" name="link_foto"
                           value="{{ old('link_foto') }}"
                           placeholder="https://drive.google.com/file/d/...">
                    <small class="text-muted">Isi jika file disimpan di Google Drive, OneDrive, Dropbox, dll. - Opsional</small>
                    @error('link_foto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <!-- TOMBOL SIMPAN -->
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('arsip.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Arsip
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // =========================
    // 1. RETENSI
    // =========================
    const form = document.getElementById('arsipForm');
    const aktifInput = document.getElementById('aktif_tahun');
    const inaktifInput = document.getElementById('inaktif_tahun');
    const tanggalArsipInput = document.getElementById('tanggal_arsip');
    const tanggalRefInput = document.getElementById('tanggal_referensi');
    const wrapperRef = document.getElementById('tanggal_referensi_wrapper');
    const keteranganJRA = document.getElementById('keterangan_jra');
    const aktifSampaiInput = document.getElementById('aktif_sampai');
    const inaktifSampaiInput = document.getElementById('inaktif_sampai');
    const statusArsipInput = document.getElementById('status_arsip_final');
    const previewRetensi = document.getElementById('previewRetensi');
    const previewText = document.getElementById('previewText');

    function ekstrakAngka(text) {
        const match = text.match(/\d+/);
        return match ? parseInt(match[0]) : null;
    }

    function tambahTahun(tanggalString, tahun) {
        const date = new Date(tanggalString);
        date.setFullYear(date.getFullYear() + tahun);
        return date.toISOString().split('T')[0];
    }

    function formatTanggal(tanggalString) {
        const date = new Date(tanggalString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function cekSetelah() {
        const aktifVal = aktifInput.value.toLowerCase();
        const inaktifVal = inaktifInput.value.toLowerCase();

        if (aktifVal.includes('setelah') || inaktifVal.includes('setelah')) {
            wrapperRef.style.display = 'block';
        } else {
            wrapperRef.style.display = 'none';
            if (tanggalRefInput) {
                tanggalRefInput.value = '';
            }
        }
        hitungRetensi();
    }

    function hitungRetensi() {
        const aktifVal = aktifInput.value.trim().toUpperCase();
        const inaktifVal = inaktifInput.value.trim().toUpperCase();
        const keterangan = keteranganJRA.value;
        const tanggalArsip = tanggalArsipInput.value;
        const tanggalReferensi = tanggalRefInput ? tanggalRefInput.value : '';

        if (!aktifVal || !inaktifVal || !tanggalArsip) {
            previewRetensi.style.display = 'none';
            return;
        }

        if ((aktifVal.includes('SETELAH') || inaktifVal.includes('SETELAH')) && !tanggalReferensi) {
            statusArsipInput.value = 'AKTIF';
            aktifSampaiInput.value = '';
            inaktifSampaiInput.value = '';
            let preview = '<strong>Perhatian:</strong><br>';
            preview += 'Anda menggunakan format "SETELAH" untuk masa retensi, tetapi belum mengisi tanggal referensi.<br>';
            preview += '<strong>Status arsip otomatis di-set ke: AKTIF</strong><br>';
            preview += 'Perhitungan retensi tidak dapat dilakukan tanpa tanggal referensi.<br>';
            preview += 'Silakan isi tanggal referensi untuk perhitungan yang lebih akurat.';
            previewText.innerHTML = preview;
            previewRetensi.style.display = 'block';
            return;
        }

        const aktifTahun = ekstrakAngka(aktifVal);
        const inaktifTahun = ekstrakAngka(inaktifVal);
        if (!aktifTahun || !inaktifTahun) {
            previewText.textContent = 'Format tahun tidak valid. Contoh: "2 TAHUN"';
            previewRetensi.style.display = 'block';
            return;
        }

        let tanggalDasar;
        let sumberTanggal = 'tanggal_arsip';
        if (aktifVal.includes('SETELAH') || inaktifVal.includes('SETELAH')) {
            tanggalDasar = tanggalReferensi;
            sumberTanggal = 'tanggal_referensi';
        } else {
            tanggalDasar = tanggalArsip;
        }

        const aktifSampai = tambahTahun(tanggalDasar, aktifTahun);
        const inaktifSampai = tambahTahun(aktifSampai, inaktifTahun);
        const musnahSampai = tambahTahun(inaktifSampai, 1);

        const sekarang = new Date();
        const aktifDate = new Date(aktifSampai);
        const inaktifDate = new Date(inaktifSampai);
        const musnahDate = new Date(musnahSampai);

        let statusArsip = 'AKTIF';
        let statusText = '';

        if (keterangan === 'PERMANEN') {
            statusArsip = 'PERMANEN';
            statusText = 'Status Arsip saat ini: PERMANEN';
        } else if (keterangan === 'MUSNAH') {
            if (sekarang <= aktifDate) {
                statusArsip = 'AKTIF';
                statusText = 'Status Arsip saat ini: AKTIF';
            } else if (sekarang <= inaktifDate) {
                statusArsip = 'INAKTIF';
                statusText = 'Status Arsip saat ini: INAKTIF';
            } else if (sekarang <= musnahDate) {
                statusArsip = 'HABIS_RETENSI';
                statusText = 'Status Arsip saat ini: HABIS RETENSI';
            } else {
                statusArsip = 'HABIS_RETENSI';
                statusText = 'Status Arsip saat ini: HABIS RETENSI (telah lewat)';
            }
        } else {
            if (sekarang <= aktifDate) {
                statusArsip = 'AKTIF';
                statusText = 'Status Arsip saat ini: AKTIF';
            } else if (sekarang <= inaktifDate) {
                statusArsip = 'INAKTIF';
                statusText = 'Status Arsip saat ini: INAKTIF';
            } else {
                statusArsip = 'INAKTIF';
                statusText = 'Status Arsip saat ini: INAKTIF (telah lewat)';
            }
        }

        aktifSampaiInput.value = aktifSampai;
        inaktifSampaiInput.value = inaktifSampai;
        statusArsipInput.value = statusArsip;

        let preview = '<strong>Perhitungan Retensi:</strong><br>';
        preview += 'Sumber Tanggal: ' + (sumberTanggal === 'tanggal_referensi' ? 'Tanggal Referensi' : 'Tanggal Arsip') + '<br>';
        preview += 'Tanggal Dasar: ' + formatTanggal(tanggalDasar) + '<br>';
        preview += 'Aktif Sampai: ' + formatTanggal(aktifSampai) + '<br>';
        preview += 'Inaktif Sampai: ' + formatTanggal(inaktifSampai) + '<br>';
        if (keterangan === 'MUSNAH') {
            preview += 'Musnah: ' + formatTanggal(musnahSampai) + '<br>';
        }
        preview += '<strong>' + statusText + '</strong>';
        previewText.innerHTML = preview;
        previewRetensi.style.display = 'block';
    }

    // Event listeners retensi
    aktifInput.addEventListener('input', cekSetelah);
    inaktifInput.addEventListener('input', cekSetelah);
    tanggalArsipInput.addEventListener('change', hitungRetensi);
    if (tanggalRefInput) {
        tanggalRefInput.addEventListener('change', hitungRetensi);
    }
    keteranganJRA.addEventListener('change', hitungRetensi);
    cekSetelah();

    // =========================
    // 2. FILTER RAK & BOX
    // =========================
    const lokasiSelect = document.getElementById('lokasi_arsip');
    const rakSelect = document.getElementById('rak_id');
    const boxSelect = document.getElementById('box_id');
    const allRakOptions = Array.from(rakSelect.options);
    const allBoxOptions = Array.from(boxSelect.options);

    function filterRak() {
        const selectedLokasi = lokasiSelect.value;
        rakSelect.innerHTML = '<option value="">Pilih Rak</option>';
        const infoRak = document.getElementById('rak-info');
        
        if (!selectedLokasi) {
            boxSelect.innerHTML = '<option value="">Pilih Box</option>';
            infoRak.textContent = 'Pilih lokasi terlebih dahulu';
            document.getElementById('box-info').textContent = 'Pilih rak terlebih dahulu';
            return;
        }
        
        const filteredRak = allRakOptions.filter(function(opt) {
            if (opt.value === '') return false;
            return opt.getAttribute('data-lokasi') === selectedLokasi;
        });
        
        if (filteredRak.length === 0) {
            infoRak.textContent = 'Tidak ada rak di lokasi ini';
            rakSelect.innerHTML = '<option value="">-- Tidak ada rak --</option>';
        } else {
            infoRak.textContent = filteredRak.length + ' rak tersedia';
            filteredRak.forEach(function(opt) {
                rakSelect.appendChild(opt);
            });
        }
        
        boxSelect.innerHTML = '<option value="">Pilih Box</option>';
        document.getElementById('box-info').textContent = 'Pilih rak terlebih dahulu';
        rakSelect.dispatchEvent(new Event('change'));
    }

    function filterBox() {
        const selectedRakId = rakSelect.value;
        boxSelect.innerHTML = '<option value="">Pilih Box</option>';
        const infoBox = document.getElementById('box-info');
        
        if (!selectedRakId) {
            infoBox.textContent = 'Pilih rak terlebih dahulu';
            return;
        }
        
        const filteredBox = allBoxOptions.filter(function(opt) {
            if (opt.value === '') return false;
            return opt.getAttribute('data-rak-id') === selectedRakId;
        });
        
        if (filteredBox.length === 0) {
            infoBox.textContent = 'Tidak ada box di rak ini';
            boxSelect.innerHTML = '<option value="">-- Tidak ada box --</option>';
        } else {
            infoBox.textContent = filteredBox.length + ' box tersedia';
            filteredBox.forEach(function(opt) {
                boxSelect.appendChild(opt);
            });
        }
    }

    // Event listeners filter
    lokasiSelect.addEventListener('change', filterRak);
    rakSelect.addEventListener('change', filterBox);

    // Inisialisasi jika ada old value
    if (lokasiSelect.value) {
        filterRak();
        if (rakSelect.value) {
            filterBox();
            const oldBoxId = "{{ old('box_id') }}";
            if (oldBoxId) {
                const boxOption = boxSelect.querySelector('option[value="' + oldBoxId + '"]');
                if (boxOption) boxOption.selected = true;
            }
        }
    }

    // =========================
    // 3. AUTO SYNC TAHUN DARI TANGGAL
    // =========================
    tanggalArsipInput.addEventListener('change', function() {
        if (this.value) {
            const tahun = new Date(this.value).getFullYear();
            document.getElementById('tahun_arsip').value = tahun;
        }
    });

    // =========================
    // 4. SUBMIT VALIDATION
    // =========================
     form.addEventListener('submit', function(e) {
    hitungRetensi();

   const fileInput = document.getElementById('file_dokumen');
    if (fileInput && fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        const ext = file.name.split('.').pop().toLowerCase();
        
        if (fileSize > 100) {
            e.preventDefault();
            alert('Ukuran file melebihi 100MB');
            return;
        }

        if (!['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
            e.preventDefault();
            alert('Format file tidak didukung. Gunakan PDF, JPG, JPEG, atau PNG');
            return;
        }
    }

    // ===== CEGAH DOUBLE SUBMIT =====
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        // Kalau sudah pernah disable (klik kedua), batalkan submit
        if (submitBtn.disabled) {
            e.preventDefault();
            return;
        }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan...';
    }
});
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );

    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const tahunInput = document.getElementById('tahun_arsip');
    const tanggalInput = document.getElementById('tanggal_arsip');
    const tahunInfo = document.getElementById('tahun-info');

    function syncTahunFromTanggal() {
        if (!tanggalInput.value) return;
        const tahunTanggal = new Date(tanggalInput.value).getFullYear();
        tahunInput.value = tahunTanggal;
        tahunInfo.textContent = '';
        tahunInput.classList.remove('is-invalid');
    }

    function cekKecocokan() {
        if (!tanggalInput.value || !tahunInput.value) return;
        const tahunTanggal = new Date(tanggalInput.value).getFullYear();

        if (parseInt(tahunInput.value) !== tahunTanggal) {
            tahunInfo.textContent = `Tahun arsip harus sama dengan tahun tanggal arsip (${tahunTanggal})`;
            tahunInfo.classList.add('text-danger');
            tahunInput.classList.add('is-invalid');
        } else {
            tahunInfo.textContent = '';
            tahunInput.classList.remove('is-invalid');
        }
    }

    // Saat tanggal dipilih, otomatis samakan tahun
    tanggalInput.addEventListener('change', syncTahunFromTanggal);

    // Kalau user ubah tahun manual, cek kecocokannya
    tahunInput.addEventListener('input', cekKecocokan);

    // Cegah submit kalau tidak cocok
    document.getElementById('arsipForm').addEventListener('submit', function (e) {
        if (!tanggalInput.value || !tahunInput.value) return;
        const tahunTanggal = new Date(tanggalInput.value).getFullYear();
        if (parseInt(tahunInput.value) !== tahunTanggal) {
            e.preventDefault();
            cekKecocokan();
            tahunInput.focus();
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput   = document.getElementById('kode_klasifikasi_search');
    const hiddenInput   = document.getElementById('kode_klasifikasi_id');
    const dropdown      = document.getElementById('kode_klasifikasi_dropdown');
    const options       = Array.from(dropdown.querySelectorAll('.searchable-select-option'));

    if (!searchInput || !hiddenInput || !dropdown) return;

    // Kalau ada old value (validasi gagal), tampilkan labelnya di input text
    if (hiddenInput.value) {
        const selected = options.find(opt => opt.dataset.value === hiddenInput.value);
        if (selected) {
            searchInput.value = selected.dataset.label;
        }
    }

    function openDropdown() {
        dropdown.classList.remove('d-none');
    }

    function closeDropdown() {
        dropdown.classList.add('d-none');
    }

    function filterOptions() {
        const keyword = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        options.forEach(function (opt) {
            const label = opt.dataset.label.toLowerCase();
            const match = label.includes(keyword);
            opt.classList.toggle('d-none', !match);
            if (match) visibleCount++;
        });

        // Handle pesan "tidak ditemukan"
        let emptyMsg = dropdown.querySelector('.searchable-select-empty');
        if (visibleCount === 0) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.className = 'searchable-select-empty';
                emptyMsg.textContent = 'Kode klasifikasi tidak ditemukan';
                dropdown.appendChild(emptyMsg);
            }
        } else if (emptyMsg) {
            emptyMsg.remove();
        }
    }

    // Buka dropdown & filter saat mengetik
    searchInput.addEventListener('input', function () {
        openDropdown();
        filterOptions();
        // Kalau user ngetik ulang, kosongkan value terpilih sampai pilih lagi
        hiddenInput.value = '';
    });

    // Buka dropdown saat fokus/klik
    searchInput.addEventListener('focus', function () {
        openDropdown();
        filterOptions();
    });

    // Pilih opsi
    dropdown.addEventListener('click', function (e) {
        const target = e.target.closest('.searchable-select-option');
        if (!target) return;

        searchInput.value = target.dataset.label;
        hiddenInput.value = target.dataset.value;
        closeDropdown();
    });

    // Tutup dropdown kalau klik di luar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.searchable-select-wrapper')) {
            closeDropdown();
        }
    });

    // Navigasi keyboard (opsional tapi enak dipakai)
    let activeIndex = -1;

    searchInput.addEventListener('keydown', function (e) {
        const visibleOptions = options.filter(opt => !opt.classList.contains('d-none'));

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, visibleOptions.length - 1);
            updateActive(visibleOptions);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActive(visibleOptions);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && visibleOptions[activeIndex]) {
                visibleOptions[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    function updateActive(visibleOptions) {
        visibleOptions.forEach(opt => opt.classList.remove('active'));
        if (visibleOptions[activeIndex]) {
            visibleOptions[activeIndex].classList.add('active');
            visibleOptions[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    // Validasi sebelum submit: pastikan hidden input terisi
    const form = document.getElementById('arsipForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!hiddenInput.value) {
                e.preventDefault();
                searchInput.classList.add('is-invalid');
                searchInput.focus();

                let feedback = searchInput.parentElement.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = 'Silakan pilih kode klasifikasi dari daftar.';
                    searchInput.parentElement.appendChild(feedback);
                }
            }
        });
    }
});
</script>
@endpush
