<?php

namespace App\Http\Controllers;

use App\Models\SuratMasuk;
use App\Models\SuratInstansi;
use App\Models\TujuanDisposisi;
use App\Models\SinarV1Document;
use App\Models\SinarV1Instansi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\SuratMasukExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Satker;
use Carbon\Carbon;
use App\Models\SubBagian;
use App\Imports\SuratMasukImport;

class SuratMasukController extends Controller
{
    protected array $sortableColumns = [
            'nomor_agenda',
            'tanggal_dokumen',
            'tanggal_penyelesaian',
            'nomor_dokumen',
            'perihal',
            'instansi_satker',
        ];
    
        /**
         * Display a listing of the resource.
         */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->filter;
    
        $query = SuratMasuk::with(['instansi', 'tujuanDisposisis']);
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_dokumen', 'like', "%{$search}%")
                    ->orWhere('nomor_agenda', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%")
                    ->orWhere('instansi_satker', 'like', "%{$search}%");
            });
        }
    
        // Ambil semua nomor dokumen yang duplikat
        $duplicateNomor = SuratMasuk::select('nomor_dokumen')
            ->groupBy('nomor_dokumen')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nomor_dokumen');
    
        // Filter hanya data duplikat
        if ($filter == 'duplikasi') {
            $query->whereIn('nomor_dokumen', $duplicateNomor);
        }
    
      // --- Sorting ---
    $sortColumn = $request->input('sort');
    $sortColumn = in_array($sortColumn, $this->sortableColumns, true) ? $sortColumn : 'nomor_agenda';

    $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';

    if ($sortColumn === 'nomor_agenda') {
        // nomor_agenda varchar, perlu di-cast supaya urut numerik bukan alfabet
        $query->orderByRaw('CAST(SUBSTRING_INDEX(nomor_agenda, "/", 1) AS UNSIGNED) ' . $sortDirection);
    } else {
        $query->orderBy($sortColumn, $sortDirection);
    }

    // Secondary sort: tanggal_dokumen (kecuali memang itu kolom utamanya)
    if ($sortColumn !== 'tanggal_dokumen') {
        $query->orderBy('tanggal_dokumen', $sortDirection);
    }

    $query->orderBy('created_at', 'desc');
        $surat = $query->paginate(10);
    
        // jumlah data yang duplikat
        $jumlahDuplikat = SuratMasuk::whereIn(
            'nomor_dokumen',
            $duplicateNomor
        )->count();
    
        // total masing-masing nomor dokumen
        $duplicateCounts = SuratMasuk::selectRaw(
            'nomor_dokumen, COUNT(*) as total'
        )
            ->groupBy('nomor_dokumen')
            ->pluck('total', 'nomor_dokumen');
    
        $jumlahHistorisV1 = SinarV1Document::where('legacy_category_id', 12)->count();
        $jumlahSudahDiimport = SuratMasuk::whereNotNull('sinar_v1_document_id')->count();
        $jumlahInstansiHistoris = SinarV1Instansi::count();
    
        return view(
            'surat_masuk.index',
            compact(
                'surat',
                'duplicateCounts',
                'jumlahDuplikat'
                , 'jumlahHistorisV1'
                , 'jumlahSudahDiimport'
                , 'jumlahInstansiHistoris'
            )
        );
    }
    /**
     * Show the form for creating a new resource.
     */
  public function create()
{
    $instansis = SuratInstansi::where('aktif', true)->orderBy('nama_instansi')->get();
    $tujuanDisposisis = TujuanDisposisi::where('aktif', true)->orderBy('nama_tujuan')->get();

    $opsiBantuan = [
        'dokumentasi' => 'Dokumentasi/File',
        'hadir_mewakili' => 'Mohon hadir mewakili saya',
        'bicarakan' => 'Membicarakan dengan saya',
        'jawaban' => 'Membuat jawaban/tanggapan',
        'ikut_hadir' => 'Ikut hadir',
        'monitor' => 'Memonitor',
        'konsep' => 'Menyiapkan konsep',
        'diketahui' => 'Diketahui/sbg. Informasi',
        'saran' => 'Mempelajari dan memberikan saran',
        'laksanakan' => 'Melaksanakan/menindaklanjuti',
        'prosedur' => 'Memproses sesuai prosedur',
        'selesai_batas_waktu' => 'Menyelesaikan sebelum batas waktu',
        'koordinasi' => 'Mengkoordinasikan',
    ];

    return view('surat_masuk.create', compact('instansis', 'tujuanDisposisis', 'opsiBantuan'));
}

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
{
    try {

        $request->validate([
            'instansi_id'          => 'required|exists:surat_instansis,id',
            'tujuan_disposisi_ids' => 'nullable|array',
            'tujuan_disposisi_ids.*' => 'distinct|exists:tujuan_disposisis,id',
            'tanggal_dokumen'      => 'required|date',
            'tanggal_penyelesaian' => 'required|date',
            'nomor_dokumen'        => 'required',
            'nomor_agenda'         => 'required',
            'kepada'               => 'required',
            'perihal'              => 'required',
            'sifat'                => 'nullable|string|in:biasa,penting,khusus,batas_waktu',
            'bantuan'              => 'nullable|array',
            'bantuan.*'            => 'string',
            'file_input'           => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
            'submit_action'        => 'nullable|in:save,save_print',
            'allow_duplicate'      => 'nullable|boolean',
        ]);

        $instansi = SuratInstansi::findOrFail($request->instansi_id);
        $duplicate = $this->findPotentialDuplicate($request);
        if ($duplicate && ! $request->boolean('allow_duplicate')) {
            return back()->withInput()->withErrors([
                'duplicate' => 'Surat yang berpotensi sama sudah tercatat. Periksa data tersebut atau centang konfirmasi untuk tetap menyimpan.',
            ])->with('potential_duplicate', $duplicate);
        }

        $fileName = null;

        if ($request->hasFile('file_input')) {

            $fileName = time() . '_' .
                $request->file('file_input')->getClientOriginalName();

            $request->file('file_input')->storeAs(
                'surat_masuk',
                $fileName
            );
        }

        $surat = SuratMasuk::create([
            'sub_bagian_id'        => null,
            'instansi_id'          => $instansi->id,
            'instansi_satker'      => $instansi->nama_instansi,
            'tanggal_dokumen'      => $request->tanggal_dokumen,
            'tanggal_penyelesaian' => $request->tanggal_penyelesaian,
            'nomor_dokumen'        => $request->nomor_dokumen,
            'nomor_agenda'         => $request->nomor_agenda,
            'kepada'               => $request->kepada,
            'perihal'              => $request->perihal,
            'catatan'              => $request->catatan,
            'sifat'                => $request->sifat,
            'bantuan'              => $request->input('bantuan', []),
            'file_input'           => $fileName,
        ]);
        $surat->tujuanDisposisis()->sync($request->input('tujuan_disposisi_ids', []));

        if ($request->input('submit_action') === 'save_print') {
            return redirect()->route('surat-masuk.disposisi', $surat->id);
        }

        return redirect()
            ->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil ditambahkan.');

    } catch (\Exception $e) {

        Log::error('Gagal simpan surat masuk: ' . $e->getMessage());

        return back()
            ->withInput()
            ->with('error', 'Data gagal disimpan!');
    }
}

    public function checkPotentialDuplicate(Request $request)
    {
        $request->validate([
            'instansi_id' => 'required|exists:surat_instansis,id',
            'tanggal_dokumen' => 'required|date',
            'nomor_dokumen' => 'required|string|max:255',
        ]);

        $duplicate = $this->findPotentialDuplicate($request);

        return response()->json([
            'duplicate' => (bool) $duplicate,
            'data' => $duplicate ? [
                'id' => $duplicate->id,
                'nomor_agenda' => $duplicate->nomor_agenda,
                'nomor_dokumen' => $duplicate->nomor_dokumen,
                'tanggal_dokumen' => optional($duplicate->tanggal_dokumen)->format('d-m-Y'),
                'instansi' => $duplicate->instansi_satker,
                'perihal' => $duplicate->perihal,
                'detail_url' => route('surat-masuk.show', $duplicate->id),
            ] : null,
        ]);
    }

    private function findPotentialDuplicate(Request $request): ?SuratMasuk
    {
        return SuratMasuk::where('instansi_id', $request->input('instansi_id'))
            ->whereDate('tanggal_dokumen', $request->input('tanggal_dokumen'))
            ->whereRaw('LOWER(TRIM(nomor_dokumen)) = ?', [mb_strtolower(trim((string) $request->input('nomor_dokumen')))])
            ->first();
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $surat = SuratMasuk::with(['subBagian', 'instansi', 'tujuanDisposisis'])->findOrFail($id);

        return view('surat_masuk.show', compact('surat'));
    }

    /**
     * Show the form for editing the specified resource.
     */
  public function edit($id)
{
    $surat = SuratMasuk::findOrFail($id);

    $instansis = SuratInstansi::where('aktif', true)
        ->when($surat->instansi_id, fn ($query, $instansiId) => $query->orWhere('id', $instansiId))
        ->orderBy('nama_instansi')
        ->get();
    $tujuanDisposisis = TujuanDisposisi::where('aktif', true)
        ->orWhereIn('id', $surat->tujuanDisposisis()->pluck('tujuan_disposisis.id'))
        ->orderBy('nama_tujuan')->get();

    $opsiBantuan = [
        'dokumentasi' => 'Dokumentasi/File',
        'hadir_mewakili' => 'Mohon hadir mewakili saya',
        'bicarakan' => 'Membicarakan dengan saya',
        'jawaban' => 'Membuat jawaban/tanggapan',
        'ikut_hadir' => 'Ikut hadir',
        'monitor' => 'Memonitor',
        'konsep' => 'Menyiapkan konsep',
        'diketahui' => 'Diketahui/sbg. Informasi',
        'saran' => 'Mempelajari dan memberikan saran',
        'laksanakan' => 'Melaksanakan/menindaklanjuti',
        'prosedur' => 'Memproses sesuai prosedur',
        'selesai_batas_waktu' => 'Menyelesaikan sebelum batas waktu',
        'koordinasi' => 'Mengkoordinasikan',
    ];

    $bantuanTerpilih = old('bantuan', $surat->bantuan ?? []);

    return view('surat_masuk.edit', compact(
        'surat', 'instansis', 'tujuanDisposisis', 'opsiBantuan', 'bantuanTerpilih'
    ));
}

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, $id)
    // {
    //     $surat = SuratMasuk::findOrFail($id);

    //     $request->validate([
    //         'instansi_id'          => 'required|exists:surat_instansis,id',
    //         'tujuan_disposisi_ids' => 'nullable|array',
    //         'tujuan_disposisi_ids.*' => 'distinct|exists:tujuan_disposisis,id',
    //         'tanggal_dokumen'      => 'required|date',
    //         'tanggal_penyelesaian' => 'required|date',
    //         'nomor_dokumen'        => 'required',
    //         'nomor_agenda'         => 'required',
    //         'kepada'               => 'required',
    //         'perihal'              => 'required',
    //         'file_input'           => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
    //     ]);

    //     $instansi = SuratInstansi::findOrFail($request->instansi_id);

    //     $fileName = $surat->file_input;

    //     if ($request->hasFile('file_input')) {

    //         if ($surat->file_input) {
    //             Storage::delete('surat_masuk/' . $surat->file_input);
    //         }

    //         $fileName = time() . '_' .
    //             $request->file('file_input')->getClientOriginalName();

    //         $request->file('file_input')->storeAs(
    //             'surat_masuk',
    //             $fileName
    //         );
    //     }

    //     $surat->update([
    //         'instansi_id'          => $instansi->id,
    //         'instansi_satker'      => $instansi->nama_instansi,
    //         'tanggal_dokumen'      => $request->tanggal_dokumen,
    //         'tanggal_penyelesaian' => $request->tanggal_penyelesaian,
    //         'nomor_dokumen'        => $request->nomor_dokumen,
    //         'nomor_agenda'         => $request->nomor_agenda,
    //         'kepada'               => $request->kepada,
    //         'perihal'              => $request->perihal,
    //         'catatan'              => $request->catatan,
    //         'file_input'           => $fileName,
    //     ]);
    //     $surat->tujuanDisposisis()->sync($request->input('tujuan_disposisi_ids', []));

    //     return redirect()
    //         ->route('surat-masuk.index')
    //         ->with('success', 'Surat masuk berhasil diperbarui.');
    // }

    public function update(Request $request, $id)
{
    $surat = SuratMasuk::findOrFail($id);

    $request->validate([
        'instansi_id'          => 'required|exists:surat_instansis,id',
        'tujuan_disposisi_ids' => 'nullable|array',
        'tujuan_disposisi_ids.*' => 'distinct|exists:tujuan_disposisis,id',
        'tanggal_dokumen'      => 'required|date',
        'tanggal_penyelesaian' => 'required|date',
        'nomor_dokumen'        => 'required',
        'nomor_agenda'         => 'required',
        'kepada'               => 'required',
        'perihal'              => 'required',
        'sifat'                => 'nullable|string|in:biasa,penting,khusus,batas_waktu',
        'bantuan'              => 'nullable|array',
        'bantuan.*'            => 'string',
        'file_input'           => 'nullable|mimes:pdf,jpg,jpeg,png|max:2048',
    ]);

    $instansi = SuratInstansi::findOrFail($request->instansi_id);

    $fileName = $surat->file_input;

    if ($request->hasFile('file_input')) {

        if ($surat->file_input) {
            Storage::delete('surat_masuk/' . $surat->file_input);
        }

        $fileName = time() . '_' .
            $request->file('file_input')->getClientOriginalName();

        $request->file('file_input')->storeAs(
            'surat_masuk',
            $fileName
        );
    }

    $surat->update([
        'instansi_id'          => $instansi->id,
        'instansi_satker'      => $instansi->nama_instansi,
        'tanggal_dokumen'      => $request->tanggal_dokumen,
        'tanggal_penyelesaian' => $request->tanggal_penyelesaian,
        'nomor_dokumen'        => $request->nomor_dokumen,
        'nomor_agenda'         => $request->nomor_agenda,
        'kepada'               => $request->kepada,
        'perihal'              => $request->perihal,
        'catatan'              => $request->catatan,
        'sifat'                => $request->sifat,
        'bantuan'              => $request->input('bantuan', []),
        'file_input'           => $fileName,
    ]);
    $surat->tujuanDisposisis()->sync($request->input('tujuan_disposisi_ids', []));

    return redirect()
        ->route('surat-masuk.index')
        ->with('success', 'Surat masuk berhasil diperbarui.');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $surat = SuratMasuk::findOrFail($id);

        if ($surat->file_input) {
            Storage::delete('surat_masuk/' . $surat->file_input);
        }

        $surat->delete();

        return redirect()
            ->route('surat-masuk.index')
            ->with('success', 'Surat masuk berhasil dihapus.');
    }

    /**
     * Disposisi surat
     */
