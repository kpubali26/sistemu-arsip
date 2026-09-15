@extends('layouts.app')

@section('page-title', 'Perbaikan Arsip')
@section('page-subtitle', 'Perbaiki arsip yang ditolak sebelum diajukan kembali')

@section('content')
<div class="container-fluid">

    <div class="mb-3">
        <a href="{{ route('subbagian.riwayat-pemindahan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat Pemindahan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali isian Anda:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">

            <!-- Alasan Penolakan -->
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-times-circle me-2"></i>Alasan Penolakan
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3" style="white-space: pre-line;">
                        {{ $arsip->catatan_verifikasi ?? 'Tidak ada catatan penolakan.' }}
                    </p>

                    <div class="alert alert-secondary mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Jika alasan penolakan adalah karena arsip ini <strong>masih aktif</strong> dan
                        seharusnya tidak perlu dipindahkan, Anda tidak perlu memperbaikinya &mdash;
                        cukup kembalikan arsip ini menjadi arsip internal Sub Bagian.
                    </div>

                    <form action="{{ route('subbagian.riwayat-pemindahan.kembalikan-internal', $arsip->id) }}"
                          method="POST" class="mt-3"
                          onsubmit="return confirm('Yakin ingin mengembalikan arsip ini menjadi arsip internal Sub Bagian? Arsip akan keluar dari daftar berita acara pemindahan.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-undo me-1"></i> Kembalikan ke Arsip Internal Sub Bagian
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form Edit Arsip -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-wrench me-2"></i>Perbaiki Data Arsip
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('subbagian.riwayat-pemindahan.simpan-perbaikan', $arsip->id) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
    @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kode Klasifikasi <span class="text-danger">*</span></label>
                                <select name="kode_klasifikasi_id" class="form-select" required>
                                    <option value="">-- Pilih Kode Klasifikasi --</option>
                                    @foreach($kodeKlasifikasis as $kode)
                                        <option value="{{ $kode->id }}"
                                            {{ old('kode_klasifikasi_id', $arsip->kode_klasifikasi_id) == $kode->id ? 'selected' : '' }}>
                                            {{ $kode->kode }} - {{ Str::limit($kode->uraian, 40) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tahun Arsip <span class="text-danger">*</span></label>
                                <input type="number" name="tahun_arsip" class="form-control"
                                       value="{{ old('tahun_arsip', $arsip->tahun_arsip) }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Uraian Arsip <span class="text-danger">*</span></label>
                            <textarea name="uraian_arsip" class="form-control" rows="3" required>{{ old('uraian_arsip', $arsip->uraian_arsip) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Tanggal Arsip <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_arsip" class="form-control"
                                       value="{{ old('tanggal_arsip', $arsip->tanggal_arsip ? \Carbon\Carbon::parse($arsip->tanggal_arsip)->format('Y-m-d') : '') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Jumlah Berkas <span class="text-danger">*</span></label>
                                <input type="number" name="jumlah_berkas" class="form-control" min="1"
                                       value="{{ old('jumlah_berkas', $arsip->jumlah_berkas) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Satuan Arsip <span class="text-danger">*</span></label>
                                <input type="text" name="satuan_arsip" class="form-control"
                                       value="{{ old('satuan_arsip', $arsip->satuan_arsip) }}" placeholder="cth: Berkas, Bendel" required>
                            </div>
                        </div>

                        <div class="row">
                           <div class="col-md-4 mb-3">
                                <label for="rak_id" class="form-label fw-semibold">Rak</label>
                                <select class="form-select @error('rak_id') is-invalid @enderror" id="rak_id" name="rak_id">
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

                            <div class="col-md-4 mb-3">
                                <label for="box_id" class="form-label fw-semibold">Box</label>
                                <select class="form-select @error('box_id') is-invalid @enderror" id="box_id" name="box_id">
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

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Nomor Sampul</label>
                                <input type="text" name="nomor_sampul" class="form-control"
                                    value="{{ old('nomor_sampul', $arsip->nomor_sampul) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Tingkat Perkembangan <span class="text-danger">*</span></label>
                                <input type="text" name="tingkat_perkembangan" class="form-control"
                                       value="{{ old('tingkat_perkembangan', $arsip->tingkat_perkembangan) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Keterangan <span class="text-danger">*</span></label>
                                <input type="text" name="keterangan" class="form-control"
                                       value="{{ old('keterangan', $arsip->keterangan) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Media Arsip <span class="text-danger">*</span></label>
                                <input type="text" name="media_arsip" class="form-control"
                                       value="{{ old('media_arsip', $arsip->media_arsip) }}" required>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nomor BAP (Berita Acara Pemindahan)</label>
                                <input type="text" name="nomor_bap" class="form-control @error('nomor_bap') is-invalid @enderror"
                                       value="{{ old('nomor_bap', $beritaAcara->nomor_bap ?? '') }}"
                                       placeholder="cth: 001/BAP/2026">
                                <small class="text-muted">
                                    Kosongkan jika nomor BAP tidak perlu diubah. Perhatian: BAP ini bisa
                                    dipakai bersama arsip lain, jadi perubahan nomor akan berlaku untuk
                                    semua arsip dalam BAP yang sama.
                                </small>
                                @error('nomor_bap')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ganti Berkas Berita Acara (opsional)</label>
                                <input type="file" name="file_berita_acara_baru" class="form-control">
                                <small class="text-muted">
                                    Kosongkan jika tidak ingin mengganti berkas berita acara yang sudah ada.
                                    Format: PDF, JPG, JPEG, PNG (Maks: 100MB)
                                </small>
                            </div> --}}
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Dokumen Arsip</label>

                                    @if($arsip->file_dokumen || $arsip->link_folder)
                                        <div class="mb-2">
                                            @if($arsip->file_dokumen)
                                                <a href="{{ asset('storage/arsip/' . $arsip->file_dokumen) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-pdf me-1"></i> Lihat PDF Saat Ini
                                                </a>
                                            @endif
                                            @if($arsip->link_folder)
                                                <a href="{{ $arsip->link_folder }}" target="_blank"
                                                class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-link me-1"></i> Lihat Link Saat Ini
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="btn-group btn-group-sm mb-2" role="group">
                                        <input type="radio" class="btn-check" name="jenis_dokumen" id="jenis_pdf" value="pdf" autocomplete="off"
                                            {{ old('jenis_dokumen', $arsip->file_dokumen ? 'pdf' : ($arsip->link_folder ? 'link' : 'pdf')) == 'pdf' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="jenis_pdf">Upload PDF</label>

                                        <input type="radio" class="btn-check" name="jenis_dokumen" id="jenis_link" value="link" autocomplete="off"
                                            {{ old('jenis_dokumen', $arsip->file_dokumen ? 'pdf' : ($arsip->link_folder ? 'link' : 'pdf')) == 'link' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary" for="jenis_link">Link (Drive, dll)</label>
                                    </div>
                                    <small class="text-danger d-block mt-1">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    Wajib diisi (PDF atau link) jika arsip ini belum pernah memiliki dokumen sebelumnya.
                                </small>
                                    <div id="wrap_upload_pdf">
                                        <input type="file" name="file_dokumen_baru" class="form-control @error('file_dokumen_baru') is-invalid @enderror" accept=".pdf">
                                        <small class="text-muted">Format: PDF saja. Maks 100MB. Kosongkan jika tidak ingin mengganti.</small>
                                        @error('file_dokumen_baru')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div id="wrap_link_dokumen" style="display:none;">
                                        <input type="url" name="link_folder" class="form-control @error('link_folder') is-invalid @enderror"
                                            value="{{ old('link_folder', $arsip->link_folder) }}"
                                            placeholder="https://drive.google.com/...">
                                        <small class="text-muted">Masukkan link folder/dokumen (mis. Google Drive).</small>
                                        @error('link_folder')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Ganti Berkas Berita Acara (opsional)</label>
                                    <input type="file" name="file_berita_acara_baru" class="form-control">
                                    <small class="text-muted">
                                        Kosongkan jika tidak ingin mengganti berkas berita acara yang sudah ada.
                                        Format: PDF, JPG, JPEG, PNG (Maks: 100MB)
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan Perbaikan <span class="text-danger">*</span></label>
                            <textarea name="catatan_perbaikan" class="form-control" rows="3" required
                                      placeholder="Jelaskan perbaikan apa saja yang sudah dilakukan...">{{ old('catatan_perbaikan', $arsip->catatan_perbaikan) }}</textarea>
                            <small class="text-muted">Catatan ini akan dilihat oleh Unit Kearsipan saat memverifikasi ulang.</small>
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Simpan Perbaikan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Catatan Perbaikan Tersimpan + Aksi Lanjutan -->
            @if($arsip->status_pindah == 'DIPERBAIKI')
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>Perbaikan Tersimpan
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="fw-semibold mb-1">Catatan Perbaikan:</p>
                        <p class="mb-3" style="white-space: pre-line;">{{ $arsip->catatan_perbaikan }}</p>

                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Jika perbaikan sudah selesai dan Anda yakin, klik tombol di bawah untuk
                            mengajukan kembali arsip ini ke Unit Kearsipan. <strong>Tidak perlu</strong>
                            mengunggah ulang berkas berita acara.
                        </div>

                        <form action="{{ route('subbagian.riwayat-pemindahan.ajukan-kembali', $arsip->id) }}"
                              method="POST"
                              onsubmit="return confirm('Yakin ingin mengajukan kembali arsip ini untuk diverifikasi?');">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-redo me-1"></i> Ajukan Kembali
                            </button>
                        </form>
                    </div>
                </div>
            @endif

        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Info Arsip</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th width="40%">Kode</th>
                            <td>: {{ $arsip->kodeKlasifikasi->kode ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Sub Bagian</th>
                            <td>: {{ $arsip->subBagian->nama_sub_bagian ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Dokumen Arsip</th>
                            <td>:
                                @if($arsip->file_dokumen)
                                    <a href="{{ asset('storage/arsip/' . $arsip->file_dokumen) }}" target="_blank">
                                        <i class="fas fa-file-pdf me-1"></i>Lihat PDF
                                    </a>
                                @elseif($arsip->link_folder)
                                    <a href="{{ $arsip->link_folder }}" target="_blank">
                                        <i class="fas fa-link me-1"></i>Buka Link
                                    </a>
                                @else
                                    <span class="text-muted">Belum ada dokumen</span>
                                @endif
                            </td>
                        </tr>
                       <tr>
                            <th>Nomor BAP</th>
                            <td>: {{ $beritaAcara->nomor_bap ?? '-' }}</td>
                        </tr>
                       <tr>
    <th>File Berita Acara</th>
    <td>:
        @if($arsip->file_berita_acara)
            <a href="{{ asset('storage/berita_acara/' . $arsip->file_berita_acara) }}" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Lihat Berita Acara
            </a>
        @else
            <span class="text-muted">Belum ada</span>
        @endif
    </td>
</tr>
                        <tr>
                            <th>Status Saat Ini</th>
                            <td>:
                                @if($arsip->status_pindah == 'DITOLAK')
                                    <span class="badge bg-danger">DITOLAK</span>
                                @elseif($arsip->status_pindah == 'DIPERBAIKI')
                                    <span class="badge bg-info text-dark">DIPERBAIKI</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Terakhir Diubah</th>
                            <td>: {{ $arsip->updated_at ? $arsip->updated_at->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rakSelect = document.getElementById('rak_id');
    const boxSelect = document.getElementById('box_id');

    if (!rakSelect || !boxSelect) return;

    const allRakOptions = Array.from(rakSelect.querySelectorAll('option:not([value=""])'));
    const allBoxOptions = Array.from(boxSelect.querySelectorAll('option:not([value=""])'));

    const selectedLokasi = "{{ $lokasi }}";
    const selectedRakId = "{{ old('rak_id', $arsip->rak_id) }}";
    const selectedBoxId = "{{ old('box_id', $arsip->box_id) }}";

    function filterRak() {
        rakSelect.innerHTML = '<option value="">Pilih Rak</option>';
        const infoRak = document.getElementById('rak-info');

        if (!selectedLokasi) {
            infoRak.textContent = 'Lokasi tidak ditemukan';
            return;
        }

        const filteredRak = allRakOptions.filter(opt => opt.getAttribute('data-lokasi') === selectedLokasi);

        if (filteredRak.length === 0) {
            infoRak.textContent = 'Tidak ada rak di lokasi ini';
            rakSelect.innerHTML = '<option value="">-- Tidak ada rak --</option>';
        } else {
            infoRak.textContent = filteredRak.length + ' rak tersedia';
            filteredRak.forEach(opt => rakSelect.appendChild(opt));

            if (selectedRakId) {
                const rakOption = rakSelect.querySelector('option[value="' + selectedRakId + '"]');
                if (rakOption) rakOption.selected = true;
            }
        }

        rakSelect.dispatchEvent(new Event('change'));
    }

    function filterBox() {
        const currentRakId = rakSelect.value;
        boxSelect.innerHTML = '<option value="">Pilih Box</option>';
        const infoBox = document.getElementById('box-info');

        if (!currentRakId) {
            infoBox.textContent = 'Pilih rak terlebih dahulu';
            return;
        }

        const filteredBox = allBoxOptions.filter(opt => opt.getAttribute('data-rak-id') === currentRakId);

        if (filteredBox.length === 0) {
            infoBox.textContent = 'Tidak ada box di rak ini';
            boxSelect.innerHTML = '<option value="">-- Tidak ada box --</option>';
        } else {
            infoBox.textContent = filteredBox.length + ' box tersedia';
            filteredBox.forEach(opt => boxSelect.appendChild(opt));

            if (selectedBoxId) {
                const boxOption = boxSelect.querySelector('option[value="' + selectedBoxId + '"]');
                if (boxOption) boxOption.selected = true;
            }
        }
    }

    filterRak();
    rakSelect.addEventListener('change', filterBox);

    
});
const jenisRadios = document.querySelectorAll('input[name="jenis_dokumen"]');
const wrapPdf = document.getElementById('wrap_upload_pdf');
const wrapLink = document.getElementById('wrap_link_dokumen');

function toggleJenisDokumen() {
    const selected = document.querySelector('input[name="jenis_dokumen"]:checked');
    if (!selected) return;
    if (selected.value === 'pdf') {
        wrapPdf.style.display = '';
        wrapLink.style.display = 'none';
    } else {
        wrapPdf.style.display = 'none';
        wrapLink.style.display = '';
    }
}

jenisRadios.forEach(r => r.addEventListener('change', toggleJenisDokumen));
toggleJenisDokumen();
</script>
@endsection