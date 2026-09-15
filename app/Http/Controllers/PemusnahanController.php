<?php

namespace App\Http\Controllers;

use App\Models\Arsip;
use App\Models\Pemusnahan;
use App\Models\PemusnahanDetail;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DaftarArsipUsulMusnahExport;

class PemusnahanController extends Controller
{
    /**
     * ===============================
     * DAFTAR USULAN PEMUSNAHAN
     * ===============================
     */
    // public function index()
    // {
    //     $pemusnahans = Pemusnahan::withCount('details')
    //         ->latest()
    //         ->get();

    //     return view('pemusnahan.usulan.index', compact('pemusnahans'));
    // }
    public function index()
    {
        $pemusnahans = Pemusnahan::withCount('details')
            ->where('status', '!=', 'dimusnahkan')
            ->orderByDesc('created_at')
            ->get();

        return view('pemusnahan.usulan.index', compact('pemusnahans'));
    }

    /**
     * ===============================
     * FORM USULAN BARU
     * ===============================
     */
    public function create()
    {
        $arsip = Arsip::where('status_arsip', 'HABIS_RETENSI')
            ->where('keterangan_jra', 'MUSNAH')
            ->orderBy('tahun_arsip')
            ->get();

        return view('pemusnahan.usulan.create', compact('arsip'));
    }