//    public function disposisi($id)
//     {
//         $surat = SuratMasuk::with('tujuanDisposisis')->findOrFail($id);

//         return view('surat_masuk.disposisi_preview', compact('surat'));
//     }

//     public function disposisiPdf($id)
//     {
//         $surat = SuratMasuk::with('tujuanDisposisis')->findOrFail($id);

//         $pdf = Pdf::loadView('surat_masuk.disposisi', compact('surat'));

//         return $pdf->stream('disposisi-surat-'.$surat->id.'.pdf');
//     }

public function disposisi($id)
{
    $surat = SuratMasuk::with('tujuanDisposisis')->findOrFail($id);
    $semuaTujuan = TujuanDisposisi::where('aktif', true)->orderBy('id')->get();

    return view('surat_masuk.disposisi_preview', compact('surat', 'semuaTujuan'));
}

public function disposisiPdf($id)
{
    $surat = SuratMasuk::with('tujuanDisposisis')->findOrFail($id);
    $semuaTujuan = TujuanDisposisi::where('aktif', true)->orderBy('id')->get();

    $pdf = Pdf::loadView('surat_masuk.disposisi', compact('surat', 'semuaTujuan'));

    return $pdf->stream('disposisi-surat-'.$surat->id.'.pdf');
}

    public function export()
{
    return Excel::download(
        new SuratMasukExport,
        'surat_masuk.xlsx'
    );
}
public function import(Request $request)
{
    $request->validate([
        'file' => [
            'required',
            'file',
            'mimes:xlsx,xls',
            'max:102400',
        ],
    ], [
        'file.required' => 'File Excel wajib dipilih.',
        'file.file' => 'File yang diupload tidak valid.',
        'file.mimes' => 'File harus berformat XLS atau XLSX.',
        'file.max' => 'Ukuran file maksimal 100 MB.',
    ]);

    DB::beginTransaction();

    try {

        $import = new SuratMasukImport();

        /*
        |--------------------------------------------------------------------------
        | Baca seluruh data Excel untuk validasi
        |--------------------------------------------------------------------------
        */

        $rows = Excel::toArray($import, $request->file('file'));

        if (empty($rows) || empty($rows[0])) {
            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak memiliki data.',
            ], 422);
        }

       // Ambil sheet pertama
