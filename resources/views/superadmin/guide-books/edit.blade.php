@extends('layouts.app')

@section('page-title', 'Edit Guide Book')

@section('content')
<div class="card p-4" style="max-width:700px;">
    <form action="{{ route('superadmin.guide-books.update', $guideBook) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('superadmin.guide-books._form', ['guideBook' => $guideBook])
        <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-save"></i> Perbarui</button>
        <a href="{{ route('superadmin.guide-books.index') }}" class="btn btn-light mt-2">Batal</a>
    </form>
</div>
@endsection