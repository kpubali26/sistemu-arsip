@extends('layouts.app')

@section('page-title', 'Edit Arsip - Sub Bagian')
@section('page-subtitle', 'Form Edit Arsip Digital')

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
<div class="card shadow">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold text-primary">Form Edit Arsip</h6>
    </div>
    <div class="card-body">
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('subbagian.arsip.update', $arsip->id) }}" method="POST" enctype="multipart/form-data" id="arsipForm">
            @csrf
            @method('PUT')

            <!-- DATA DASAR -->
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
                            value="{{ old('kode_klasifikasi_id', $arsip->kode_klasifikasi_id) }}" required>

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

                <!-- Sub Bagian (readonly) -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Sub Bagian <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="{{ Auth::user()->subBagian->nama_sub_bagian ?? '-' }}" readonly disabled>
                    <input type="hidden" name="sub_bagian_id" value="{{ Auth::user()->sub_bagian_id }}">
                </div>

                <!-- Uraian Arsip -->
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
                    <input type="text" class="form-control @error('uraian_arsip') is-invalid @enderror" name="uraian_arsip" value="{{ old('uraian_arsip', $arsip->uraian_arsip) }}" required>
                    @error('uraian_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tahun & Tanggal -->
                <div class="col-md-3 mb-3">
                    <label for="tahun_arsip" class="form-label">Tahun Arsip <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('tahun_arsip') is-invalid @enderror"
                        id="tahun_arsip" name="tahun_arsip" value="{{ old('tahun_arsip', $arsip->tahun_arsip) }}"
                        min="2000" max="{{ date('Y') + 1 }}" required>
                    <small class="text-muted" id="tahun-info"></small>
                    @error('tahun_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="tanggal_arsip" class="form-label">Tanggal Arsip <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('tanggal_arsip') is-invalid @enderror"
                        id="tanggal_arsip" name="tanggal_arsip" value="{{ old('tanggal_arsip', $arsip->tanggal_arsip_for_input) }}" required>
                    @error('tanggal_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Jumlah Berkas & Satuan -->
                <div class="col-md-3 mb-3">
                    <label for="jumlah_berkas" class="form-label">Jumlah Berkas <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('jumlah_berkas') is-invalid @enderror" name="jumlah_berkas" value="{{ old('jumlah_berkas', $arsip->jumlah_berkas) }}" min="1" required>
                    @error('jumlah_berkas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="satuan_arsip" class="form-label">Satuan Arsip <span class="text-danger">*</span></label>
                    <select class="form-control @error('satuan_arsip') is-invalid @enderror" name="satuan_arsip" required>
                        <option value="">Pilih Satuan</option>
                        <option value="BENDEL" {{ old('satuan_arsip', $arsip->satuan_arsip) == 'BENDEL' ? 'selected' : '' }}>BENDEL</option>
                        <option value="LEMBAR" {{ old('satuan_arsip', $arsip->satuan_arsip) == 'LEMBAR' ? 'selected' : '' }}>LEMBAR</option>
                    </select>
                    @error('satuan_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Kondisi Fisik -->
                <div class="col-md-6 mb-3">
                    <label for="keterangan" class="form-label">Kondisi Fisik <span class="text-danger">*</span></label>
                    <select class="form-control @error('keterangan') is-invalid @enderror" name="keterangan">
                        <option value="">Pilih Kondisi</option>
                        <option value="BAIK" {{ old('keterangan', $arsip->keterangan) == 'BAIK' ? 'selected' : '' }}>Baik</option>
                        <option value="RUSAK" {{ old('keterangan', $arsip->keterangan) == 'RUSAK' ? 'selected' : '' }}>Rusak</option>
                        <option value="HILANG" {{ old('keterangan', $arsip->keterangan) == 'HILANG' ? 'selected' : '' }}>Hilang</option>
                    </select>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Media Arsip -->
                <div class="col-md-6 mb-3">
                    <label for="media_arsip" class="form-label">Media Arsip <span class="text-danger">*</span></label>
                    <select class="form-control @error('media_arsip') is-invalid @enderror" name="media_arsip">
                        <option value="">Pilih Media</option>
                        <option value="TEKSTUAL" {{ old('media_arsip', $arsip->media_arsip) == 'TEKSTUAL' ? 'selected' : '' }}>Tekstual</option>
                        <option value="DIGITAL" {{ old('media_arsip', $arsip->media_arsip) == 'DIGITAL' ? 'selected' : '' }}>Digital</option>
                    </select>
                    @error('media_arsip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Klasifikasi Keamanan -->
                <div class="col-md-6 mb-3">
                    <label for="klasifikasi_keamanan" class="form-label">Klasifikasi Keamanan <span class="text-danger">*</span></label>
                    <select class="form-control @error('klasifikasi_keamanan') is-invalid @enderror" name="klasifikasi_keamanan" required>
                        <option value="">Pilih</option>
                        <option value="Biasa/Terbuka" {{ old('klasifikasi_keamanan', $arsip->klasifikasi_keamanan) == 'Biasa/Terbuka' ? 'selected' : '' }}>Biasa/Terbuka</option>
                        <option value="Terbatas" {{ old('klasifikasi_keamanan', $arsip->klasifikasi_keamanan) == 'Terbatas' ? 'selected' : '' }}>Terbatas</option>
                        <option value="Rahasia" {{ old('klasifikasi_keamanan', $arsip->klasifikasi_keamanan) == 'Rahasia' ? 'selected' : '' }}>Rahasia</option>
                    </select>
                    @error('klasifikasi_keamanan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tingkat Perkembangan -->
                <div class="col-md-6 mb-3">
                    <label for="tingkat_perkembangan" class="form-label">Tingkat Perkembangan <span class="text-danger">*</span></label>
                    <select class="form-control @error('tingkat_perkembangan') is-invalid @enderror" name="tingkat_perkembangan">
                        <option value="">Pilih Tingkat</option>
                        <option value="ASLI" {{ old('tingkat_perkembangan', $arsip->tingkat_perkembangan) == 'ASLI' ? 'selected' : '' }}>ASLI</option>
                        <option value="COPY" {{ old('tingkat_perkembangan', $arsip->tingkat_perkembangan) == 'COPY' ? 'selected' : '' }}>COPY</option>
                        <option value="SALINAN" {{ old('tingkat_perkembangan', $arsip->tingkat_perkembangan) == 'SALINAN' ? 'selected' : '' }}>SALINAN</option>
                    </select>
                    @error('tingkat_perkembangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <hr>

            <!-- INFORMASI TAMBAHAN (Opsional) -->
            <h6 class="mb-3 text-primary">Informasi Tambahan (Opsional)</h6>
            <div class="row">
                <!-- Lokasi Arsip -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Lokasi Arsip</label>
                    <input type="text" class="form-control" value="{{ $lokasiLabel ?? 'Sub Bagian' }}" readonly disabled>
                    <small class="text-muted">Lokasi ditentukan berdasarkan sub bagian Anda</small>
                    <input type="hidden" name="lokasi_arsip" value="{{ $lokasi }}">
                </div>

                <!-- Rak -->
                <div class="col-md-4 mb-3">
                    <label for="rak_id" class="form-label">Rak</label>
                    <select class="form-control @error('rak_id') is-invalid @enderror" id="rak_id" name="rak_id" required>
                        <option value="">Pilih Rak</option>
                        @forelse($rakOptions as $rak)
                            <option value="{{ $rak->id }}" 
                                    data-lokasi="{{ $rak->lokasi_arsip }}"
                                    {{ old('rak_id', $arsip->rak_id) == $rak->id ? 'selected' : '' }}>
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

                <!-- Box -->
                <div class="col-md-4 mb-3">
                    <label for="box_id" class="form-label">Box</label>
                    <select class="form-control @error('box_id') is-invalid @enderror" id="box_id" name="box_id" required>
                        <option value="">Pilih Box</option>
                        @forelse($boxOptions as $box)
                            <option value="{{ $box->id }}" 
                                    data-rak-id="{{ $box->rak_id }}"
                                    {{ old('box_id', $arsip->box_id) == $box->id ? 'selected' : '' }}>
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

                <!-- Nomor Sampul -->
                <div class="col-md-4 mb-3">
                  <label for="nomor_sampul" class="form-label">Nomor Sampul <span class="text-muted">(Opsional)</span></label>
                    <input type="text" class="form-control @error('nomor_sampul') is-invalid @enderror" 
                        name="nomor_sampul" value="{{ old('nomor_sampul', $arsip->nomor_sampul) }}" placeholder="Contoh: 1">
                    <small class="text-muted">Isi jika ada nomor sampul</small>
                        @error('nomor_sampul')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tanggal Masuk -->
                <!-- {{-- <div class="col-md-6 mb-3">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control @error('tanggal_masuk') is-invalid @enderror" 
                        name="tanggal_masuk" value="{{ old('tanggal_masuk', $arsip->tanggal_masuk_for_input) }}">
                    <small class="text-muted">Tanggal arsip dimasukkan ke sistem</small>
                    @error('tanggal_masuk')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> --}} -->
            </div>

            <!-- File Upload & Link -->
            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <label for="file_dokumen" class="form-label">File Dokumen</label>
                    @if($arsip->file_dokumen)
                        <div class="mb-2">
                            <span class="badge bg-info">File saat ini:</span>
                            <a href="{{ Storage::url('arsip/' . $arsip->file_dokumen) }}" target="_blank">
                                {{ $arsip->file_dokumen }}
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusFile()">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                            <input type="hidden" name="hapus_file" id="hapus_file" value="0">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('file_dokumen') is-invalid @enderror" 
                        name="file_dokumen" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG (Maks: 100MB)</small>
                    @error('file_dokumen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="link_foto" class="form-label">Link Foto / URL Dokumen</label>
                    <input type="url" class="form-control @error('link_foto') is-invalid @enderror" 
                        name="link_foto" value="{{ old('link_foto', $arsip->link_foto) }}" placeholder="https://drive.google.com/file/d/...">
                    @if($arsip->link_foto)
                        <small class="d-block mt-2">
                            Link saat ini: <a href="{{ $arsip->link_foto }}" target="_blank">Lihat Foto</a>
                        </small>
                    @endif
                    @error('link_foto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- Penanganan Duplikat -->
            @if($arsip->is_duplicate == 1)
            <div class="card border-danger mt-3">
                <div class="card-header bg-danger text-white">
                    <strong>Penanganan Arsip Duplikat</strong>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="tangani_duplikat" name="tangani_duplikat" value="1">
                                <label class="form-check-label fw-bold">
                                    Ubah status menjadi <span class="text-danger">NON ARSIP</span>
                                </label>
                                <small class="d-block text-muted">Gunakan jika arsip ini terbukti duplikat dan tidak digunakan</small>
                            </div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Alasan Penanganan</label>
                            <textarea name="duplicate_reason" class="form-control" rows="3" placeholder="Tuliskan alasan kenapa arsip ini dijadikan non arsip...">{{ old('duplicate_reason') }}</textarea>
                            <small class="text-muted">Wajib diisi jika status diubah menjadi Non Arsip</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Tombol -->
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('subbagian.arsip.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Arsip
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rakSelect = document.getElementById('rak_id');
    const boxSelect = document.getElementById('box_id');

    // Simpan semua opsi asli
    const allRakOptions = Array.from(rakSelect.querySelectorAll('option:not([value=""])'));
    const allBoxOptions = Array.from(boxSelect.querySelectorAll('option:not([value=""])'));

    // Ambil nilai yang tersimpan
    const selectedLokasi = "{{ $lokasi }}";
    const selectedRakId = "{{ old('rak_id', $arsip->rak_id) }}";
    const selectedBoxId = "{{ old('box_id', $arsip->box_id) }}";

    function filterRak() {
        // Reset rak
        rakSelect.innerHTML = '<option value="">Pilih Rak</option>';
        const infoRak = document.getElementById('rak-info');
        
        if (!selectedLokasi) {
            infoRak.textContent = 'Lokasi tidak ditemukan';
            return;
        }

        // Filter rak berdasarkan lokasi
        const filteredRak = allRakOptions.filter(opt => {
            return opt.getAttribute('data-lokasi') === selectedLokasi;
        });

        if (filteredRak.length === 0) {
            infoRak.textContent = 'Tidak ada rak di lokasi ini';
            rakSelect.innerHTML = '<option value="">-- Tidak ada rak --</option>';
        } else {
            infoRak.textContent = filteredRak.length + ' rak tersedia';
            filteredRak.forEach(opt => rakSelect.appendChild(opt));
            
            // Set selected rak jika ada
            if (selectedRakId) {
                const rakOption = rakSelect.querySelector('option[value="' + selectedRakId + '"]');
                if (rakOption) {
                    rakOption.selected = true;
                }
            }
        }

        // Trigger change untuk filter box
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

        // Filter box berdasarkan rak_id
        const filteredBox = allBoxOptions.filter(opt => {
            return opt.getAttribute('data-rak-id') === selectedRakId;
        });

        if (filteredBox.length === 0) {
            infoBox.textContent = 'Tidak ada box di rak ini';
            boxSelect.innerHTML = '<option value="">-- Tidak ada box --</option>';
        } else {
            infoBox.textContent = filteredBox.length + ' box tersedia';
            filteredBox.forEach(opt => boxSelect.appendChild(opt));
            
            // Set selected box jika ada
            if (selectedBoxId) {
                const boxOption = boxSelect.querySelector('option[value="' + selectedBoxId + '"]');
                if (boxOption) {
                    boxOption.selected = true;
                }
            }
        }
    }

    // Jalankan filter
    filterRak();

    // Event listener
    rakSelect.addEventListener('change', filterBox);

    // Fungsi hapus file
    window.hapusFile = function() {
        if (confirm('Apakah Anda yakin ingin menghapus file ini?')) {
            document.getElementById('hapus_file').value = '1';
            const fileInfoDiv = document.querySelector('.mb-2');
            if (fileInfoDiv) {
                fileInfoDiv.style.display = 'none';
            }
        }
    }
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
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tahunInput = document.getElementById('tahun_arsip');
    const tanggalInput = document.getElementById('tanggal_arsip');
    const tahunInfo = document.getElementById('tahun-info');
    const form = document.getElementById('arsipForm');

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

    // Kalau tanggal diubah, samakan tahun otomatis
    tanggalInput.addEventListener('change', syncTahunFromTanggal);

    // Kalau tahun diubah manual, cek kecocokan
    tahunInput.addEventListener('input', cekKecocokan);

    // Cek saat halaman pertama load (data lama dari database)
    cekKecocokan();

    // Cegah submit kalau tidak cocok
    form.addEventListener('submit', function (e) {
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

    // Isi otomatis label sesuai kode klasifikasi arsip yang sedang diedit (atau old value)
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

    searchInput.addEventListener('input', function () {
        openDropdown();
        filterOptions();
        hiddenInput.value = '';
    });

    searchInput.addEventListener('focus', function () {
        openDropdown();
        filterOptions();
    });

    dropdown.addEventListener('click', function (e) {
        const target = e.target.closest('.searchable-select-option');
        if (!target) return;

        searchInput.value = target.dataset.label;
        hiddenInput.value = target.dataset.value;
        closeDropdown();
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.searchable-select-wrapper')) {
            closeDropdown();
        }
    });

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