// Catatan: SuratMasukImport implements WithStartRow (startRow() = 4),
// jadi Excel::toArray() SUDAH otomatis mulai baca dari baris ke-4.
// Tidak perlu array_slice lagi di sini.
$data = $rows[0];

// Buang baris yang benar-benar kosong
$dataRows = array_values(array_filter($data, function ($row) {
    return collect($row)->filter(function ($value) {
        return $value !== null && trim((string) $value) !== '';
    })->isNotEmpty();
}));
        if (empty($dataRows)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data surat yang ditemukan mulai dari baris ke-4.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi seluruh baris
        |--------------------------------------------------------------------------
        */

        $import->validateExcel($dataRows);

        if (!empty($import->errors)) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terdapat kesalahan pada file Excel.',
                'errors' => $import->errors,
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan data
        |--------------------------------------------------------------------------
        */

        $jumlahBerhasil = 0;

        foreach ($dataRows as $row) {

            $subBagian = null;

            if (!empty($row[8])) {
                $subBagian = SubBagian::where(
                    'nama_sub_bagian',
                    trim($row[8])
                )->first();
            }

            SuratMasuk::create([
                'nomor_agenda' => trim($row[0] ?? ''),

                'tanggal_dokumen' => !empty($row[1])
                    ? Carbon::parse($row[1])->format('Y-m-d')
                    : null,

                'tanggal_penyelesaian' => !empty($row[2])
                    ? Carbon::parse($row[2])->format('Y-m-d')
                    : null,

                'nomor_dokumen' => trim($row[3] ?? ''),

                'kepada' => trim($row[4] ?? ''),

                'perihal' => trim($row[5] ?? ''),

                'instansi_satker' => trim($row[6] ?? ''),

                'catatan' => !empty($row[7])
                    ? trim($row[7])
                    : null,

                'sub_bagian_id' => $subBagian?->id,
            ]);

            $jumlahBerhasil++;
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Import berhasil. {$jumlahBerhasil} data surat masuk berhasil diimport.",
            'jumlah' => $jumlahBerhasil,
        ]);

    } catch (\Throwable $e) {

        DB::rollBack();

        Log::error('Import Surat Masuk gagal', [
            'exception' => $e,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Import gagal: ' . $e->getMessage(),
        ], 500);
    }
}
// public function import(Request $request)
// {
//     $validated = $request->validate([
//         'confirmation' => 'accepted',
//         'after_id' => ['nullable', 'integer', 'min:0'],
//     ]);

