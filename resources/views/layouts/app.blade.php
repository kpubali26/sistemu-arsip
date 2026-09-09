<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SINAR KPU BALI V2 </title>
<link rel="icon" type="image/png" href="{{ asset('logo-sinar-v2-kotak.png') }}?v=1">

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>

    <style>
        /* ----- VARIABLES ----- */
        :root {
            --primary-dark: #6a1a1a;
            --primary: #8B0000;
            --primary-light: #a83232;
            --primary-soft: #fdf0f0;
            --sidebar-width: 270px;
            --sidebar-collapsed: 88px;
            --header-height: 72px;
            --transition-default: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: #f4f7fc;
            overflow-x: hidden;
            color: #1e293b;
        }

        /* ----- TYPOGRAPHY & UTILITY ----- */
        .fs-sm { font-size: 0.875rem; }
        .text-primary-dark { color: var(--primary-dark); }
        .bg-primary-soft { background-color: var(--primary-soft); }
        .rounded-2xl { border-radius: 1rem; }
        .border-subtle { border-color: #e9eef3; }

        /* ----- SIDEBAR (ELEVATED, CLEAN) ----- */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(145deg, #7a1f1f 0%, #660000 100%);
            color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(0px);
            z-index: 1030;
            transition: var(--transition-default);
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
        }

        /* collapsed mode */
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        .sidebar.collapsed .brand-text,
        .sidebar.collapsed .nav-link span:not(.ms-auto i),
        .sidebar.collapsed .nav-link small,
        .sidebar.collapsed .nav-link .badge,
        .sidebar.collapsed .nav-link .ms-auto,
        .sidebar.collapsed .submenu-list {
            display: none;
        }
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px 0;
            margin: 6px 12px;
        }
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.4rem;
        }
        .sidebar.collapsed .logo-container {
            width: 58px;
            height: 58px;
            margin: 0 auto;
            border-radius: 18px;
        }
        .sidebar.collapsed .sidebar-brand {
            padding: 20px 8px;
        }
        .sidebar.collapsed .nav-link.active {
            border-left: none;
            box-shadow: none;
            background: rgba(255, 255, 255, 0.18);
            outline: 2px solid #ffb347;
            outline-offset: -2px;
        }

        /* brand */
        .sidebar-brand {
            position: relative;
            padding: 28px 20px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 24px;
        }
        .logo-container {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 24px;
            width: 112px;
            height: 112px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            box-shadow:
                0 16px 32px rgba(32, 8, 8, 0.24),
                0 0 26px rgba(255, 36, 112, 0.14);
            transition: var(--transition-default);
        }
        .sidebar-logo {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 22px;
            filter: drop-shadow(0 8px 14px rgba(255, 36, 112, 0.18));
        }
        .brand-text h4 {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.3px;
            margin: 0;
            color: white;
        }
        .brand-text small {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        /* toggle button (desktop) - sekarang di bawah teks brand */
        .toggle-sidebar-btn {
            margin: 14px auto 0;
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            cursor: pointer;
            transition: var(--transition-default);
        }
        .toggle-sidebar-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: scale(1.08);
        }

        /* navigation */
        .nav {
            flex: 1;
            gap: 4px;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 20px;
            margin: 0 12px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            transition: var(--transition-default);
            font-weight: 500;
        }
        .nav-link i {
            font-size: 1.25rem;
            width: 28px;
            margin-right: 12px;
            text-align: center;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.12);
            color: white;
            transform: translateX(3px);
        }
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 3px solid #ffb347;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        /* submenu */
        .submenu-list {
            padding-left: 1.2rem;
            margin-top: 4px;
            margin-bottom: 6px;
            list-style: none;
        }
        .submenu-list .nav-link {
            padding: 10px 16px;
            font-size: 0.88rem;
            margin: 2px 8px 2px 0;
        }
        .submenu-list .nav-link i {
            font-size: 1rem;
            width: 24px;
        }
        .collapse.show + .submenu-list {
            display: block;
        }

        /* ----- MAIN CONTENT (LAYOUT)----- */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: var(--transition-default);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed);
        }

        /* ----- TOP HEADER (MODERN)----- */
        .top-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #eef2f6;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1025;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.96);
        }
        .page-title h3 {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #8B0000, #a13030);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            margin: 0;
        }
        .page-title p {
            color: #5b6e8c;
            font-size: 0.85rem;
            margin: 0;
        }

        /* user dropdown area */
        .user-info .dropdown-toggle::after {
            display: none;
        }
        .user-avatar {
            width: 46px;
            height: 46px;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            transition: 0.2s;
        }
        .dropdown-menu {
            border: none;
            border-radius: 20px;
            box-shadow: 0 18px 35px rgba(0,0,0,0.12);
            margin-top: 8px;
            z-index: 1060;
        }

        /* Cards & content */
        .card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 8px 20px -6px rgba(0, 0, 0, 0.05);
            transition: 0.2s;
            background: white;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #ecf3fa;
            padding: 1.2rem 1.5rem;
            font-weight: 600;
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 40px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -8px rgba(139,0,0,0.4);
        }

        /* footer */
        .footer {
            margin-top: 3rem;
            padding: 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            border-top: 1px solid #eef2f8;
            color: #5c6f87;
        }

        /* mobile adjustments */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1060;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .toggle-sidebar-btn {
                display: none;
            }
            .mobile-menu-btn {
                display: flex;
                background: var(--primary);
                border: none;
                color: white;
                border-radius: 50px;
                padding: 8px 16px;
                gap: 8px;
                align-items: center;
            }
            .top-header {
                padding: 12px 20px;
            }
        }
        @media (min-width: 992px) {
            .mobile-menu-btn {
                display: none;
            }
        }

        /* micro interactions */
        .fade-in {
            animation: fadeSlideUp 0.35s ease-out;
        }
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        a, button {
            transition: 0.2s ease;
        }
        .mobile-menu-btn {
    display: none;
}

