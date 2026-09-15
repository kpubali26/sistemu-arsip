@extends('layouts.app')

@section('page-title', 'Tambah Berita Acara')
@section('page-subtitle', 'Input Data Berita Acara Pemindahan')

@section('content')
<div class="card">
    <div class="card-body">

        {{-- Alert Success --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Alert Error --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Terjadi kesalahan!</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

       <form action="{{ route('berita-acara.store') }}" method="POST" enctype="multipart/form-data" id="formBeritaAcara">
    @csrf

            <div class="mb-3">
                <label class="form-label">Nomor BAP</label>
                <input type="text"
                       name="nomor_bap"
                       class="form-control @error('nomor_bap') is-invalid @enderror"
                       value="{{ old('nomor_bap') }}"
                       required>

                @error('nomor_bap')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal BAP</label>
                <input type="date"
                       name="tanggal_bap"
                       class="form-control @error('tanggal_bap') is-invalid @enderror"
                       value="{{ old('tanggal_bap') }}"
                       required>

                @error('tanggal_bap')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- <div class="mb-3">
                <label class="form-label">File BAP</label>
                <input type="file"
                       name="file_bap"
                       class="form-control @error('file_bap') is-invalid @enderror"
                       accept=".pdf,.jpg,.jpeg,.png"
                       required>

                <small class="text-muted">
                    Format yang diperbolehkan: PDF, JPG, JPEG, PNG.
                    <br>
                    <strong>Ukuran file maksimal 100 MB.</strong>
                </small>

                @error('file_bap')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div> --}}

            <div class="d-flex gap-2">
        <a href="{{ route('berita-acara.index') }}" class="btn btn-secondary">Kembali</a>
        <button class="btn btn-primary" id="btnSubmitBAP">
            Simpan
        </button>
    </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
document.getElementById('formBeritaAcara').addEventListener('submit', function (e) {
    const btn = document.getElementById('btnSubmitBAP');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
});
</script>
@endpush
@endsection