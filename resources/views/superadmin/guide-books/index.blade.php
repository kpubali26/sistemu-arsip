@extends('layouts.app')

@section('page-title', 'Manajemen Guide Book')
@section('page-subtitle', 'Kelola panduan PDF untuk Unit Kearsipan & Sub Bagian')

@section('content')
<div class="card p-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="kategori" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <option value="unit_kearsipan" {{ request('kategori')=='unit_kearsipan'?'selected':'' }}>Unit Kearsipan</option>
                <option value="subbagian" {{ request('kategori')=='subbagian'?'selected':'' }}>Sub Bagian</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari judul..." value="{{ request('search') }}">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>

        <a href="{{ route('superadmin.guide-books.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Panduan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Diunggah Oleh</th>
                    <th>Tanggal</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guides as $guide)
                <tr>
                    <td>
                        <i class="bi bi-file-earmark-pdf text-danger"></i> {{ $guide->judul }}
                        @if($guide->deskripsi)
                            <br><small class="text-muted">{{ $guide->deskripsi }}</small>
                        @endif
                    </td>
                    <td>
                        @if($guide->kategori === 'unit_kearsipan')
                            <span class="badge bg-primary">Unit Kearsipan</span>
                        @else
                            <span class="badge bg-secondary">Sub Bagian</span>
                        @endif
                    </td>
                    <td>{{ $guide->uploader->name ?? '-' }}</td>
                    <td>{{ $guide->created_at->format('d M Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('superadmin.guide-books.download', $guide) }}" class="btn btn-sm btn-outline-primary" title="Unduh">
                            <i class="bi bi-download"></i>
                        </a>
                        <a href="{{ route('superadmin.guide-books.edit', $guide) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('superadmin.guide-books.destroy', $guide) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus panduan ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data panduan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $guides->links() }}
</div>
@endsection