//     $stats = ['instansi_baru' => 0, 'surat_baru' => 0, 'surat_diperbarui' => 0, 'file_disalin' => 0];
//     $afterId = (int) ($validated['after_id'] ?? 0);
//     $batchSize = 50;

//     try {
//         $documents = SinarV1Document::where('legacy_category_id', 12)
//             ->where('id', '>', $afterId)
//             ->orderBy('id')
//             ->limit($batchSize)
//             ->get();

//         DB::transaction(function () use (&$stats, $afterId, $documents) {
//             // Master instansi cukup disinkronkan pada batch pertama.
//             if ($afterId === 0) {
//                 SinarV1Instansi::orderBy('id')->chunk(200, function ($items) use (&$stats) {
//                     foreach ($items as $legacy) {
//                         $namaInstansi = trim((string) $legacy->nama_instansi);
//                         if ($namaInstansi === '') {
//                             continue;
//                         }

//                         $instansi = SuratInstansi::firstOrNew(['nama_instansi' => $namaInstansi]);
//                         $isNew = ! $instansi->exists;
//                         foreach (['alamat', 'telepon', 'fax', 'email', 'website'] as $field) {
//                             if ($legacy->{$field} !== null && $legacy->{$field} !== '') {
//                                 $instansi->{$field} = $legacy->{$field};
//                             }
//                         }
//                         $instansi->aktif = true;
//                         $instansi->created_by ??= auth()->id();
//                         $instansi->save();
//                         $stats['instansi_baru'] += $isNew ? 1 : 0;
//                     }
//                 });
//             }