@media (max-width: 991.98px) {
    .mobile-menu-btn {
        display: flex !important;
        align-items: center;
    }
}
    </style>

    @stack('styles')
</head>
<body>

<!-- ========== SIDEBAR ========== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="logo-container">
            <img src="{{ asset('logo-sinar-v2-kotak.png') }}?v=1" alt="Logo SINAR KPU Bali V2" class="sidebar-logo">
        </div>
        <div class="brand-text">
            <h4>SINAR KPU BALI</h4>
            <small>Sistem Arsip Digital</small>
        </div>

        <!-- ========== TOGGLE BUTTON (di bawah teks brand) ========== -->
        <div class="toggle-sidebar-btn" id="sidebarToggleBtn" aria-label="Toggle sidebar">
            <i class="bi bi-chevron-left" id="toggleIcon"></i>
        </div>
    </div>

    <ul class="nav flex-column">
        @auth
            @if(auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('arsip.*') ? 'active' : '' }}"
                       data-bs-toggle="collapse" href="#menuArsip" role="button" aria-expanded="{{ request()->routeIs('arsip.*') ? 'true' : 'false' }}">
                        <i class="bi bi-folder"></i> <span>Kelola Arsip</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('arsip.*') ? 'show' : '' }}" id="menuArsip">
                        <ul class="submenu-list">
                            <li><a class="nav-link {{ request()->routeIs('arsip.*') ? 'active' : '' }}" href="{{ route('arsip.index') }}"><i class="bi bi-folder2-open"></i> Arsip Internal</a></li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('arsip-masuk.*') ? 'active' : '' }}" href="{{ route('arsip-masuk.index') }}">
                        <i class="bi bi-inbox-fill"></i> <span>Daftar Arsip Masuk</span>
                        @if($arsipMasukCount ?? 0 > 0) <span class="badge bg-danger ms-2">{{ $arsipMasukCount }}</span> @endif
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('manajemen-lokasi.*') ? 'active' : '' }}" href="{{ route('manajemen-lokasi.index') }}">
                        <i class="bi bi-geo-alt-fill"></i> <span>Manajemen Lokasi</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('pemusnahan.*') ? 'active' : '' }}"
                       data-bs-toggle="collapse" href="#menuPemusnahan" role="button">
                        <i class="bi bi-trash"></i> <span>Pemusnahan Arsip</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('pemusnahan.*') ? 'show' : '' }}" id="menuPemusnahan">
                        <ul class="submenu-list">
                            <li><a class="nav-link {{ request()->routeIs('pemusnahan.usulan.index') ? 'active' : '' }}" href="{{ route('pemusnahan.usulan.index') }}"><i class="bi bi-fire"></i> Proses Pemusnahan</a></li>
                            <li><a class="nav-link {{ request()->routeIs('pemusnahan.riwayat') ? 'active' : '' }}" href="{{ route('pemusnahan.riwayat') }}"><i class="bi bi-clock-history"></i> Riwayat Pemusnahan</a></li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('lintas-unit.*') ? 'active' : '' }}" href="{{ route('lintas-unit.index') }}">
                        <i class="bi bi-diagram-3-fill"></i> <span>Arsip Lintas Unit</span>
                    </a>
                </li>
            @endif

            @if(auth()->user()->role === 'super_admin')
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('superadmin.sub-bagians.*') ? 'active' : '' }}" href="{{ route('superadmin.sub-bagians.index') }}"><i class="bi bi-layers"></i> <span>Kelola Sub Bagian</span></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('superadmin.kode-klasifikasis.*') ? 'active' : '' }}" href="{{ route('superadmin.kode-klasifikasis.index') }}"><i class="bi bi-card-list"></i> <span>Kode Klasifikasi</span></a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('superadmin.satkers.*') ? 'active' : '' }}" href="{{ route('superadmin.satkers.index') }}"><i class="bi bi-building-fill-gear"></i> <span>Kelola Satker</span></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}" href="{{ route('superadmin.users.index') }}"><i class="bi bi-shield-check"></i> <span>Manajemen User</span></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.backup.*') ? 'active' : '' }}" href="{{ route('admin.backup.index') }}"><i class="bi bi-cloud-arrow-down-fill"></i> <span>Backup Sistem</span></a></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('superadmin.guide-books.*') ? 'active' : '' }}" href="{{ route('superadmin.guide-books.index') }}">
                        <i class="bi bi-journal-bookmark-fill"></i> <span>Manajemen Guide Book</span>
                    </a>
                </li>
            @endif

            @if(auth()->user()->role === 'user')
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('subbagian.dashboard') ? 'active' : '' }}" href="{{ route('subbagian.dashboard') }}"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a></li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center {{ request()->routeIs('subbagian.arsip.*') ? 'active' : '' }}"
                       data-bs-toggle="collapse" href="#menuArsip" role="button" aria-expanded="{{ request()->routeIs('subbagian.arsip.*') ? 'true' : 'false' }}">
                        <i class="bi bi-folder"></i> <span>Kelola Arsip</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('subbagian.arsip.*') ? 'show' : '' }}" id="menuArsip">
                        <ul class="submenu-list">
                            <li><a class="nav-link {{ request()->routeIs('subbagian.arsip.*') ? 'active' : '' }}" href="{{ route('subbagian.arsip.index') }}"><i class="bi bi-folder2-open"></i> Arsip Internal</a></li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('subbagian.manajemen-lokasi.*') ? 'active' : '' }}" href="{{ route('subbagian.manajemen-lokasi.index') }}"><i class="bi bi-geo-alt-fill"></i> <span>Manajemen Lokasi</span></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('subbagian.riwayat-pemindahan.*') ? 'active' : '' }}" href="{{ route('subbagian.riwayat-pemindahan.index') }}"><i class="bi bi-download"></i> <span>Riwayat Pemindahan</span></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('berita-acara.*') ? 'active' : '' }}" href="{{ route('berita-acara.index') }}"><i class="bi bi-file-earmark-text"></i> <span>Berita Acara Pemindahan</span></a></li>
                  <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('lintas-unit.*') ? 'active' : '' }}" href="{{ route('lintas-unit.index') }}">
                        <i class="bi bi-diagram-3-fill"></i> <span>Arsip Lintas Unit</span>
                    </a>
                </li>
            @endif

            @if(auth()->user()->role === 'TU')

    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('tu.dashboard') ? 'active' : '' }}"
           href="{{ route('tu.dashboard') }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
    </li>

            @endif

            @php
                $isPengelolaSurat = in_array(strtolower((string) auth()->user()->role), ['admin', 'super_admin', 'tu'], true);
                $suratMenuActive = $isPengelolaSurat
                    ? request()->routeIs('surat-masuk.*', 'surat-instansi.*', 'tujuan-disposisi.*')
                    : request()->routeIs('subbagian.surat-masuk.*');
            @endphp
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center {{ $suratMenuActive ? 'active' : '' }}"
                   data-bs-toggle="collapse" href="#menuSuratMasuk" role="button">
                    <i class="bi bi-envelope-paper"></i><span>Surat Masuk</span><i class="bi bi-chevron-down ms-auto"></i>
                </a>
                <div class="collapse {{ $suratMenuActive ? 'show' : '' }}" id="menuSuratMasuk">
                    <ul class="submenu-list">
                        <li><a class="nav-link {{ request()->routeIs($isPengelolaSurat ? 'surat-masuk.*' : 'subbagian.surat-masuk.*') ? 'active' : '' }}" href="{{ $isPengelolaSurat ? route('surat-masuk.index') : route('subbagian.surat-masuk.index') }}"><i class="bi bi-list-ul"></i> Daftar Surat Masuk</a></li>
                        @if($isPengelolaSurat)
                            <li><a class="nav-link {{ request()->routeIs('surat-instansi.*') ? 'active' : '' }}" href="{{ route('surat-instansi.index') }}"><i class="bi bi-building"></i> Instansi/Satker</a></li>
                            <li><a class="nav-link {{ request()->routeIs('tujuan-disposisi.*') ? 'active' : '' }}" href="{{ route('tujuan-disposisi.index') }}"><i class="bi bi-signpost-split"></i> Tujuan Disposisi</a></li>
                        @endif
                    </ul>
                </div>
            </li>

            {{-- <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('sinar-v1.*') ? 'active' : '' }}" href="{{ route('sinar-v1.index') }}">
                    <i class="bi bi-archive-fill"></i> <span>SINAR V1</span>
                    <small class="ms-1 opacity-75">Historis</small>
                </a>
            </li> --}}
            @if(in_array(strtolower((string) auth()->user()->role), ['admin','super_admin','tu']))
                {{-- <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('sinar-v1.import*') ? 'active' : '' }}" href="{{ route('sinar-v1.import') }}">
                        <i class="bi bi-database-up"></i> <span>Import SINAR V1</span>
                    </a>
                </li> --}}
            @endif
            <li class="nav-item">
    <a class="nav-link {{ request()->routeIs('guide-book.*') ? 'active' : '' }}" href="{{ route('guide-book.index') }}">
        <i class="bi bi-journal-bookmark-fill"></i> <span>Panduan Pengguna</span>
    </a>
</li>
        @endauth
    </ul>
</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-content" id="mainContent">
    <div class="top-header">
        <div class="d-flex align-items-center gap-3">
            <button class="mobile-menu-btn btn btn-sm" id="mobileMenuBtn" type="button">
                <i class="bi bi-list"></i> Menu
            </button>
            <div class="page-title">
                <h3>@yield('page-title', 'Dashboard')</h3>
                <p>@yield('page-subtitle', 'Sistem Informasi Arsip KPU Bali')</p>
                @php $satkerAktif = \App\Models\Satker::aktif(); @endphp
                @if($satkerAktif)
                    <span class="badge bg-primary-soft text-primary-dark fs-sm mt-1">
                        <i class="bi bi-building"></i> {{ $satkerAktif->nama_satker }}
                    </span>
                @endif
            </div>
        </div>

     @php
    $role = auth()->user()->role ?? 'user';

    $roleLabel = match ($role) {
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'tu'          => 'Tata Usaha',
        'user'        => 'Sub Bagian',
        default       => 'User',
    };

    $badgeColor = match ($role) {
        'super_admin' => 'danger',
        'admin'       => 'primary',
        'tu'          => 'warning',
        'user'        => 'secondary',
        default       => 'secondary',
    };
@endphp

        <div class="dropdown user-info">
            <button class="btn p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">{{ auth()->check() ? strtoupper(substr(auth()->user()->name, 0, 1)) : 'U' }}</div>
                <div class="d-none d-md-block text-end">
                    <strong class="d-block">{{ auth()->user()->name }}</strong>
                    <span class="badge bg-{{ $badgeColor }} fs-sm">{{ $roleLabel }}</span>
                </div>
                <i class="bi bi-chevron-down text-muted d-none d-md-block"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li class="px-3 py-2"><strong>{{ auth()->user()->name }}</strong><br><small>{{ auth()->user()->email }}</small></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Edit Profil</a></li>
                <li><a class="dropdown-item" href="{{ route('profile.password') }}"><i class="bi bi-key me-2"></i> Ubah Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

     <div class="container-fluid px-lg-4 px-3 pt-4 fade-in">
        @yield('content')
    </div>

    <div class="footer">
        <p class="mb-0">&copy; {{ date('Y') }} SINAR - KPU Bali <br><small>Versi 2.0</small></p>
    </div>
</div>

<script>
    (function() {
        // SIDEBAR TOGGLE DESKTOP
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('sidebarToggleBtn');
        const toggleIcon = document.getElementById('toggleIcon');

        function updateToggleState() {
            if (!sidebar) return;
            const isCollapsed = sidebar.classList.contains('collapsed');
            if (toggleIcon) {
                toggleIcon.className = isCollapsed ? 'bi bi-chevron-right' : 'bi bi-chevron-left';
            }
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            if (mainContent) mainContent.classList.toggle('expanded', isCollapsed);
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                updateToggleState();
            });
        }

        // load saved state
        const saved = localStorage.getItem('sidebarCollapsed');
        if (saved === 'true' && window.innerWidth > 991) {
            sidebar.classList.add('collapsed');
            updateToggleState();
        }

        // MOBILE MENU
        const mobileBtn = document.getElementById('mobileMenuBtn');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }

        // close sidebar when click outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 991 && sidebar.classList.contains('show') &&
                !sidebar.contains(event.target) && !mobileBtn?.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });

        // adjust on window resize (prevent layout shift)
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) {
                sidebar.classList.remove('show');
                if (localStorage.getItem('sidebarCollapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                    mainContent?.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    mainContent?.classList.remove('expanded');
                }
                updateToggleState();
            } else {
                sidebar.classList.remove('collapsed');
                mainContent?.classList.remove('expanded');
            }
        });

        // small fix for Bootstrap dropdowns to be above everything
        document.querySelectorAll('.dropdown-toggle').forEach(el => {
            el.addEventListener('shown.bs.dropdown', () => {
                const menu = el.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.style.zIndex = '1070';
                }
            });
        });
    })();
</script>
<script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted || performance.getEntriesByType("navigation")[0]?.type === "back_forward") {
        window.location.reload();
    }
});
</script>
@stack('scripts')
</body>
</html>
