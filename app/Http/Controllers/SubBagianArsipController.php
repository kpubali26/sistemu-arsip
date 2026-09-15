<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Arsip;
use App\Models\KodeKlasifikasi;
use App\Models\SubBagian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ArsipImportSubBagian;
use App\Exports\ArsipExport;
use Carbon\Carbon;
use App\Models\BeritaAcaraPindah;
use App\Models\BeritaAcaraDetail;
use Illuminate\Support\Facades\DB;
use App\Models\MasterRak;
use App\Models\MasterBox;


class SubBagianArsipController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $showAllStatus = $request->filter === 'belum_dokumen';

        $query = Arsip::with(['kodeKlasifikasi', 'subBagian'])
            ->where('sub_bagian_id', $user->sub_bagian_id);

        if (!$showAllStatus) {
            $query->where('status_pindah', 'BELUM');
        }

        $query->where(function ($q) use ($user) {
        $q->where('klasifikasi_keamanan', '!=', 'Rahasia');
        $q->orWhere(function ($sub) use ($user) {

        $sub->where('klasifikasi_keamanan', 'Rahasia');

               
        if (
            $user->isAdmin() ||
            $user->isSuperAdmin() ||
            $user->isTu()
        ) {
            return;
        }

        // user hanya miliknya sendiri
        $sub->where('created_by', $user->id);
        });

        });

        // if ($request->has('sort')) {
        //     $sort = $request->sort;
        //     $direction = $request->direction ?? 'asc';
            
        //     // Handle sorting untuk relasi
        //     if ($sort == 'rak.nomor_rak') {
        //         $query->join('master_raks', 'arsips.rak_id', '=', 'master_raks.id')
        //             ->orderBy('master_raks.nomor_rak', $direction)
        //             ->select('arsips.*');
        //     } elseif ($sort == 'box.nomor_box') {
        //         $query->join('master_box', 'arsips.box_id', '=', 'master_box.id')
        //             ->orderBy('master_box.nomor_box', $direction)
        //             ->select('arsips.*');
        //     }
        // }

        $tahunOptions = Arsip::select('tahun_arsip')
            ->distinct()
            ->orderBy('tahun_arsip', 'desc')
            ->pluck('tahun_arsip');

        $subBagianOptions = SubBagian::select('id', 'nama_sub_bagian as nama_sub_bagian')
            ->orderBy('nama_sub_bagian')
            ->get();

        $bapOptions = BeritaAcaraPindah::where('sub_bagian_id', $user->sub_bagian_id)
            ->where('status', 'DRAFT') // <-- GANTI dari DIAJUKAN ke DRAFT
            ->orderBy('tanggal_bap', 'desc')
            ->get();
       if ($request->filter === 'tanpa_ruangan') {
            $query->where('sub_bagian_id', $user->sub_bagian_id)
                ->where(function ($q) {
                    $q->whereNull('rak_id')
                        ->orWhereNull('box_id');
                })
                ->whereIn('status_pindah', ['BELUM']);
        }
        if ($request->filter === 'belum_dokumen') {
            $query->whereIn('status_pindah', [
                'BELUM',
                'DIAJUKAN',
                'DIPINDAHKAN'
            ]);

            $query->where('status_arsip', '!=', 'NON_ARSIP');

            $query->where(function ($q) {
                $q->whereNull('file_dokumen')
                ->orWhere('file_dokumen', '');
            });

            $query->where(function ($q) {
                $q->whereNull('link_foto')
                ->orWhere('link_foto', '');
            });
        }
        // Filter & search sama seperti ArsipController
        if ($request->has('status_arsip') && $request->status_arsip != '') {
            $query->where('status_arsip', $request->status_arsip);
        }
        if ($request->has('tahun_arsip') && $request->tahun_arsip != '') {
            $query->where('tahun_arsip', $request->tahun_arsip);
        }

        if ($request->filled('triwulan')) {
            $triwulanRanges = [
                '1' => [1, 3],   // Jan - Mar
                '2' => [4, 6],   // Apr - Jun
                '3' => [7, 9],   // Jul - Sep
                '4' => [10, 12], // Okt - Des
            ];

            if (isset($triwulanRanges[$request->triwulan])) {
                [$startMonth, $endMonth] = $triwulanRanges[$request->triwulan];
                $query->whereMonth('tanggal_arsip', '>=', $startMonth)
                    ->whereMonth('tanggal_arsip', '<=', $endMonth);
            }
        }
        if ($request->has('kode_klasifikasi_id') && $request->kode_klasifikasi_id != '') {
            $query->where('kode_klasifikasi_id', $request->kode_klasifikasi_id);
        }
        if ($request->filled('rak_id')) {
            $query->where('rak_id', $request->rak_id);
        }
        if ($request->filled('box_id')) {
            $query->where('box_id', $request->box_id);
        }
        if ($request->has('keterangan') && $request->keterangan != '') {
            $query->where('keterangan', $request->keterangan);
        }
        if ($request->has('keterangan_jra') && $request->keterangan_jra != '') {
            $query->where('keterangan_jra', $request->keterangan_jra);
        }
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('uraian_arsip', 'like', "%{$request->search}%")
                  ->orWhereHas('kodeKlasifikasi', function($sub) use ($request){
                      $sub->where('kode','like',"%{$request->search}%")
                          ->orWhere('uraian','like',"%{$request->search}%");
                  });
            });
        }

        // Filter duplikat
        if ($request->show_duplicates == 1) {

            DB::table('arsips')
                ->where('sub_bagian_id', $user->sub_bagian_id)
                ->update([
                    'is_duplicate' => 0,
                    'duplicate_reason' => null
                ]);

            $duplicateGroups = DB::table('arsips')
                ->selectRaw("
                    LOWER(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        TRIM(uraian_arsip),
                                    ' ', ''),
                                '\n', ''),
                            '\r', ''),
                        '\t', '')
                    ) AS cleaned_uraian,
                    tahun_arsip,
                    tingkat_perkembangan,
                    COUNT(*) AS total
                ")
                ->where('sub_bagian_id', $user->sub_bagian_id)
                ->where('status_pindah', '!=', 'NON_ARSIP')
                ->groupByRaw("
                    LOWER(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        TRIM(uraian_arsip),
                                    ' ', ''),
                                '\n', ''),
                            '\r', ''),
                        '\t', '')
                    ),
                    tahun_arsip,
                    tingkat_perkembangan
                ")
                ->having('total', '>', 1)
                ->get();

            foreach ($duplicateGroups as $group) {

                DB::table('arsips')
                    ->where('sub_bagian_id', $user->sub_bagian_id)
                    ->where('status_pindah', '!=', 'NON_ARSIP')
                    ->where('tahun_arsip', $group->tahun_arsip)
                    ->where('tingkat_perkembangan', $group->tingkat_perkembangan)
                    ->whereRaw("
                        LOWER(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            TRIM(uraian_arsip),
                                        ' ', ''),
                                    '\n', ''),
                                '\r', ''),
                            '\t', '')
                        ) = ?
                    ", [$group->cleaned_uraian])
                    ->update([
                        'is_duplicate' => 1,
                        'duplicate_reason' => 'Duplikat otomatis'
                    ]);
            }

            $query->where('is_duplicate', 1);
        }


        // ===== SORTING (pindah ke sini, SEBELUM paginate) =====
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $query->reorder();

        if ($sort === 'kode_klasifikasi') {
            $query->leftJoin('kode_klasifikasis', 'arsips.kode_klasifikasi_id', '=', 'kode_klasifikasis.id')
                ->orderBy('kode_klasifikasis.kode', $direction)
                ->select('arsips.*');
        } elseif ($sort === 'rak.nomor_rak' || $sort === 'nomor_rak') {
            $query->leftJoin('master_raks', 'arsips.rak_id', '=', 'master_raks.id')
                ->orderBy('master_raks.nomor_rak', $direction)
                ->select('arsips.*');
        } elseif ($sort === 'box.nomor_box' || $sort === 'nomor_box') {
            $query->leftJoin('master_box', 'arsips.box_id', '=', 'master_box.id')
                ->orderBy('master_box.nomor_box', $direction)
                ->select('arsips.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $perPageInput = $request->get('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50, 100];

        if ($perPageInput === 'all') {
            // Hitung total data sesuai filter yang aktif, lalu jadikan itu sebagai perPage
            $perPage = (clone $query)->count();
            $perPage = $perPage > 0 ? $perPage : 1; // hindari paginate(0)
        } elseif (in_array((int) $perPageInput, $allowedPerPage)) {
            $perPage = (int) $perPageInput;
        } else {
            $perPage = 15;
        }

        // $arsips = $query->orderBy('id','desc')->paginate($perPage)->withQueryString();
        if (!$request->has('sort')) {
            $query->orderBy('id', 'desc');
        }

        $arsips = $query->paginate($perPage)->withQueryString();

        // Filter dropdown options
        $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
        $statusOptions = ['AKTIF'=>'Aktif','INAKTIF'=>'Inaktif','HABIS_RETENSI'=>'HABIS RETENSI','MUSNAH'=>'Musnah','PERMANEN'=>'Permanen'];
        $kondisiOptions = ['BAIK'=>'Baik','RUSAK'=>'Rusak','HILANG'=>'Hilang'];
        $keteranganJraOptions = ['MUSNAH'=>'Musnah','PERMANEN'=>'Permanen'];
        $lokasi = $this->getLokasiArsip($user);

        $rakOptions = MasterRak::where('lokasi_arsip', $lokasi)
            ->orderBy('nomor_rak')
            ->get(['id', 'nomor_rak']);

        $boxOptions = MasterBox::whereIn('rak_id', $rakOptions->pluck('id'))
            ->orderBy('nomor_box')
            ->get(['id', 'nomor_box', 'rak_id']);
        return view('subbagian.arsip.index', compact(
            'arsips','kodeKlasifikasiOptions','statusOptions','kondisiOptions','keteranganJraOptions', 'tahunOptions','subBagianOptions', 'bapOptions','rakOptions','boxOptions'
        ));
    }

    private function authorizeSubBagianArsip(Arsip $arsip)
    {
        $user = Auth::user();

        if ($arsip->sub_bagian_id != $user->sub_bagian_id) {
            abort(403);
        }

        if (!$user->canViewArsip($arsip)) {
            abort(403);
        }
    }

 public function create()
{
    $user = Auth::user();
    $lokasi = $this->getLokasiArsip($user);
    $lokasiLabel = $this->getLabelLokasi($lokasi);

    $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
    $subBagianOptions = SubBagian::orderBy('nama_sub_bagian')->get();

    $rakOptions = MasterRak::where('lokasi_arsip', $lokasi)
        ->orderBy('nomor_rak')
        ->get(['id', 'nomor_rak', 'lokasi_arsip']);

    $boxOptions = MasterBox::whereIn('rak_id', $rakOptions->pluck('id'))
        ->orderBy('nomor_box')
        ->get(['id', 'nomor_box', 'rak_id']);

    $defaultValues = [
        'status_arsip' => 'AKTIF',
        'jumlah_berkas' => 1,
        'satuan_arsip' => 'LEMBAR',
        'tahun_arsip' => date('Y'),
        'tanggal_arsip' => date('Y-m-d'),
        'keterangan_jra' => 'MUSNAH',
    ];

    return view('subbagian.arsip.create', compact(
        'kodeKlasifikasiOptions',
        'subBagianOptions',
        'rakOptions',
        'boxOptions',
        'defaultValues',
        'lokasi',
        'lokasiLabel'
    ));
}

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'kode_klasifikasi_id'=>'required|exists:kode_klasifikasis,id',
            'uraian_arsip'=>'required|string|min:30',
            'tahun_arsip' => [
                'required',
                'integer',
                'min:2000',
                'max:' . (date('Y') + 1),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tanggal_arsip')) {
                        $tahunTanggal = \Carbon\Carbon::parse($request->tanggal_arsip)->year;
                        if ((int) $value !== $tahunTanggal) {
                            $fail("Tahun arsip ({$value}) harus sama dengan tahun pada tanggal arsip ({$tahunTanggal}).");
                        }
                    }
                },
            ],
            'tanggal_arsip'=>'required|date',
            'jumlah_berkas'=>'required|integer|min:1',
            'satuan_arsip'=>'required|in:BENDEL,LEMBAR',
            'klasifikasi_keamanan' => 'required|in:Biasa/Terbuka,Terbatas,Rahasia',

            // 'aktif_tahun'=>'nullable|string|max:100',
            // 'inaktif_tahun'=>'nullable|string|max:100',
            // 'tanggal_referensi'=>'nullable|date',
            // 'keterangan_jra'=>'nullable|in:PERMANEN,MUSNAH',
             'rak_id' => 'required|exists:master_raks,id',
            'box_id' => 'required|exists:master_box,id',
            'nomor_sampul'=>'nullable|string|max:100',
            'lokasi_arsip' => 'required|string|max:100',
            'tingkat_perkembangan'=>'nullable|in:ASLI,COPY,SALINAN',
            'keterangan'=>'nullable|in:BAIK,RUSAK,HILANG',
            'media_arsip'=>'nullable|string|max:255',
            'file_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:102400',
            'link_foto'    => 'nullable|url|max:1000',
        ]);

        $validated['sub_bagian_id'] = $user->sub_bagian_id;
        $validated['created_by'] = $user->id;
        $validated['tanggal_masuk'] = now()->format('Y-m-d');
        $validated['status_pindah'] = 'BELUM';
        // $validated['lokasi_arsip'] = 'SUB_BAGIAN';

        $user = auth()->user();

        $namaSub = $user->subBagian->nama_sub_bagian ?? null;

        $mapLokasi = [
            'Sub Bagian Keuangan, Umum dan Logistik' => 'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK',
            'Sub Bagian Partisipasi, Hubungan Masyarakat dan SDM' => 'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM',
            'Sub Bagian Perencanaan, Data, dan Informasi' => 'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI',
            'Sub Bagian Teknis Penyelenggaraan Pemilu dan Hukum' => 'RUANG_SUBBAGIAN_TEKNIS_HUKUM',
        ];

        $validated['lokasi_arsip'] = $mapLokasi[$namaSub] ?? null;
        $validated['link_foto'] = $request->link_foto;

        if($request->hasFile('file_dokumen')){
            $file=$request->file('file_dokumen');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->storeAs('arsip',$fileName,'public');
            $validated['file_dokumen'] = $fileName;
        }

        // $perhitungan = (new \App\Http\Controllers\ArsipController)->hitungRetensi(
        //     $validated['aktif_tahun'] ?? null,
        //     $validated['inaktif_tahun'] ?? null,
        //     $validated['keterangan_jra'],
        //     $validated['tanggal_arsip'],
        //     $validated['tanggal_referensi'] ?? null
        // );

        // $validated['aktif_sampai']=$perhitungan['aktif_sampai'];
        // $validated['inaktif_sampai']=$perhitungan['inaktif_sampai'];
        // $validated['status_arsip']=$perhitungan['status_arsip'];

        Arsip::create($validated);

        return redirect()->route('subbagian.arsip.index')->with('success','Arsip berhasil ditambahkan.');
    }

  public function edit(Arsip $arsip)
{
    $this->authorizeSubBagianArsip($arsip);

    $user = Auth::user();
    $lokasi = $this->getLokasiArsip($user);
    $lokasiLabel = $this->getLabelLokasi($lokasi);

    $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
    $subBagianOptions = SubBagian::orderBy('nama_sub_bagian')->get();

    $rakOptions = MasterRak::where('lokasi_arsip', $lokasi)
        ->orderBy('nomor_rak')
        ->get(['id', 'nomor_rak', 'lokasi_arsip']);

    $boxOptions = MasterBox::whereIn('rak_id', $rakOptions->pluck('id'))
        ->orderBy('nomor_box')
        ->get(['id', 'nomor_box', 'rak_id']);

    return view('subbagian.arsip.edit', compact(
        'arsip',
        'kodeKlasifikasiOptions',
        'subBagianOptions',
        'rakOptions',
        'boxOptions',
        'lokasi',
        'lokasiLabel'
    ));
}

    public function show(Request $request, Arsip $arsip)
    {
        $user = Auth::user();

if (!$user->canViewArsip($arsip)) {
    abort(403);
}
        $this->authorizeSubBagianArsip($arsip);

        return view('subbagian.arsip.show', [
            'arsip' => $arsip,
            'returnUrl' => $request->get('return')
        ]);
    }

    public function downloadFile(Arsip $arsip)
    {
        $this->authorizeSubBagianArsip($arsip);

        if (!$arsip->file_dokumen) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        if (!Auth::user()->canDownloadArsip($arsip)) {
            abort(403, 'Anda tidak memiliki akses untuk mengunduh file ini.');
        }

        $path = 'arsip/' . $arsip->file_dokumen;
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return Storage::disk('public')->download($path);
    }

    public function update(Request $request, Arsip $arsip)
    {
         $this->authorizeSubBagianArsip($arsip);

    $user = Auth::user();
        $this->authorizeSubBagianArsip($arsip);

        $validated = $request->validate([
            'kode_klasifikasi_id'=>'required|exists:kode_klasifikasis,id',
            'uraian_arsip'=>'required|string|min:30',
            'tahun_arsip' => [
                'required',
                'integer',
                'min:2000',
                'max:' . (date('Y') + 1),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tanggal_arsip')) {
                        $tahunTanggal = \Carbon\Carbon::parse($request->tanggal_arsip)->year;
                        if ((int) $value !== $tahunTanggal) {
                            $fail("Tahun arsip ({$value}) harus sama dengan tahun pada tanggal arsip ({$tahunTanggal}).");
                        }
                    }
                },
            ],
            'tanggal_arsip'=>'required|date',
            'jumlah_berkas'=>'required|integer|min:1',
            'satuan_arsip'=>'required|in:BENDEL,LEMBAR',
            'klasifikasi_keamanan' => 'required|in:Biasa/Terbuka,Terbatas,Rahasia',

            // 'aktif_tahun'=>'required|string|max:100',
            // 'inaktif_tahun'=>'required|string|max:100',
            // 'tanggal_referensi'=>'nullable|date',
            // 'keterangan_jra'=>'required|in:PERMANEN,MUSNAH',
            'rak_id' => 'required|exists:master_raks,id',
        'box_id' => 'required|exists:master_box,id',
            'nomor_sampul'=>'nullable|string|max:100',
            'lokasi_arsip' => 'required|string|max:100',
            'tingkat_perkembangan'=>'nullable|in:ASLI,COPY,SALINAN',
            'keterangan'=>'nullable|in:BAIK,RUSAK,HILANG',
            'media_arsip'=>'nullable|string|max:255',
           'file_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:102400',
            'hapus_file'=>'nullable|in:0,1',
            'tangani_duplikat' => 'nullable|in:1',
            'duplicate_reason' => 'nullable|string|max:1000',
               'link_foto' => 'nullable|url|max:1000',
        ]);
        $validated['lokasi_arsip'] = $this->getLokasiArsip($user);
         $validated['link_foto'] = $request->link_foto;
        if(($request->hapus_file??'0')=='1' && $arsip->file_dokumen){
            Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
            $validated['file_dokumen']=null;
        }

        if($request->hasFile('file_dokumen')){
            if($arsip->file_dokumen) Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
            $file=$request->file('file_dokumen');
            $fileName = time().'_'.$file->getClientOriginalName();
            $file->storeAs('arsip',$fileName,'public');
            $validated['file_dokumen']=$fileName;
        }

        // $perhitungan = (new \App\Http\Controllers\ArsipController)->hitungRetensi(
        //     $validated['aktif_tahun'] ?? null,
        //     $validated['inaktif_tahun'] ?? null,
        //     $validated['keterangan_jra'],
        //     $validated['tanggal_arsip'],
        //     $validated['tanggal_referensi'] ?? null
        // );
        // $validated['aktif_sampai']=$perhitungan['aktif_sampai'];
        // $validated['inaktif_sampai']=$perhitungan['inaktif_sampai'];
        // $validated['status_arsip']=$perhitungan['status_arsip'];
        // =========================
        // PENANGANAN DUPLIKAT
        // =========================
        $validated['duplicate_reason'] = $request->duplicate_reason;
