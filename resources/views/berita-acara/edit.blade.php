@extends('layouts.app')

@section('page-title', 'Edit Berita Acara')
@section('page-subtitle', 'Perbarui Data Berita Acara Pemindahan')

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

        <form action="{{ route('berita-acara.update', $berita_acara->id) }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Nomor BAP</label>
                <input type="text"
                       name="nomor_bap"
                       value="{{ old('nomor_bap', $berita_acara->nomor_bap) }}"
                       class="form-control @error('nomor_bap') is-invalid @enderror"
                       required>

                @error('nomor_bap')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal BAP</label>
                <input type="date"
                       name="tanggal_bap"
                       value="{{ old('tanggal_bap', $berita_acara->tanggal_bap) }}"
                       class="form-control @error('tanggal_bap') is-invalid @enderror"
                       required>

                @error('tanggal_bap')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- <div class="mb-3">
                <label class="form-label">File Baru (Opsional)</label>
                <input type="file"
                       name="file_bap"
                       accept=".pdf,.jpg,.jpeg,.png"
                       class="form-control @error('file_bap') is-invalid @enderror">

                <small class="text-muted">
                    Kosongkan jika tidak ingin mengganti file.
                    <br>
                    Format: PDF, JPG, JPEG, PNG.
                    <br>
                    <strong>Ukuran file maksimal 100 MB.</strong>
                </small>

                @error('file_bap')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            @if($berita_acara->file_bap)
                <div class="mb-3">
                    <label class="form-label">File Saat Ini</label>
                    <br>
                    <a href="{{ asset('storage/berita_acara/'.$berita_acara->file_bap) }}"
                       target="_blank"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> Lihat File Lama
                    </a>
                </div>
            @endif --}}

            <div class="d-flex gap-2">
                <a href="{{ route('berita-acara.index') }}" class="btn btn-secondary">
                    Kembali
                </a>

                <button type="submit" class="btn btn-warning">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>
@endsection