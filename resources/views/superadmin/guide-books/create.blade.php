@extends('layouts.app')

@section('page-title', 'Tambah Guide Book')

@section('content')
<div class="card p-4" style="max-width:700px;">
    <form action="{{ route('superadmin.guide-books.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('superadmin.guide-books._form')
        <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-upload"></i> Simpan</button>
        <a href="{{ route('superadmin.guide-books.index') }}" class="btn btn-light mt-2">Batal</a>
    </form>
</div>
@endsection