if ($request->tangani_duplikat == '1') {

    if (!$request->duplicate_reason) {
        return back()
            ->withInput()
            ->with('error', 'Alasan wajib diisi untuk penanganan duplikat.');
    }

    $validated['status_pindah'] = 'NON_ARSIP';
    $validated['is_duplicate'] = 0;

    Arsip::where('uraian_arsip', $arsip->uraian_arsip)
        ->where('tahun_arsip', $arsip->tahun_arsip)
        ->where('tingkat_perkembangan', $arsip->tingkat_perkembangan)
        ->where('id', '!=', $arsip->id)
        ->update([
            'is_duplicate' => 0
        ]);

    $validated['duplicate_reason'] = $request->duplicate_reason;
}
        // unset($validated['status_pindah']);


        $arsip->update($validated);

        return redirect()->route('subbagian.arsip.index')->with('success','Arsip berhasil diperbarui.');
    }

    public function destroy(Arsip $arsip)
    {
        $this->authorizeSubBagianArsip($arsip);

        if($arsip->file_dokumen) Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
        $arsip->delete();
        return redirect()->route('subbagian.arsip.index')->with('success','Arsip berhasil dihapus.');
    }

// public function import(Request $request)
// {
//     $request->validate([
//         'file_excel' => [
//             'required',
//             'file',
//             'mimes:xlsx,xls',
//             'extensions:xlsx,xls'
//         ]
//     ]);

