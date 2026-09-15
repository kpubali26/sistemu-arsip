@extends('layouts.app')

@section('page-title', 'Panduan Pengguna')
@section('page-subtitle', 'Buku panduan penggunaan sistem SINAR')

@section('content')
@php
    $isPengelola = in_array(auth()->user()->role, ['admin', 'super_admin', 'tu']);

    // Ambil satker yang sedang aktif
    $satkerAktif = \App\Models\Satker::aktif();

    // Daftar kontak helpdesk per satker (key harus huruf kecil semua untuk pencocokan)
    $helpdeskList = [
        'kpu kabupaten badung' => [
            'nama'  => 'Vigyan',
            'nomor' => '082247716884',
        ],
        'kpu kabupaten jembrana' => [
            'nama'  => 'Krisna',
            'nomor' => '081337933719',
        ],
        'kpu kota denpasar' => [
            'nama'  => 'Erma',
            'nomor' => '089674342432',
        ],
    ];

    // Cocokkan nama satker aktif (case-insensitive) dengan daftar helpdesk
    $namaSatkerAktif = $satkerAktif->nama_satker ?? null;
    $helpdeskAktif = $namaSatkerAktif
        ? ($helpdeskList[strtolower(trim($namaSatkerAktif))] ?? null)
        : null;
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

    <!-- HELP DESK -->
    <div class="col-12">
        <div class="card p-4 border-start border-4 border-success">
            <h5 class="mb-3">
                <i class="bi bi-headset text-success"></i>
                Help Desk
            </h5>

            @if($helpdeskAktif)
                {{-- Satker aktif dikenali, tampilkan hanya kontak yang bersangkutan --}}
                <p class="mb-2">
                    Untuk bantuan teknis terkait <strong>{{ $namaSatkerAktif }}</strong>, silakan hubungi:
                </p>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle fs-4 text-success"></i>
                    <div>
                        <div class="fw-bold">{{ $helpdeskAktif['nama'] }}</div>
                        <a href="https://wa.me/62{{ ltrim($helpdeskAktif['nomor'], '0') }}" target="_blank" class="text-decoration-none">
                            <i class="bi bi-whatsapp text-success"></i> {{ $helpdeskAktif['nomor'] }}
                        </a>
                    </div>
                </div>
            @else
                {{-- Tidak ada satker aktif / tidak dikenali, tampilkan semua kontak --}}
                <p class="mb-3 text-muted">
                    Tidak ada satker aktif yang terdeteksi. Berikut pembagian kontak Help Desk untuk masing-masing satker:
                </p>
                <div class="row g-3">
                    @foreach($helpdeskList as $satkerNama => $kontak)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small text-uppercase mb-1">{{ $satkerNama }}</div>
                                <div class="fw-bold">{{ $kontak['nama'] }}</div>
                                <a href="https://wa.me/62{{ ltrim($kontak['nomor'], '0') }}" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-whatsapp text-success"></i> {{ $kontak['nomor'] }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection