{{-- resources/views/arsip/show.blade.php --}}
@extends('layouts.app')

@section('page-title', 'Detail Arsip')
@section('page-subtitle', 'Informasi Lengkap Arsip')

@section('content')
<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Detail Arsip</h6>
        {{-- <div class="btn-group">
            <a href="{{ route('arsip.index') }}" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="{{ route('arsip.edit', $arsip->id) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div> --}}
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Informasi Utama -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary mb-3">Informasi Utama</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%"><strong>Kode Klasifikasi</strong></td>
                        <td>: {{ $arsip->kodeKlasifikasi->kode ?? 'N/A' }} - {{ $arsip->kodeKlasifikasi->uraian ?? '' }}</td>
                    </tr>
                   <tr>
                        <td><strong>Judul Arsip</strong></td>
                        <td class="detail-text">
                            : {{ $arsip->uraian_arsip }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Sub Bagian</strong></td>
                        <td>: {{ $arsip->subBagian->nama_sub_bagian ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tahun Arsip</strong></td>
                        <td>: {{ $arsip->tahun_arsip }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Arsip</strong></td>
                        <td>: {{ $arsip->tanggal_arsip ? \Carbon\Carbon::parse($arsip->tanggal_arsip)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status Arsip</strong></td>
                        <td>: 
                            @php
                                $statusColors = [
                                    'AKTIF' => 'success',
                                    'INAKTIF' => 'warning',
                                    'MUSNAH' => 'danger',
                                    'PERMANEN' => 'info'
                                ];
                                $color = $statusColors[$arsip->status_arsip] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ $arsip->status_arsip }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Status Pindah</strong></td>
                        <td>:
                            @php
                                $pindahColors = [
                                    'BELUM PINDAH' => 'secondary',
                                    'DIAJUKAN'  => 'info',
                                    'DIPINDAHKAN'     => 'success',
                                ];
                                $pindahColor = $pindahColors[$arsip->status_pindah] ?? 'secondary';
                            @endphp

                            @if($arsip->status_pindah)
                                <span class="badge bg-{{ $pindahColor }}">
                                    {{ $arsip->status_pindah }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>

                </table>
            </div>
            
            <!-- Informasi Fisik -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary mb-3">Informasi Fisik</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%"><strong>Jumlah Berkas</strong></td>
                        <td>: {{ $arsip->jumlah_berkas ?? '1' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Satuan Arsip</strong></td>
                        <td>: {{ $arsip->satuan_arsip ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tingkat Perkembangan</strong></td>
                        <td>: {{ $arsip->tingkat_perkembangan ?? '-' }}</td>
                    </tr>

                    <tr>
                        <td><strong>Lokasi Arsip</strong></td>
                        <td>:
                            @php
                                $lokasiLabels = [
                                    'RECORD_CENTER_PERMANEN' => 'Record Center Permanen',
                                    'RECORD_CENTER_INAKTIF'  => 'Record Center Inaktif',
                                    'SUB_BAGIAN'             => 'Ruang Sub Bagian',
                                    // Tambahkan jika ada lokasi khusus subbagian
                                      'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK' => 'Ruang Subbagian Keuangan, Umum & Logistik',
        'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM' => 'Ruang Subbagian Parmas & SDM',
        'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI' => 'Ruang Subbagian Perencanaan, Data & Informasi',
        'RUANG_SUBBAGIAN_TEKNIS_HUKUM' => 'Ruang Subbagian Teknis dan Hukum',
                                ];
                                $lokasiLabel = $lokasiLabels[$arsip->lokasi_arsip] ?? $arsip->lokasi_arsip;
                            @endphp
                            {{ $lokasiLabel ?: '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Nomor Rak</strong></td>
                        <td>: {{ $arsip->rak ? $arsip->rak->nomor_rak : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Nomor Box</strong></td>
                        <td>: {{ $arsip->box ? $arsip->box->nomor_box : '-' }}</td>
                    </tr>
                    
                     <tr>
                        <td><strong>Nomor Sampul</strong></td>
                        <td>: {{ $arsip->nomor_sampul ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Kondisi Fisik</strong></td>
                        <td>: 
                            @php
                                $kondisiColors = [
                                    'BAIK' => 'success',
                                    'RUSAK' => 'warning',
                                    'HILANG' => 'danger'
                                ];
                                $kondisiColor = $kondisiColors[$arsip->keterangan] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $kondisiColor }}">
                                {{ $arsip->keterangan ?: '-' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Media Arsip</strong></td>
                        <td>: 
                            @php
                                $mediaColors = [
                                    'TEKSTUAL' => 'primary',
                                    'DIGITAL' => 'info',
                                ];
                                $mediaColor = $mediaColors[$arsip->media_arsip] ?? 'secondary';
                            @endphp

                            @if($arsip->media_arsip)
                                <span class="badge bg-{{ $mediaColor }}">
                                    {{ $arsip->media_arsip }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
    <td><strong>Klasifikasi Keamanan</strong></td>
    <td>:
        @php
            $keamananColors = [
                'Biasa/Terbuka' => 'success',
                'Terbatas'      => 'warning',
                'Rahasia'       => 'danger',
            ];

            $keamananColor = $keamananColors[$arsip->klasifikasi_keamanan] ?? 'secondary';
        @endphp

        @if($arsip->klasifikasi_keamanan)
            <span class="badge bg-{{ $keamananColor }}">
                {{ ucwords(strtolower($arsip->klasifikasi_keamanan)) }}
            </span>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
</tr>

                     <tr>
                        <td><strong>File Dokumen</strong></td>
                        <td>: 
                            @if($arsip->file_dokumen || $arsip->link_foto)
                                @if(auth()->user()->canDownloadArsip($arsip))
                                    <div>
                                        @if($arsip->file_dokumen)
                                            <!-- <a href="{{ route('arsip.downloadFile', $arsip->id) }}" -->
                                              <a href="{{ route('arsip.file', $arsip->id) }}"
                                               target="_blank"
                                               class="btn btn-primary">
                                                Lihat/Unduh Dokumen
                                            </a>
                                        @elseif($arsip->link_foto)
                                            <a href="{{ $arsip->link_foto }}"
                                               target="_blank"
                                               class="btn btn-primary">
                                                Lihat Dokumen
                                            </a>
                                        @endif

                                        <small class="text-muted d-block mt-1">
                                            {{ $arsip->file_dokumen ?? $arsip->link_foto }}
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">File terbatas. Tidak dapat dilihat.</span>
                                @endif
                            @else
                                <span class="text-muted">Tidak ada file</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Informasi Retensi -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary mb-3">Informasi Retensi</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%"><strong>Masa Retensi Aktif</strong></td>
                        <td>: {{ $arsip->aktif_tahun ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Masa Retensi Inaktif</strong></td>
                        <td>: {{ $arsip->inaktif_tahun ?: '-' }}</td>
                    </tr>
                    @if($arsip->tanggal_referensi)
                    <tr>
                        <td><strong>Tanggal Referensi</strong></td>
                        <td>: {{ $arsip->tanggal_referensi ? \Carbon\Carbon::parse($arsip->tanggal_referensi)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Aktif Sampai</strong></td>
                        <td>: 
                            @if($arsip->aktif_sampai)
                                {{ \Carbon\Carbon::parse($arsip->aktif_sampai)->format('d/m/Y') }}
                                @php
                                    $aktifDate = \Carbon\Carbon::parse($arsip->aktif_sampai);
                                    $now = \Carbon\Carbon::now();
                                    if($now > $aktifDate) {
                                        echo '<span class="badge bg-danger ms-2">Lewat</span>';
                                    } else {
                                        echo '<span class="badge bg-success ms-2">Aktif</span>';
                                    }
                                @endphp
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Inaktif Sampai</strong></td>
                        <td>: 
                            @if($arsip->inaktif_sampai)
                                {{ \Carbon\Carbon::parse($arsip->inaktif_sampai)->format('d/m/Y') }}
                                @php
                                    $inaktifDate = \Carbon\Carbon::parse($arsip->inaktif_sampai);
                                    $now = \Carbon\Carbon::now();
                                    if($now > $inaktifDate) {
                                        echo '<span class="badge bg-danger ms-2">Lewat</span>';
                                    } else {
                                        echo '<span class="badge bg-warning ms-2">Akan Datang</span>';
                                    }
                                @endphp
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Keterangan JRA</strong></td>
                        <td>: 
                            @php
                                $jraColors = [
                                    'PERMANEN' => 'info',
                                    'MUSNAH' => 'danger'
                                ];
                                $jraColor = $jraColors[$arsip->keterangan_jra] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $jraColor }}">{{ $arsip->keterangan_jra ?: '-' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Informasi Sistem -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-primary mb-3">Informasi Sistem</h6>
                <table class="table table-borderless">
                    <tr>
                        <td width="40%"><strong>Tanggal Masuk</strong></td>
                        <td>: {{ $arsip->tanggal_masuk ? \Carbon\Carbon::parse($arsip->tanggal_masuk)->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Dibuat Oleh</strong></td>
                        <td>: {{ $arsip->created_by ? \App\Models\User::find($arsip->created_by)->name ?? 'System' : 'System' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Dibuat</strong></td>
                        <td>: {{ $arsip->created_at ? \Carbon\Carbon::parse($arsip->created_at)->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Terakhir Diupdate</strong></td>
                        <td>: {{ $arsip->updated_at ? \Carbon\Carbon::parse($arsip->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                </table>
            </div>
            
            <!-- Catatan Tambahan -->
            @if($arsip->aktif_tahun && stripos($arsip->aktif_tahun, 'SETELAH') !== false && !$arsip->tanggal_referensi)
            <div class="col-md-12 mt-3">
                <div class="alert alert-warning">
                    <strong>Catatan:</strong> Arsip ini menggunakan format "SETELAH" untuk masa retensi tetapi tanggal referensi belum diisi. Status arsip otomatis di-set ke <strong>{{ $arsip->status_arsip }}</strong>.
                </div>
            </div>
            @endif
        </div>
        
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex gap-2 align-items-center">

                {{-- <a href="{{ $returnUrl ?? route('arsip.index') }}"
                class="btn btn-secondary">
                    ⬅ Kembali
                </a> --}}

                    <a href="{{ session('arsip_return_url') ?? url()->previous() ?? route('arsip.index') }}"
                class="btn btn-secondary">
                    ⬅ Kembali
                </a>


              @if(auth()->user()->canEditArsip($arsip) && $arsip->status_arsip !== 'DISETUJUI_MUSNAH')
    <a href="{{ route('arsip.edit', $arsip->id) }}"
       class="btn btn-warning">
        <i class="bi bi-pencil"></i> Edit Arsip
    </a>
@endif

@if(auth()->user()->canDeleteArsip($arsip) && $arsip->status_arsip !== 'DISETUJUI_MUSNAH')
    <form action="{{ route('arsip.destroy', $arsip->id) }}"
          method="POST"
          class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="btn btn-danger"
                onclick="return confirm('Apakah Anda yakin ingin menghapus arsip ini?')">
            <i class="bi bi-trash"></i> Hapus Arsip
        </button>
    </form>
@endif

            </div>
        </div>

    </div>
</div>

<style>
    .detail-text {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
}

</style>

@endsection