//             foreach ($documents as $legacy) {
//                 $namaInstansi = trim($legacy->instansi_satker ?: 'Instansi tidak tercatat');
//                 $instansi = SuratInstansi::firstOrCreate(
//                     ['nama_instansi' => $namaInstansi],
//                     ['aktif' => true, 'created_by' => auth()->id()]
//                 );

//                 $surat = SuratMasuk::firstOrNew(['sinar_v1_document_id' => $legacy->id]);
//                 $isNew = ! $surat->exists;
//                 $fileName = $surat->file_input;
//                 if ($legacy->file_path && Storage::disk('local')->exists($legacy->file_path)) {
//                     $fileName = 'sinar_v1_'.$legacy->id.'_'.basename($legacy->file_name_original ?: $legacy->file_path);
//                     $targetPath = 'surat_masuk/'.$fileName;

//                     if (! Storage::disk('public')->exists($targetPath)) {
//                         $stream = Storage::disk('local')->readStream($legacy->file_path);
//                         if ($stream === false) {
//                             throw new \RuntimeException('Lampiran tidak dapat dibaca: '.$legacy->file_path);
//                         }

//                         try {
//                             Storage::disk('public')->writeStream($targetPath, $stream);
//                         } finally {
//                             if (is_resource($stream)) {
//                                 fclose($stream);
//                             }
//                         }
//                         $stats['file_disalin']++;
//                     }
//                 }