    /**
     * ===============================
     * SIMPAN DRAFT USULAN
     * ===============================
     */
    public function store(Request $request)
    {
        $request->validate([
            'tahun' => 'required|numeric',
        ]);

        $pemusnahan = Pemusnahan::create([
            'tahun'      => $request->tahun,
            'tanggal_usulan' => now(),
            'status'     => 'draft',
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->route('pemusnahan.usulan.show', $pemusnahan)
            ->with('success', 'Usulan pemusnahan berhasil dibuat');
    }

    /**
     * ===============================
     * DETAIL USULAN PEMUSNAHAN
     * ===============================
     */
    public function show(Pemusnahan $pemusnahan)
    {
        $arsipList = Arsip::where('status_arsip', 'HABIS_RETENSI')
            ->whereDoesntHave('pemusnahanDetails', function ($q) use ($pemusnahan) {
                $q->where('pemusnahan_id', $pemusnahan->id);
            })
            ->orderBy('id')
            ->get();

        return view('pemusnahan.usulan.show', compact(
            'pemusnahan',
            'arsipList'
        ));
    }

    /**
     * ===============================
     * UPDATE KEPUTUSAN ARSIP
     * ===============================
     */
    public function updateKeputusan(Request $request, PemusnahanDetail $detail)
    {
        $request->validate([
            'keputusan' => 'required|in:musnah,inaktif,permanen',
        ]);

        $detail->update([
            'keputusan' => $request->keputusan,
            'catatan'   => $request->catatan,
        ]);

        return back()->with('success', 'Keputusan arsip diperbarui');
    }

    /**
     * ===============================
     * FINALISASI PEMUSNAHAN
     * ===============================
     */

     public function finalisasi(Pemusnahan $pemusnahan)
    {
        $jumlahMusnah = $pemusnahan->details()
            ->where('keputusan', 'musnah')
            ->count();

        if ($jumlahMusnah < 1) {
            return back()->with('error',
                'Belum ada arsip yang diputuskan MUSNAH.'
            );
        }

        $pemusnahan->update([
            'status' => 'diajukan_ke_anri',
            'tanggal_sidang' => now(),
        ]);

        return redirect()
            ->route('pemusnahan.usulan.index')
            ->with('success', 'Hasil sidang ditetapkan & diajukan ke ANRI.');
    }


        public function anri(Pemusnahan $pemusnahan)
        {
            if (!in_array($pemusnahan->status, ['diajukan_ke_anri', 'revisi_anri'])) {
                abort(403);
            }

            return view('pemusnahan.anri.index', compact('pemusnahan'));
        }

     public function setujuiAnri(Request $request, Pemusnahan $pemusnahan)
{
    // VALIDASI
    $request->validate([
        'file_persetujuan_anri' => 'required|file|mimes:pdf|max:102400',
    ]);

    // minimal 1 musnah
    $jumlahMusnah = $pemusnahan->details()
        ->where('keputusan', 'musnah')
        ->count();

    if ($jumlahMusnah < 1) {
        return back()->with('error',
            'Minimal 1 arsip harus diputuskan MUSNAH.'
        );
    }

    // upload file - PERBAIKAN DI SINI
    $file = $request->file('file_persetujuan_anri');
    $fileName = time() . '_' . $file->getClientOriginalName();
    $filePath = $file->storeAs('persetujuan_anri', $fileName, 'public');

    $pemusnahan->update([
        'status' => 'disetujui_anri',
        'tanggal_persetujuan_anri' => now(),
        'file_persetujuan_anri' => $filePath, // SEKARANG TERISI
    ]);

    foreach ($pemusnahan->details as $detail) {
        $arsip = $detail->arsip;

        if ($detail->keputusan === 'musnah') {
            $arsip->status_arsip = 'disetujui_musnah';
            $arsip->pemusnahan_id = $pemusnahan->id;
        } else {
            $arsip->status_arsip = strtoupper($detail->keputusan);
            $arsip->pemusnahan_id = null;
            $detail->delete();
        }
        $arsip->save();
    }

    return redirect()
        ->route('pemusnahan.usulan.index')
        ->with('success', 'Pemusnahan arsip telah disetujui ANRI.');
}

        public function simpanAnri(Request $request, Pemusnahan $pemusnahan)
        {
            $request->validate([
                'status' => 'required|in:disetujui_anri,revisi_anri',
                'catatan_anri' => 'nullable|string'
            ]);

            $pemusnahan->update([
                'status' => $request->status,
                'catatan_anri' => $request->catatan_anri,
                'tanggal_persetujuan_anri' =>
                    $request->status === 'disetujui_anri' ? now() : null
            ]);

            return redirect()
                ->route('pemusnahan.usulan.index')
                ->with('success', 'Status ANRI berhasil disimpan.');
        }



    /**
     * ===============================
     * NOTA DINAS WORD
     * ===============================
     */
    public function notaDinasWord()
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(
            "KOMISI PEMILIHAN UMUM PROVINSI BALI",
            ['bold' => true],
            ['alignment' => 'center']
        );

        $section->addTextBreak(2);
        $section->addText("NOTA DINAS", ['bold' => true], ['alignment' => 'center']);

        $fileName = 'Nota_Dinas_Pemusnahan.docx';
        $path = storage_path($fileName);

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    /**
     * ===============================
     * EXPORT EXCEL
     * ===============================
     */
       public function daftarArsipExcel(Pemusnahan $pemusnahan)
        {
            $jumlahMusnah = $pemusnahan->details()
                ->where('keputusan', 'musnah')
                ->count();

            if ($jumlahMusnah < 1) {
                return back()->with('error',
                    'Belum ada arsip yang diputuskan MUSNAH. Export tidak bisa dilakukan.'
                );
            }

            return Excel::download(
                new DaftarArsipUsulMusnahExport($pemusnahan),
                'Daftar_Arsip_Musnah_' . $pemusnahan->id . '.xlsx'
            );
        }

    /**
     * ===============================
     * RIWAYAT PEMUSNAHAN
     * ===============================
     */
    public function riwayat()
    {
        $pemusnahans = Pemusnahan::withCount('details')
            ->where('status', 'dimusnahkan')
            ->orderByDesc('tanggal_usulan')
            ->get();

        return view('pemusnahan.riwayat.index', compact('pemusnahans'));
    }

    public function riwayatShow(Pemusnahan $pemusnahan)
    {
        if ($pemusnahan->status !== 'dimusnahkan') {
            abort(404);
        }

        $pemusnahan->load(['details' => function ($q) {
    $q->where('keputusan', 'musnah')
      ->with('arsip');
}]);

        return view('pemusnahan.riwayat.show', compact('pemusnahan'));
    }



    public function eksekusi(Pemusnahan $pemusnahan)
    {
        // if ($pemusnahan->status !== 'disetujui_anri') {
        //     return back()->with('error', 'Pemusnahan belum disetujui ANRI.');
        // }

        return view('pemusnahan.riwayat.eksekusi', compact('pemusnahan'));
    }


 public function simpanEksekusi(Request $request, Pemusnahan $pemusnahan)
{
    $request->validate([
       'file_berita_acara' => 'required|file|mimes:pdf|max:102400',
        'file_sk_pemusnahan' => 'required|file|mimes:pdf|max:102400',
    ]);

    // PERBAIKAN
    $beritaAcaraFile = $request->file('file_berita_acara');
    $beritaAcaraName = time() . '_ba_' . $beritaAcaraFile->getClientOriginalName();
    $beritaAcara = $beritaAcaraFile->storeAs('berita_acara', $beritaAcaraName, 'public');

    $skFile = $request->file('file_sk_pemusnahan');
    $skName = time() . '_sk_' . $skFile->getClientOriginalName();
    $sk = $skFile->storeAs('sk_pemusnahan', $skName, 'public');

    foreach ($pemusnahan->details as $detail) {
        $arsip = $detail->arsip;
        if ($arsip->status_arsip == 'disetujui_musnah') {
             $arsip->status_arsip = 'musnah';
            $arsip->save();
        }
    }

    $pemusnahan->update([
        'status' => 'dimusnahkan',
        'tanggal_pemusnahan' => now(),
        'file_berita_acara' => $beritaAcara, // SEKARANG TERISI
        'file_sk_pemusnahan' => $sk, // SEKARANG TERISI
    ]);

    return redirect()->route('pemusnahan.riwayat');
}




    public function tambahArsip(Request $request, Pemusnahan $pemusnahan)
    {
        $request->validate([
            'arsip_id' => 'required|array',
            'arsip_id.*' => 'exists:arsips,id',
        ]);

        foreach ($request->arsip_id as $arsipId) {

            // Cegah duplikasi
            PemusnahanDetail::firstOrCreate(
                [
                    'pemusnahan_id' => $pemusnahan->id,
                    'arsip_id'      => $arsipId,
                ],
                [
                    'keputusan' => 'belum_dinilai',
                ]
            );
               Arsip::where('id', $arsipId)
            ->update([
                'status_arsip' => 'DIAJUKAN_MUSNAH',
            ]);
        }

        return back()->with('success', 'Arsip berhasil ditambahkan ke pemusnahan');
    }



    public function hapusArsip(Pemusnahan $pemusnahan, Arsip $arsip)
    {
        PemusnahanDetail::where('pemusnahan_id', $pemusnahan->id)
            ->where('arsip_id', $arsip->id)
            ->delete();
    $arsip->update([
        'status_arsip' => 'INAKTIF',
    ]);
        return back()->with('success', 'Arsip dikeluarkan dari daftar');
    }

public function sidang(Request $request, Pemusnahan $pemusnahan)
{
    $pemusnahan->load('details.arsip');

    $details = $pemusnahan->details;

    if ($request->filled('search')) {
        $details = $details->filter(function ($detail) use ($request) {
            return str_contains(
                strtolower($detail->arsip->uraian_arsip),
                strtolower($request->search)
            );
        });
    }

    if ($request->filled('tahun')) {
        $details = $details->filter(function ($detail) use ($request) {
            return $detail->arsip->tahun_arsip == $request->tahun;
        });
    }

    if ($request->filled('keputusan')) {
        $details = $details->filter(function ($detail) use ($request) {
            return $detail->keputusan == $request->keputusan;
        });
    }

    if ($request->filled('tingkat')) {
        $details = $details->filter(function ($detail) use ($request) {
            return strtolower($detail->arsip->tingkat_perkembangan)
                    == strtolower($request->tingkat);
        });
    }

    $details = $details->values(); // 👈 penting! reset key jadi 0,1,2,...

    return view('pemusnahan.sidang.index', compact('pemusnahan', 'details'));
}

    public function inlineUpdate(Request $request)
    {
        if ($request->model === 'arsip') {
            $detail = PemusnahanDetail::findOrFail($request->id);
            $detail->arsip->{$request->field} = $request->value;
            $detail->arsip->save();
        }

        if ($request->model === 'detail') {
            $detail = PemusnahanDetail::findOrFail($request->id);
            $detail->{$request->field} = $request->value;
            $detail->save();
        }

        return response()->json(['success' => true]);
    }

public function bulkUpdateKeputusan(Request $request, Pemusnahan $pemusnahan)
{
    $request->validate([
        'ids'       => 'required|array|min:1',
        'ids.*'     => 'integer',
        'keputusan' => 'required|in:musnah,inaktif',
    ]);

    // Tidak pakai rule "exists" per-item (mahal untuk ribuan ID).
    // Scoping ke pemusnahan_id di whereIn() otomatis mengabaikan
    // ID yang tidak valid / bukan milik pemusnahan ini.
    $updated = PemusnahanDetail::where('pemusnahan_id', $pemusnahan->id)
        ->whereIn('id', $request->ids)
        ->update(['keputusan' => $request->keputusan]);

    return response()->json([
        'success' => true,
        'updated' => $updated,
    ]);
}

public function kpu(Pemusnahan $pemusnahan)
{
    if (!in_array($pemusnahan->status, ['disetujui_anri', 'menunggu_persetujuan_kpu'])) {
        return back()->with('error', 'Harus melalui ANRI dulu.');
    }

    // 🔥 ambil hanya yang MUSNAH
    $pemusnahan->load(['details' => function ($q) {
        $q->where('keputusan', 'musnah')
          ->with('arsip');
    }]);

    return view('pemusnahan.kpu.index', compact('pemusnahan'));
}


public function simpanKpu(Request $request, Pemusnahan $pemusnahan)
{
    $request->validate([
        'file_persetujuan_kpu' => 'required|file|mimes:pdf|max:102400',
    ]);

    // PERBAIKAN
    $file = $request->file('file_persetujuan_kpu');
    $fileName = time() . '_' . $file->getClientOriginalName();
    $filePath = $file->storeAs('persetujuan_kpu', $fileName, 'public');

    $pemusnahan->update([
        'status' => 'disetujui_kpu',
        'file_persetujuan_kpu' => $filePath, // PERBAIKAN
    ]);

    return redirect()
        ->route('pemusnahan.eksekusi', $pemusnahan->id)
        ->with('success', 'Persetujuan KPU berhasil diupload.');
}

/**
 * ===============================
 * FORM EDIT USULAN (HANYA DRAFT)
 * ===============================
 */
public function edit(Pemusnahan $pemusnahan)
{
    if ($pemusnahan->status !== 'draft') {
        abort(403, 'Hanya pemusnahan berstatus draft yang dapat diedit.');
    }

    return view('pemusnahan.usulan.edit', compact('pemusnahan'));
}

/**
 * ===============================
 * UPDATE USULAN (HANYA DRAFT)
 * ===============================
 */
public function update(Request $request, Pemusnahan $pemusnahan)
{
    if ($pemusnahan->status !== 'draft') {
        abort(403, 'Hanya pemusnahan berstatus draft yang dapat diedit.');
    }

    $request->validate([
        'tahun' => 'required|numeric',
    ]);

    $pemusnahan->update([
        'tahun'      => $request->tahun,
        'keterangan' => $request->keterangan,
    ]);

    return redirect()
        ->route('pemusnahan.usulan.show', $pemusnahan)
        ->with('success', 'Usulan pemusnahan berhasil diperbarui');
}

/**
 * ===============================
 * HAPUS USULAN (HANYA DRAFT)
 * Arsip yang sudah dimasukkan akan
 * dikembalikan status semulanya
 * ===============================
 */
public function destroy(Pemusnahan $pemusnahan)
{
    if ($pemusnahan->status !== 'draft') {
        return back()->with('error', 'Hanya pemusnahan berstatus draft yang dapat dihapus.');
    }

    foreach ($pemusnahan->details as $detail) {
        $arsip = $detail->arsip;

        if ($arsip) {
            $arsip->status_arsip  = 'HABIS_RETENSI'; // kembalikan status semula
            $arsip->pemusnahan_id = null;
            $arsip->save();
        }

        $detail->delete();
    }

    $pemusnahan->delete();

    return redirect()
        ->route('pemusnahan.usulan.index')
        ->with('success', 'Pemusnahan berhasil dihapus dan arsip dikembalikan ke daftar.');
}

}