//     try {

//         // validasi isi file excel
//         \PhpOffice\PhpSpreadsheet\IOFactory::load(
//             $request->file('file_excel')->getRealPath()
//         );

//         $import = new ArsipImportSubBagian();

//         Excel::import($import, $request->file('file_excel'));

//         if ($import->importedRows == 0) {
//             return back()->with(
//                 'error',
//                 'File tidak berisi data yang dapat diimport.'
//             );
//         }

//         if ($import->failures()->isNotEmpty()) {
//             return back()->with([
//                 'warning' => 'Import selesai tetapi ada data yang gagal.',
//                 'import_errors' => $import->failures()
//             ]);
//         }

//         return back()->with(
//             'success',
//             'Semua data berhasil diimport.'
//         );

//     } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {

//         return back()->with(
//             'error',
//             'File Excel tidak valid atau rusak.'
//         );

//     } catch (\Exception $e) {

//         return back()->with(
//             'error',
//             'Import gagal: '.$e->getMessage()
//         );
//     }
// }
// di ArsipController (atau controller yang sesuai)

public function import(Request $request)
{
    $request->validate([
        'file_excel' => 'required|file|mimes:xlsx,xls'
    ]);

    try {

        /*
        |--------------------------------------------------------------------------
        | BACA EXCEL
        |--------------------------------------------------------------------------
        */

        $rows = Excel::toArray(
            new ArsipImportSubBagian(),
            $request->file('file_excel')
        );

        $rows = $rows[0];

        $validatorImport = new ArsipImportSubBagian();

        $validatorImport->validateExcel($rows);


        /*
        |--------------------------------------------------------------------------
        | ADA ERROR
        |--------------------------------------------------------------------------
        */

        if (!empty($validatorImport->errors)) {

            return back()->with([
                'error' => 'Import dibatalkan karena terdapat data yang tidak valid.',
                'import_errors' => $validatorImport->errors,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | TIDAK ADA ERROR
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        $import = new ArsipImportSubBagian();

        Excel::import(
            $import,
            $request->file('file_excel')
        );

        DB::commit();

        return redirect()
            ->route('subbagian.arsip.index')
            ->with(
                'success',
                "Berhasil mengimpor {$import->importedRows} data arsip."
            );

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error($e->getMessage());

        return back()->with(
            'error',
            'Import gagal : ' . $e->getMessage()
        );
    }
}
// public function import(Request $request)
// {
//     $request->validate([
//         'file_excel' => 'required|file|mimes:xlsx,xls'
//     ]);

//     try {
//         $import = new ArsipImportSubBagian();

//         // Excel::import($import, $request->file('file_excel'));

//         // // Ambil data error (baris yang gagal)
//         // $failures = $import->failures();

//         // // Jika ada error
//         // if ($failures->isNotEmpty()) {
//         //     return back()->with([
//         //         'warning' => '⚠️ Import selesai, tapi ada data yang gagal.',
//         //         'import_errors' => $failures
//         //     ]);
//         // }

//         Excel::import($import, $request->file('file_excel'));

// if ($import->importedRows == 0) {
//     return back()->with(
//         'error',
//         'File tidak berisi data yang dapat diimport.'
//     );
// }

// $failures = $import->failures();

// if ($failures->isNotEmpty()) {
//     return back()->with([
//         'warning' => 'Import selesai tetapi ada data yang gagal.',
//         'import_errors' => $failures
//     ]);
// }

//         // Jika semua sukses
//         return redirect()->route('subbagian.arsip.index')
//             ->with('success', '✅ Semua data berhasil diimport.');

//     } catch (\Exception $e) {
//         return back()->with('error', '❌ Import gagal: ' . $e->getMessage());
//     }
// }
    // public function export(Request $request)
    // {
    //     $columns = $request->input('columns',[]);
    //     if(count($columns)==0) return back()->with('error','Pilih minimal satu kolom.');

    //     return Excel::download(
    //         new ArsipExport($request,$columns,Auth::user()->sub_bagian_id),
    //         'arsip_subbagian_'.date('Y-m-d-H-i-s').'.xlsx'
    //     );
    // }

public function export(Request $request)
{
    $columns = $request->input('columns', []);
    if (count($columns) == 0) {
        return back()->with('error', 'Pilih minimal satu kolom.');
    }

    $columns = array_map(function($col) {
        return $col === 'jumlah_berkas' ? 'jumlah' : $col;
    }, $columns);

    return Excel::download(
        new ArsipExport($request, $columns, []),
        'arsip_subbagian_' . date('Y-m-d-H-i-s') . '.xlsx'
    );
}

    public function ajukanPindah(Request $request, Arsip $arsip)
{
    $user = Auth::user();

    if ($arsip->sub_bagian_id != $user->sub_bagian_id) {
        abort(403);
    }

    $request->validate([
        'bap_id' => 'required|exists:berita_acara_pindah,id'
    ]);

    // Ambil BAP
    $bap = BeritaAcaraPindah::where('id', $request->bap_id)
        ->where('sub_bagian_id', $user->sub_bagian_id)
        ->firstOrFail();

    DB::beginTransaction();
    try {
        // Simpan detail
        BeritaAcaraDetail::create([
            'bap_id'   => $bap->id,
            'arsip_id' => $arsip->id,
            'status'   => 'DIAJUKAN',
        ]);

        // Update arsip
        $arsip->status_pindah = 'DIAJUKAN';
        $arsip->file_berita_acara = $bap->file_bap;
        $arsip->save();

        DB::commit();

        return back()->with('success', 'Arsip berhasil diajukan menggunakan BAP: ' . $bap->nomor_bap);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal: ' . $e->getMessage());
    }
}


public function ajukanPindahMultiple(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'arsip_ids'   => 'required|array',
        'arsip_ids.*' => 'exists:arsips,id',
        'bap_id'      => 'required|exists:berita_acara_pindah,id'
    ]);

    // Ambil BAP - pastikan status DRAFT
    $bap = BeritaAcaraPindah::where('id', $request->bap_id)
        ->where('sub_bagian_id', $user->sub_bagian_id)
        ->where('status', 'DRAFT')
        ->firstOrFail();

    // Ambil arsip milik user yang BELUM memiliki BAP aktif
    $arsips = Arsip::whereIn('id', $request->arsip_ids)
        ->where('sub_bagian_id', $user->sub_bagian_id)
        ->where('status_pindah', 'BELUM')
        ->whereDoesntHave('beritaAcaraDetails', function($q) {
            $q->whereIn('status', ['DRAFT', 'DIAJUKAN', 'DITERIMA']);
        })
        ->get();

    if ($arsips->count() != count($request->arsip_ids)) {
        return back()->with('error', 'Beberapa arsip tidak ditemukan atau sudah memiliki BAP.');
    }

    DB::beginTransaction();
    try {
        foreach ($arsips as $arsip) {
            // Cek apakah arsip sudah ada di BAP ini
            $exists = BeritaAcaraDetail::where('bap_id', $bap->id)
                ->where('arsip_id', $arsip->id)
                ->exists();

            if (!$exists) {
                BeritaAcaraDetail::create([
                    'bap_id'   => $bap->id,
                    'arsip_id' => $arsip->id,
                    'status'   => 'DRAFT',
                ]);
            }

            // UPDATE status_pindah menjadi DRAFT
            $arsip->update([
                'status_pindah' => 'DRAFT'
            ]);
        }

        DB::commit();

        return redirect()->route('subbagian.arsip.index')
            ->with('success', count($arsips) . ' arsip berhasil ditambahkan ke BAP: ' . $bap->nomor_bap);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal menambahkan arsip ke BAP: ' . $e->getMessage());
    }
}
    public function duplicate(Arsip $arsip)
{
    $user = Auth::user();

    // pastikan arsip milik sub bagian user
    if ($arsip->sub_bagian_id != $user->sub_bagian_id) {
        abort(403);
    }

    // duplikat data
    $newArsip = $arsip->replicate();

    // reset beberapa field penting
    $newArsip->created_by = $user->id;
    $newArsip->tanggal_masuk = now()->format('Y-m-d');
    $newArsip->status_pindah = 'BELUM';

    // opsional: kasih penanda biar keliatan duplikat
    $newArsip->uraian_arsip = $arsip->uraian_arsip . ' (Copy)';

    // kalau mau reset file biar tidak ikut
    // $newArsip->file_dokumen = null;

    $newArsip->save();

    return redirect()->route('subbagian.arsip.index')
        ->with('success', 'Arsip berhasil diduplikasi.');
}

// private function getLokasiArsip($user)
// {
//     $namaSub = $user->subBagian->nama_sub_bagian ?? null;

//     $mapLokasi = [
//         'Sub Bagian Umum dan Logistik' => 'RUANG_SUBBAGIAN_UMUM_LOGISTIK',
//         'Sub Bagian Partisipasi, Hubungan Masyarakat dan SDM' => 'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM',
//         'Sub Bagian Keuangan' => 'RUANG_SUBBAGIAN_KEUANGAN',
//         'Sub Bagian Perencanaan, Data, dan Informasi' => 'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI',
//         'Sub Bagian Teknis Penyelenggaraan Pemilu' => 'RUANG_SUBBAGIAN_TEKNIS',
//         'Sub Bagian Hukum' => 'RUANG_SUBBAGIAN_HUKUM',
//     ];

//     return $mapLokasi[$namaSub] ?? null;
// }

private function getLokasiArsip($user)
{
    $mapLokasi = [
        1 => 'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK',
        2 => 'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM',
        7 => 'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI',
        5 => 'RUANG_SUBBAGIAN_TEKNIS_HUKUM',
    ];

    return $mapLokasi[$user->sub_bagian_id] ?? null;
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

public function destroyMultiple(Request $request)
{
    $user = Auth::user();

    $request->validate([
        'arsip_ids'   => 'required|array',
        'arsip_ids.*' => 'exists:arsips,id',
    ]);

    // Hanya ambil arsip milik sub bagian user
    $arsips = Arsip::whereIn('id', $request->arsip_ids)
        ->where('sub_bagian_id', $user->sub_bagian_id)
        ->get();

    if ($arsips->isEmpty()) {
        return back()->with('error', 'Tidak ada arsip yang dapat dihapus.');
    }

    $gagal = [];
    $berhasil = 0;

    DB::beginTransaction();
    try {
        foreach ($arsips as $arsip) {
            // Arsip yang sudah diajukan/dipindahkan tidak boleh dihapus
            if (in_array($arsip->status_pindah, ['DIAJUKAN', 'DIPINDAHKAN'])) {
                $gagal[] = $arsip->uraian_arsip;
                continue;
            }

            if ($arsip->file_dokumen) {
                Storage::disk('public')->delete('arsip/' . $arsip->file_dokumen);
            }

            $arsip->delete();
            $berhasil++;
        }

        DB::commit();

        $message = "{$berhasil} arsip berhasil dihapus.";
        if (!empty($gagal)) {
            $message .= ' ' . count($gagal) . ' arsip tidak dapat dihapus karena statusnya sudah DIAJUKAN/DIPINDAHKAN.';
        }

        return redirect()->route('subbagian.arsip.index')->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal menghapus arsip: ' . $e->getMessage());
    }
}

}
