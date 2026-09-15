<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Arsip;
use App\Models\KodeKlasifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\BeritaAcaraDetail;
use App\Models\HistoryPindah;
use App\Models\MasterRak;
use App\Models\MasterBox;


class SubBagianRiwayatPemindahanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ambil arsip dengan semua status yang relevan termasuk DRAFT
        $query = Arsip::with(['kodeKlasifikasi', 'subBagian'])
            ->where('sub_bagian_id', $user->sub_bagian_id)
            ->whereIn('status_pindah', ['DIPINDAHKAN', 'DITOLAK', 'DIAJUKAN', 'DIPERBAIKI', 'DRAFT'])
            ->orderBy('updated_at', 'desc');

        // Filter berdasarkan status
        if ($request->has('status_pindah') && $request->status_pindah != '') {
            $query->where('status_pindah', $request->status_pindah);
        }

        // Filter berdasarkan tahun
        if ($request->has('tahun') && $request->tahun != '') {
            $query->whereYear('updated_at', $request->tahun);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('uraian_arsip', 'like', "%{$request->search}%")
                    ->orWhereHas('kodeKlasifikasi', function ($sub) use ($request) {
                        $sub->where('kode', 'like', "%{$request->search}%")
                            ->orWhere('uraian', 'like', "%{$request->search}%");
                    });
            });
        }
 if ($request->filled('status')) {
        $query->where('status_pindah', $request->status);
    }

        $arsips = $query->paginate(15);

        // Data untuk filter
        $tahunOptions = Arsip::where('sub_bagian_id', $user->sub_bagian_id)
            ->whereIn('status_pindah', ['DIPINDAHKAN', 'DITOLAK', 'DIAJUKAN', 'DIPERBAIKI', 'DRAFT'])
            ->selectRaw('YEAR(updated_at) as tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        // Status options
        $statusOptions = [
            'DRAFT' => 'Draft',
            'DIAJUKAN' => 'Diajukan',
            'DIPERBAIKI' => 'Diperbaiki',
            'DITOLAK' => 'Ditolak',
            'DIPINDAHKAN' => 'Dipindahkan'
        ];

        return view('subbagian.riwayat-pemindahan.index', compact(
            'arsips',
            'tahunOptions',
            'statusOptions'
        ));
    }

    public function show(Arsip $arsip)
    {
        $user = Auth::user();

        if ($arsip->sub_bagian_id != $user->sub_bagian_id) {
            abort(403);
        }

        if (!in_array($arsip->status_pindah, ['DIPINDAHKAN', 'DITOLAK', 'DIAJUKAN', 'DIPERBAIKI', 'DRAFT'])) {
            abort(404);
        }

        // Berita acara terakhir
        $beritaAcara = optional(
            $arsip->beritaAcaraDetailS()
                ->latest()
                ->first()
        )->beritaAcara;

        // Ambil tanggal pindah dari riwayat_pindah
        $tanggalDipindahkan = HistoryPindah::where('arsip_id', $arsip->id)
            ->where('alasan_pindah', 'Arsip dipindahkan ke Unit Kearsipan')
            ->orderBy('tanggal_pindah', 'desc')
            ->value('tanggal_pindah');

        return view(
            'subbagian.riwayat-pemindahan.show',
            compact('arsip', 'beritaAcara', 'tanggalDipindahkan')
        );
    }

    /**
     * Menampilkan halaman Perbaikan Arsip.
     * Berisi: alasan penolakan, form edit arsip, catatan perbaikan,
     * tombol ajukan kembali, dan tombol kembalikan ke arsip internal.
     */
    public function perbaikanForm(Arsip $arsip)
    {
        $user = Auth::user();

        if ($arsip->sub_bagian_id != $user->sub_bagian_id) {
            abort(403);
        }

        if (!in_array($arsip->status_pindah, ['DITOLAK', 'DIPERBAIKI'])) {
            abort(404);
        }

        $arsip->load(['kodeKlasifikasi', 'subBagian']);
        $kodeKlasifikasis = KodeKlasifikasi::orderBy('kode')->get();

        // Ambil Berita Acara terkait arsip ini (sama seperti di method show())
        $beritaAcara = optional(
            $arsip->beritaAcaraDetailS()
                ->latest()
                ->first()
        )->beritaAcara;

        $lokasi = $this->getLokasiArsip($user);
        $lokasiLabel = $this->getLabelLokasi($lokasi);

        $rakOptions = MasterRak::where('lokasi_arsip', $lokasi)
            ->orderBy('nomor_rak')
            ->get(['id', 'nomor_rak', 'lokasi_arsip']);

        $boxOptions = MasterBox::whereIn('rak_id', $rakOptions->pluck('id'))
            ->orderBy('nomor_box')
            ->get(['id', 'nomor_box', 'rak_id']);

        return view('subbagian.riwayat-pemindahan.perbaikan', compact(
            'arsip', 'kodeKlasifikasis', 'rakOptions', 'boxOptions',
            'lokasi', 'lokasiLabel', 'beritaAcara' // <-- TAMBAHKAN INI
        ));
    }

    /**
     * Simpan perbaikan arsip (edit data + catatan perbaikan).
     * Status arsip diubah menjadi DIPERBAIKI.
     * File berita acara TIDAK wajib diupload ulang di sini.
     */
     public function simpanPerbaikan(Request $request, Arsip $arsip)
    {
        $user = Auth::user();
 
        if ($arsip->sub_bagian_id != $user->sub_bagian_id
            || !in_array($arsip->status_pindah, ['DITOLAK', 'DIPERBAIKI'])) {
            abort(403);
        }
 
        $validated = $request->validate([
            'kode_klasifikasi_id' => 'required|exists:kode_klasifikasis,id',
            'uraian_arsip'        => 'required|string|min:30',
            'tahun_arsip'         => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'tanggal_arsip'       => 'required|date',
            'jumlah_berkas'       => 'required|integer|min:1',
            'satuan_arsip'        => 'required|string|max:20',
 
            'nomor_rak'           => 'nullable|string|max:50',
            'nomor_box'           => 'nullable|string|max:50',
            'nomor_sampul'        => 'nullable|string|max:50',
 
            'tingkat_perkembangan' => 'required|string|max:50',
            'keterangan'           => 'required|string|max:100',
            'media_arsip'          => 'required|string|max:50',
 
            // Upload BA baru bersifat OPSIONAL — tidak wajib
            'file_berita_acara_baru' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:102400',
            'jenis_dokumen' => 'nullable|in:pdf,link',
            'file_dokumen_baru' => 'nullable|file|mimes:pdf|max:102400',
            'link_folder' => 'nullable|url|max:500',
 
            // Nomor BAP (berita acara pindah) — opsional, hanya jika perlu diperbaiki
            'nomor_bap' => 'nullable|string|max:255',

            
 
            // Wajib diisi agar jelas apa yang sudah diperbaiki
            'catatan_perbaikan' => 'required|string|max:1000',
        ]);
 
        DB::beginTransaction();
        try {
                $jenisDokumen = $request->input('jenis_dokumen', 'pdf');

                // Tentukan kondisi dokumen SETELAH perubahan ini (apa yang akan tersimpan)
                $akanAdaFileBaru = ($jenisDokumen === 'pdf' && $request->hasFile('file_dokumen_baru'));
                $akanAdaLinkBaru = ($jenisDokumen === 'link' && !empty($validated['link_folder']));

                // Dokumen dianggap "ada" kalau: sudah ada dari dulu (dan tidak sedang dihapus/diganti tipe),
                // ATAU baru diisi sekarang (upload baru / link baru)
                $sudahPunyaFileLama = !empty($arsip->file_dokumen);
                $sudahPunyaLinkLama = !empty($arsip->link_folder);

                $dokumenAkanKosong = true;

                if ($jenisDokumen === 'pdf') {
                    // Kalau pilih mode PDF: valid jika ada file baru, atau sudah ada file lama
                    if ($akanAdaFileBaru || $sudahPunyaFileLama) {
                        $dokumenAkanKosong = false;
                    }
                } elseif ($jenisDokumen === 'link') {
                    // Kalau pilih mode link: valid jika ada link baru, atau sudah ada link lama
                    if ($akanAdaLinkBaru || $sudahPunyaLinkLama) {
                        $dokumenAkanKosong = false;
                    }
                }

                // Fallback: kalau field jenis_dokumen tidak dikirim sama sekali (misal request lama),
                // tetap valid selama arsip sudah punya salah satu dari dulu
                if ($sudahPunyaFileLama || $sudahPunyaLinkLama) {
                    // arsip lama sudah punya dokumen, jadi selama tidak sengaja dikosongkan, aman
                }

                if ($dokumenAkanKosong) {
                    DB::rollBack();
                    return back()
                        ->with('error', 'Dokumen arsip wajib diisi (upload PDF atau isi link) sebelum perbaikan bisa disimpan.')
                        ->withInput();
                }

                // Upload file berita acara baru (jika ada)
// Upload file berita acara baru (jika ada)
                if ($request->hasFile('file_berita_acara_baru')) {
                    if ($arsip->file_berita_acara) {
                        Storage::disk('public')->delete('berita_acara/' . $arsip->file_berita_acara);
                    }

                    $file = $request->file('file_berita_acara_baru');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->storeAs('berita_acara', $fileName, 'public');
                    $validated['file_berita_acara'] = $fileName;
                }

                // Upload / ganti dokumen arsip utama
                if ($jenisDokumen === 'pdf' && $akanAdaFileBaru) {
                    if ($arsip->file_dokumen) {
                        Storage::disk('public')->delete('arsip/' . $arsip->file_dokumen);
                    }

                    $fileDokumen = $request->file('file_dokumen_baru');
                    $fileDokumenName = time() . '_' . $fileDokumen->getClientOriginalName();
                    $fileDokumen->storeAs('arsip', $fileDokumenName, 'public');
                    $validated['file_dokumen'] = $fileDokumenName;
                    $validated['link_folder'] = null;
                } elseif ($jenisDokumen === 'link' && $akanAdaLinkBaru) {
                    if ($arsip->file_dokumen) {
                        Storage::disk('public')->delete('arsip/' . $arsip->file_dokumen);
                    }
                    $validated['file_dokumen'] = null;
                    // $validated['link_folder'] sudah otomatis kebawa dari request
                } else {
                    // Tidak ada perubahan dokumen — jangan sentuh file_dokumen / link_folder yang sudah ada
                    unset($validated['link_folder']);
                }
// kalau jenis_dokumen dipilih tapi field-nya kosong, biarkan data lama tidak berubah
            
            // Nomor BAP bukan kolom di tabel arsips, jadi diupdate terpisah
            // ke tabel berita_acara_pindah lewat relasi berita_acara_detail.
            $nomorBapBaru = $validated['nomor_bap'] ?? null;
            unset($validated['nomor_bap']);
 
            if (!empty($nomorBapBaru)) {
                $beritaAcaraDetail = $arsip->beritaAcaraDetailS()->latest()->first();
 
                if ($beritaAcaraDetail && $beritaAcaraDetail->beritaAcara) {
                    $beritaAcaraDetail->beritaAcara->update([
                        'nomor_bap' => $nomorBapBaru,
                    ]);
                }
            }
 
            // Tandai sebagai sudah diperbaiki (belum diajukan ulang)
            $validated['status_pindah'] = 'DIPERBAIKI';
            $validated['updated_at'] = now();
 
            unset($validated['file_berita_acara_baru']);
            unset($validated['file_dokumen_baru']);
 
            $arsip->update($validated);
 
            DB::commit();
 
            return redirect()->route('subbagian.riwayat-pemindahan.perbaikan', $arsip->id)
                ->with('success', 'Perbaikan arsip berhasil disimpan. Silakan klik "Ajukan Kembali" jika sudah yakin.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Ajukan kembali arsip yang sudah diperbaiki.
     * TIDAK memerlukan upload ulang berkas berita acara.
     */
    public function ajukanKembali(Request $request, Arsip $arsip)
    {
        $user = Auth::user();

        if ($arsip->sub_bagian_id != $user->sub_bagian_id || $arsip->status_pindah != 'DIPERBAIKI') {
            abort(403);
        }

        DB::beginTransaction();
        try {
            $arsip->update([
                'status_pindah'        => 'DIAJUKAN',
                'diverifikasi_oleh'    => null,
                'tanggal_diverifikasi' => null,
                'updated_at'           => now()
            ]);

            DB::commit();

            return redirect()->route('subbagian.riwayat-pemindahan.index')
                ->with('success', 'Arsip berhasil diajukan kembali untuk proses verifikasi.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Kembalikan arsip menjadi arsip internal Sub Bagian.
     * Dipakai untuk kasus arsip ditolak karena sebenarnya masih aktif
     * dan tidak perlu dipindahkan ke Unit Kearsipan.
     *
     * - status_pindah di-reset (keluar dari alur pemindahan)
     * - arsip dikeluarkan dari detail berita acara terkait
     */
    public function kembalikanInternal(Arsip $arsip)
    {
        $user = Auth::user();

        if ($arsip->sub_bagian_id != $user->sub_bagian_id
            || !in_array($arsip->status_pindah, ['DITOLAK', 'DIPERBAIKI'])) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            // Keluarkan arsip ini dari detail berita acara (jika ada)
            $arsip->beritaAcaraDetailS()->delete();

            // Reset arsip menjadi arsip internal Sub Bagian
            // (keluar sepenuhnya dari alur pemindahan ke Unit Kearsipan)
            $arsip->update([
                'status_pindah'         => 'BELUM',
                'catatan_verifikasi'    => null,
                'catatan_perbaikan'     => null,
                'diverifikasi_oleh'     => null,
                'tanggal_diverifikasi'  => null,
                'rak_id'                => null,
                'box_id'                => null,
                'updated_at'            => now(),
            ]);

            DB::commit();

            return redirect()->route('subbagian.riwayat-pemindahan.index')
                ->with('success', 'Arsip berhasil dikembalikan menjadi arsip internal Sub Bagian dan dikeluarkan dari berita acara.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // Method lama di bawah ini dipertahankan untuk kompatibilitas
    // route yang mungkin masih memanggilnya, tapi flow utama
    // sekarang menggunakan perbaikanForm() + simpanPerbaikan().
    // ==========================================================

    public function perbaikiArsip(Request $request, Arsip $arsip)
    {
        return redirect()->route('subbagian.riwayat-pemindahan.perbaikan', $arsip->id);
    }

    public function editPerbaikan(Arsip $arsip)
    {
        return redirect()->route('subbagian.riwayat-pemindahan.perbaikan', $arsip->id);
    }

    public function updatePerbaikan(Request $request, Arsip $arsip)
    {
        return $this->simpanPerbaikan($request, $arsip);
    }

    private function getLokasiArsip($user)
{
    $namaSub = $user->subBagian->nama_sub_bagian ?? null;

    $mapLokasi = [
        'Sub Bagian Keuangan,Umum dan Logistik' => 'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK',
        'Sub Bagian Partisipasi, Hubungan Masyarakat dan SDM' => 'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM',
        'Sub Bagian Perencanaan, Data, dan Informasi' => 'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI',
        'Sub Bagian Teknis Penyelenggaraan Pemilu dan Hukum' => 'RUANG_SUBBAGIAN_TEKNIS_HUKUM',
    ];

    return $mapLokasi[$namaSub] ?? null;
}

// Tambahkan method ini di controller
private function getLabelLokasi($lokasiKey)
{
    $labels = [
  'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK' => 'Ruang Subbagian Keuangan, Umum & Logistik',
        'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM' => 'Ruang Subbagian Parmas & SDM',
        'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI' => 'Ruang Subbagian Perencanaan, Data & Informasi',
        'RUANG_SUBBAGIAN_TEKNIS_HUKUM' => 'Ruang Subbagian Teknis dan Hukum',
    ];
    return $labels[$lokasiKey] ?? $lokasiKey;
}
}