//                 $tanggalDokumen = $legacy->tanggal_dokumen ?: $legacy->tanggal_penyelesaian ?: $legacy->legacy_created_at?->toDateString() ?: now()->toDateString();
//                 $surat->fill([
//                     'sub_bagian_id' => $legacy->sub_bagian_id,
//                     'instansi_id' => $instansi->id,
//                     'instansi_satker' => $instansi->nama_instansi,
//                     'tanggal_dokumen' => $tanggalDokumen,
//                     'tanggal_penyelesaian' => $legacy->tanggal_penyelesaian ?: $tanggalDokumen,
//                     'nomor_dokumen' => $legacy->nomor_dokumen ?: 'TANPA-NOMOR-V1-'.$legacy->legacy_id,
//                     'nomor_agenda' => $legacy->nomor_agenda ?: 'V1-'.$legacy->legacy_id,
//                     'kepada' => $legacy->kepada ?: optional(Satker::aktif())->nama_satker ?? 'KPU Provinsi Bali',
//                     'perihal' => $legacy->perihal ?: 'Surat Masuk SINAR V1',
//                     'catatan' => $legacy->catatan,
//                     'file_input' => $fileName,
//                 ])->save();

//                 $stats[$isNew ? 'surat_baru' : 'surat_diperbarui']++;
//             }
//         });

//         $nextCursor = (int) ($documents->last()?->id ?? $afterId);
//         $hasMore = SinarV1Document::where('legacy_category_id', 12)
//             ->where('id', '>', $nextCursor)
//             ->exists();

//         return response()->json([
//             'success' => true,
//             'done' => ! $hasMore,
//             'next_cursor' => $nextCursor,
//             'processed' => $documents->count(),
//             'total' => SinarV1Document::where('legacy_category_id', 12)->count(),
//             'stats' => $stats,
//         ]);
//     } catch (\Throwable $e) {
//         Log::error('Import Surat Masuk SINAR V1 gagal', ['exception' => $e]);
//         return response()->json([
//             'success' => false,
//             'message' => 'Import gagal: '.$e->getMessage(),
//         ], 500);
//     }
// }

public function cekDuplikasi()
{
    $duplicates = SuratMasuk::select(
            'nomor_dokumen',
            'tanggal_dokumen',
            DB::raw('COUNT(*) as total')
        )
        ->groupBy(
            'nomor_dokumen',
            'tanggal_dokumen'
        )
        ->having('total', '>', 1)
        ->get();

    $duplicateData = [];

    foreach ($duplicates as $duplicate) {

        $surats = SuratMasuk::with('subBagian')
            ->where('nomor_dokumen', $duplicate->nomor_dokumen)
            ->whereDate(
                'tanggal_dokumen',
                $duplicate->tanggal_dokumen
            )
            ->get();

        $duplicateData[] = [
            'nomor_dokumen' => $duplicate->nomor_dokumen,
            'tanggal_dokumen' => $duplicate->tanggal_dokumen,
            'total' => $duplicate->total,
            'data' => $surats
        ];
    }

    return view(
        'surat_masuk.cek_duplikasi',
        compact('duplicateData')
    );
}


}
