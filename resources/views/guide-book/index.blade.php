@extends('layouts.app')

@section('page-title', 'Panduan Pengguna')
@section('page-subtitle', 'Buku panduan penggunaan sistem SINAR')

@section('content')
@php
    $isPengelola = in_array(auth()->user()->role, ['admin', 'super_admin', 'tu']);
@endphp

<div class="row g-4">

    <div class="col-12">
        <div class="card p-4">
            <h5 class="mb-3">
                <i class="bi bi-{{ $isPengelola ? 'building' : 'people' }} text-primary"></i>
                Panduan {{ $kategoriLabel }}
            </h5>
            @forelse($guides as $guide)
                @include('guide-book._item', ['guide' => $guide, 'canManage' => false])
            @empty
                <p class="text-muted mb-0">Belum ada panduan untuk {{ $kategoriLabel }}.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection