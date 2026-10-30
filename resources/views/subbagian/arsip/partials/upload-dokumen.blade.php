@php
    $arsip       = $arsip ?? null;
    $uploadRoute = $uploadRoute ?? 'subbagian.arsip.upload';
    $fileRoute   = $fileRoute ?? 'subbagian.arsip.file.view';
    $maxSizeMb   = \App\Http\Controllers\ChunkUploadController::maxSizeMb();
@endphp

<div class="col-md-6 mb-3">
    <label class="form-label">File Dokumen <span class="text-muted">(Opsional)</span></label>

    @if($arsip && $arsip->file_dokumen)
        <div class="mb-2" id="file-lama-wrapper" @if(old('hapus_file') == '1') style="display:none" @endif>
            <span class="badge bg-info">File saat ini:</span>
            <a href="{{ route($fileRoute, $arsip) }}" target="_blank">
                {{ $arsip->file_dokumen }}
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btn-hapus-file-lama">
                <i class="bi bi-trash"></i> Hapus
            </button>
        </div>
        <input type="hidden" name="hapus_file" id="hapus_file" value="{{ old('hapus_file', 0) }}">
    @endif

    <div id="chunk-upload"
         data-url-status="{{ route($uploadRoute . '.status') }}"
         data-url-chunk="{{ route($uploadRoute . '.chunk') }}"
         data-url-finish="{{ route($uploadRoute . '.finish') }}"
         data-url-cancel="{{ route($uploadRoute . '.cancel') }}"
         data-chunk-size="{{ \App\Http\Controllers\ChunkUploadController::CHUNK_SIZE }}"
         data-max-size="{{ \App\Http\Controllers\ChunkUploadController::MAX_FILE_SIZE }}">

        {{-- Sengaja TANPA atribut name: file tidak ikut terkirim saat form disubmit --}}
        <input type="file" id="file_dokumen_picker"
               class="form-control @error('file_dokumen') is-invalid @enderror {{ old('uploaded_file') ? 'd-none' : '' }}"
               accept=".pdf,.jpg,.jpeg,.png">

        <input type="hidden" name="uploaded_file" id="uploaded_file" value="{{ old('uploaded_file') }}">
        <input type="hidden" name="uploaded_file_name" id="uploaded_file_name" value="{{ old('uploaded_file_name') }}">

        <div id="upload-progress-wrapper" class="mt-2 d-none">
            <div class="progress" style="height: 20px;">
                <div id="upload-progress-bar"
                     class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: 0%">0%</div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted" id="upload-status-text"></small>
                <small class="text-muted" id="upload-speed-text"></small>
            </div>
            <div class="mt-1">
                <button type="button" class="btn btn-sm btn-outline-warning d-none" id="btn-upload-pause">
                    <i class="bi bi-pause-fill"></i> Jeda
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary d-none" id="btn-upload-resume">
                    <i class="bi bi-play-fill"></i> Lanjutkan
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btn-upload-cancel">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
            </div>
        </div>

        <div id="upload-done" class="alert alert-success py-2 mt-2 mb-0 {{ old('uploaded_file') ? '' : 'd-none' }}">
            <i class="bi bi-check-circle"></i> File siap disimpan:
            <strong id="upload-done-name">{{ old('uploaded_file_name') }}</strong>
            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="btn-upload-remove">Ganti file</button>
        </div>
    </div>

    <small class="text-muted d-block mt-1">
        Format: PDF, JPG, JPEG, PNG (Maks: {{ $maxSizeMb }}MB). File diupload bertahap. Bila koneksi terputus,
        pilih file yang sama lagi untuk melanjutkan.
    </small>
    @error('file_dokumen')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>