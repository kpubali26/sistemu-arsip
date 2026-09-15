<?php

namespace App\Http\Controllers;

use App\Exports\ArsipExport;
use App\Imports\ArsipImport;
use App\Models\Arsip;
use App\Models\BeritaAcaraDetail;
use App\Models\HistoryPindah;
use App\Models\KodeKlasifikasi;
use App\Models\MasterBox;
use App\Models\MasterRak;
use App\Models\SinarV1Document;
use App\Models\SubBagian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ArsipController extends Controller
{
    public function index(Request $request)
    {

        $query = Arsip::with(['kodeKlasifikasi', 'subBagian'])
            ->whereIn('status_pindah', [
                'DIPINDAHKAN',
                'LANGSUNG',
            ]);

        // Filter duplikat
        if ($request->show_duplicates == 1) {

            // RESET hanya yang BUKAN NON_ARSIP
            DB::table('arsips')
                ->where('status_arsip', '!=', 'NON_ARSIP')
                ->update([
                    'is_duplicate' => 0,
                ]);

            $duplicateGroups = DB::table('arsips')
                ->select('uraian_arsip', 'tahun_arsip', 'tingkat_perkembangan')
                ->where('status_arsip', '!=', 'NON_ARSIP')
                ->groupBy('uraian_arsip', 'tahun_arsip', 'tingkat_perkembangan')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateGroups as $group) {
                DB::table('arsips')
                    ->where('uraian_arsip', $group->uraian_arsip)
                    ->where('tahun_arsip', $group->tahun_arsip)
                    ->where('tingkat_perkembangan', $group->tingkat_perkembangan)
                    ->where('status_arsip', '!=', 'NON_ARSIP')
                    ->update([
                        'is_duplicate' => 1,
                        'duplicate_reason' => DB::raw("
                            CASE 
                                WHEN duplicate_reason IS NULL 
                                THEN 'Duplikat otomatis' 
                                ELSE duplicate_reason 
                            END
                        "),
                    ]);
            }

            $query->where('is_duplicate', 1);
        }

        if ($request->filter === 'tanpa_ruangan') {
            $query->where(function ($q) {
                $q->whereNull('rak_id')
                    ->orWhereNull('box_id');
            });
        }

        if ($request->filter === 'belum_file') {
            $query->where(function ($q) {
                $q->whereNull('file_dokumen')->orWhere('file_dokumen', '');
            })->where(function ($q) {
                $q->whereNull('link_foto')->orWhere('link_foto', '');
            });
        }
        // Filter berdasarkan status arsip
        if ($request->has('status_arsip') && $request->status_arsip != '') {
            $query->where('status_arsip', $request->status_arsip);
        }

        // Filter berdasarkan tahun
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
        // Filter berdasarkan sub bagian
        if ($request->has('sub_bagian_id') && $request->sub_bagian_id != '') {
            $query->where('sub_bagian_id', $request->sub_bagian_id);
        }

        // Filter berdasarkan kode klasifikasi
        if ($request->has('kode_klasifikasi_id') && $request->kode_klasifikasi_id != '') {
            $query->where('kode_klasifikasi_id', $request->kode_klasifikasi_id);
        }

        if ($request->filled('lokasi_arsip')) {
            $query->where('lokasi_arsip', $request->lokasi_arsip);
        }
        if ($request->filled('rak_id')) {
            $query->where('rak_id', $request->rak_id);
        }
        if ($request->filled('box_id')) {
            $query->where('box_id', $request->box_id);
        }

        // Filter berdasarkan kondisi fisik
        if ($request->has('keterangan') && $request->keterangan != '') {
            $query->where('keterangan', $request->keterangan);
        }

        // Filter berdasarkan keterangan JRA
        if ($request->has('keterangan_jra') && $request->keterangan_jra != '') {
            $query->where('keterangan_jra', $request->keterangan_jra);
        }
        // Filter aktif_sampai kosong / tidak
        if (request('aktif_tahun_kosong') == '1') {
            // BELUM DIISI
            $query->where(function ($q) {
                $q->whereNull('aktif_sampai')
                    ->orWhere('aktif_sampai', '0000-00-00');
            });
        } elseif (request('aktif_tahun_kosong') == '0') {
            // SUDAH DIISI
            $query->whereNotNull('aktif_sampai')
                ->where('aktif_sampai', '!=', '0000-00-00');
        }

        // Filter inaktif_tahun kosong / tidak
        if (request('inaktif_tahun_kosong') == '1') {
            // BELUM DIISI
            $query->where(function ($q) {
                $q->whereNull('inaktif_sampai')
                    ->orWhere('inaktif_sampai', '0000-00-00');
            });
        } elseif (request('inaktif_tahun_kosong') == '0') {
            // SUDAH DIISI
            $query->whereNotNull('inaktif_sampai')
                ->where('inaktif_sampai', '!=', '0000-00-00');
        }

        $kondisiOptions = [
            'BAIK' => 'Baik',
            'RUSAK' => 'Rusak',
            'HILANG' => 'Hilang',
        ];

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('kodeKlasifikasi', function ($subQuery) use ($request) {
                    $subQuery->where('kode', 'like', "%{$request->search}%")
                        ->orWhere('uraian', 'like', "%{$request->search}%");
                })
                    ->orWhere('uraian_arsip', 'like', "%{$request->search}%")
                    ->orWhereHas('subBagian', function ($subQuery) use ($request) {
                        $subQuery->where('nama_sub_bagian', 'like', "%{$request->search}%");
                    });
            });
        }

        // Sorting
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $query->reorder(); // jaga-jaga ada order lain yang menempel dari relasi/eager load

        if ($sort === 'kode_klasifikasi') {
            $query->leftJoin('kode_klasifikasis', 'arsips.kode_klasifikasi_id', '=', 'kode_klasifikasis.id')
                ->orderBy('kode_klasifikasis.kode', $direction)
                ->select('arsips.*');
        } elseif ($sort === 'nomor_rak') {
            $query->leftJoin('master_raks', 'arsips.rak_id', '=', 'master_raks.id')
                ->orderBy('master_raks.nomor_rak', $direction)
                ->select('arsips.*');
        } elseif ($sort === 'nomor_box') {
            $query->leftJoin('master_box', 'arsips.box_id', '=', 'master_box.id')
                ->orderBy('master_box.nomor_box', $direction)
                ->select('arsips.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        \Log::info('SORT DEBUG', [
            'sort' => $sort,
            'direction' => $direction,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        // $arsips = $query->paginate(15);

        $perPageInput = $request->get('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50, 100];

        if ($perPageInput === 'all') {
            // Hitung total data sesuai filter yang aktif (pakai arsips.id agar aman dari join)
            $perPage = (clone $query)->count('arsips.id');
            $perPage = $perPage > 0 ? $perPage : 1; // hindari paginate(0)
        } elseif (in_array((int) $perPageInput, $allowedPerPage)) {
            $perPage = (int) $perPageInput;
        } else {
            $perPage = 15;
        }

        $arsips = $query->paginate($perPage)->withQueryString();

        // Data untuk filter
        $tahunOptions = Arsip::select('tahun_arsip')
            ->distinct()
            ->orderBy('tahun_arsip', 'desc')
            ->pluck('tahun_arsip');

        $subBagianOptions = SubBagian::select('id', 'nama_sub_bagian as nama_sub_bagian')
            ->orderBy('nama_sub_bagian')
            ->get();

        $kodeKlasifikasiOptions = KodeKlasifikasi::select('id', 'kode', 'uraian')
            ->orderBy('kode')
            ->get();

        $statusOptions = [
            'AKTIF' => 'Aktif',
            'INAKTIF' => 'Inaktif',
            'HABIS_RETENSI' => 'HABIS RETENSI',
            'MUSNAH' => 'Musnah',
            'PERMANEN' => 'Permanen',
        ];

        $keteranganJraOptions = [
            'MUSNAH' => 'Musnah',
            'PERMANEN' => 'Permanen',
        ];

        $lokasiOptions = [
            'RECORD_CENTER_PERMANEN' => 'Record Center (Arsip Permanen)',
            'RECORD_CENTER_INAKTIF' => 'Record Center (Arsip Inaktif)',
        ];

        $rakOptions = MasterRak::orderBy('nomor_rak')->get(['id', 'nomor_rak', 'lokasi_arsip']);
        $boxOptions = MasterBox::orderBy('nomor_box')->get(['id', 'nomor_box', 'rak_id']);

        return view('arsip.index', compact(
            'arsips',
            'tahunOptions',
            'subBagianOptions',
            'kodeKlasifikasiOptions',
            'statusOptions',
            'kondisiOptions',
            'keteranganJraOptions',
            'lokasiOptions',   
            'rakOptions',      
            'boxOptions'
        ));
    }

    public function create(Request $request)
    {
        $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
        $subBagianOptions = SubBagian::orderBy('nama_sub_bagian')->get();

        // =========================
        // FILTER LOKASI BERDASARKAN ROLE
        // =========================
        $user = auth()->user();
        $allowedLokasi = [];

        if (in_array($user->role, ['admin', 'unit_kearsipan'])) {
            // Admin & Unit Kearsipan: bisa pilih semua lokasi
            $allowedLokasi = [
                'RECORD_CENTER_PERMANEN',
                'RECORD_CENTER_INAKTIF',
            ];
        } else {
            // Subbagian: hanya ruangannya sendiri
            $subBagianId = $user->sub_bagian_id;
            $ruanganUser = $this->getRuanganBySubBagian($subBagianId);
            if ($ruanganUser) {
                $allowedLokasi = [$ruanganUser];
            }
        }

        $rakOptions = MasterRak::whereIn('lokasi_arsip', $allowedLokasi)
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

        $legacyDocument = null;
        if ($request->filled('sinar_v1_document')) {
            $legacyDocument = SinarV1Document::whereKey($request->integer('sinar_v1_document'))
                ->where('status_integrasi', 'SIAP_DIDAFTARKAN')
                ->whereNull('arsip_id')
                ->firstOrFail();
        }

        return view('arsip.create', compact(
            'kodeKlasifikasiOptions',
            'subBagianOptions',
            'rakOptions',
            'boxOptions',
            'defaultValues',
            'legacyDocument'
        ));
    }

    private function getRuanganBySubBagian($subBagianId)
    {
        $mapping = [
            1 => 'RUANG_SUBBAGIAN_KEUANGAN_UMUM_LOGISTIK',
            2 => 'RUANG_SUBBAGIAN_PARTISIPASI_MASYARAKAT_SDM',
            7 => 'RUANG_SUBBAGIAN_PERENCANAAN_DATA_INFORMASI',
            5 => 'RUANG_SUBBAGIAN_TEKNIS_HUKUM',
        ];

        return $mapping[$subBagianId] ?? null;
    }

    public function edit(Arsip $arsip)
    {
        $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
        $subBagianOptions = SubBagian::orderBy('nama_sub_bagian')->get();

        // =========================
        // FILTER LOKASI BERDASARKAN ROLE
        // =========================
        $user = auth()->user();
        $allowedLokasi = [];

        if (in_array($user->role, ['admin', 'unit_kearsipan'])) {
            $allowedLokasi = [
                'RECORD_CENTER_PERMANEN',
                'RECORD_CENTER_INAKTIF',
            ];
        } else {
            $subBagianId = $user->sub_bagian_id;
            $ruanganUser = $this->getRuanganBySubBagian($subBagianId);
            if ($ruanganUser) {
                $allowedLokasi = [$ruanganUser];
            }
        }

        $rakOptions = MasterRak::whereIn('lokasi_arsip', $allowedLokasi)
            ->orderBy('nomor_rak')
            ->get(['id', 'nomor_rak', 'lokasi_arsip']);

        $boxOptions = MasterBox::whereIn('rak_id', $rakOptions->pluck('id'))
            ->orderBy('nomor_box')
            ->get(['id', 'nomor_box', 'rak_id']);

        return view('arsip.edit', compact(
            'arsip',
            'kodeKlasifikasiOptions',
            'subBagianOptions',
            'rakOptions',
            'boxOptions'
        ));
    }

    public function store(Request $request)
    {
        \Log::info('Data yang dikirim:', $request->all());

        $legacyDocumentToArchive = null;
        if ($request->filled('sinar_v1_document_id')) {
            abort_unless(in_array(strtolower((string) $request->user()->role), ['admin', 'super_admin', 'tu'], true), 403);
            $legacyDocumentToArchive = SinarV1Document::whereKey($request->integer('sinar_v1_document_id'))
                ->where('status_integrasi', 'SIAP_DIDAFTARKAN')
                ->whereNull('arsip_id')
                ->firstOrFail();
        }

        $validated = $request->validate([
            // WAJIB
            'kode_klasifikasi_id' => 'required|exists:kode_klasifikasis,id',
            'uraian_arsip' => 'required|string|min:30',
            'sub_bagian_id' => 'required|exists:sub_bagians,id',
            'tahun_arsip' => [
                'required',
                'integer',
                'min:2000',
                'max:'.(date('Y') + 1),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tanggal_arsip')) {
                        $tahunTanggal = Carbon::parse($request->tanggal_arsip)->year;
                        if ((int) $value !== $tahunTanggal) {
                            $fail("Tahun arsip ({$value}) harus sama dengan tahun pada tanggal arsip ({$tahunTanggal}).");
                        }
                    }
                },
            ],
            'tanggal_arsip' => 'required|date',
            'jumlah_berkas' => 'required|integer|min:1',
            'satuan_arsip' => 'required|in:BENDEL,LEMBAR',
            'klasifikasi_keamanan' => 'required|in:Biasa/Terbuka,Terbatas,Rahasia',

            // RETENSI
            'aktif_tahun' => 'nullable|string|max:100',
            'inaktif_tahun' => 'nullable|string|max:100',
            'tanggal_referensi' => 'nullable|date',
            'keterangan_jra' => 'nullable|in:PERMANEN,MUSNAH',

            // hasil hitung
            'aktif_sampai' => 'nullable|date',
            'inaktif_sampai' => 'nullable|date',
            'status_arsip' => 'nullable|in:AKTIF,INAKTIF,MUSNAH,PERMANEN',

            // optional lain
            'rak_id' => 'required|exists:master_raks,id',
            'box_id' => 'required|exists:master_box,id',
            'nomor_sampul' => 'nullable|string|max:100',
            'lokasi_arsip' => 'required|in:SUB_BAGIAN,RECORD_CENTER_PERMANEN,RECORD_CENTER_INAKTIF',
            'tingkat_perkembangan' => 'nullable|in:ASLI,COPY,SALINAN',
            'keterangan' => 'nullable|in:BAIK,RUSAK,HILANG',
            'media_arsip' => 'nullable|string|max:255',
            'file_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:102400',
            'link_foto' => 'nullable|url|max:1000',
            'sinar_v1_document_id' => 'nullable|exists:sinar_v1_documents,id',
        ]);

        // Validasi tahun
        $tahunInput = (int) $validated['tahun_arsip'];
        $tahunTanggal = (int) date('Y', strtotime($validated['tanggal_arsip']));
        if ($tahunInput !== $tahunTanggal) {
            return back()->withInput()->withErrors([
                'tahun_arsip' => 'Tahun arsip harus sama dengan tahun pada tanggal arsip.',
                'tanggal_arsip' => 'Tanggal arsip harus sesuai dengan tahun arsip.',
            ]);
        }

        try {
            $validated['tahun_arsip'] = (string) $validated['tahun_arsip'];
            if (auth()->check()) {
                $validated['created_by'] = auth()->id();
            }
            $validated['tanggal_masuk'] = now()->format('Y-m-d');
            $validated['link_foto'] = $request->link_foto;

            // Default field (tanpa nomor_rak dan nomor_box)
            $defaults = [
                'nomor_sampul' => '',
                'keterangan' => 'BAIK',
                'tingkat_perkembangan' => 'ASLI',
                'status_pindah' => 'LANGSUNG',
            ];
            foreach ($defaults as $field => $value) {
                if (! isset($validated[$field]) || $validated[$field] === '') {
                    $validated[$field] = $value;
                }
            }

            // Upload file
            // if ($request->hasFile('file_dokumen')) {
            //     $file = $request->file('file_dokumen');
            //     $fileName = time().'_'.$file->getClientOriginalName();
            //     $file->storeAs('arsip', $fileName, 'public');
            //     $validated['file_dokumen'] = $fileName;
            // }

            if ($request->hasFile('file_dokumen')) {
                $file = $request->file('file_dokumen');
                $fileName = time().'_'.$file->getClientOriginalName();
                $file->storeAs('', $fileName, 'arsip');
                $validated['file_dokumen'] = $fileName;
            }

            // Retensi
            if (empty($validated['aktif_tahun']) || empty($validated['inaktif_tahun'])) {
                $validated['aktif_sampai'] = null;
                $validated['inaktif_sampai'] = null;
                $validated['status_arsip'] = 'AKTIF';
            } else {
                $perhitungan = $this->hitungRetensi(
                    $validated['aktif_tahun'],
                    $validated['inaktif_tahun'],
                    $validated['keterangan_jra'],
                    $validated['tanggal_arsip'],
                    $validated['tanggal_referensi'] ?? null
                );
                $validated['aktif_sampai'] = $perhitungan['aktif_sampai'];
                $validated['inaktif_sampai'] = $perhitungan['inaktif_sampai'];
                $validated['status_arsip'] = $perhitungan['status_arsip'];
            }

            $arsip = Arsip::create($validated);

            if ($legacyDocumentToArchive) {
                $safeExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $extension = strtolower(pathinfo($legacyDocumentToArchive->file_name_original ?? '', PATHINFO_EXTENSION));

                if (! $arsip->file_dokumen && in_array($extension, $safeExtensions, true)
                    && $legacyDocumentToArchive->file_path && Storage::disk('local')->exists($legacyDocumentToArchive->file_path)) {
                    $fileName = (string) Str::uuid().($extension ? ".{$extension}" : '');
                    Storage::disk('public')->put('arsip/'.$fileName, Storage::disk('local')->get($legacyDocumentToArchive->file_path));
                    $arsip->update(['file_dokumen' => $fileName]);
                }

                $legacyDocumentToArchive->update([
                    'arsip_id' => $arsip->id,
                    'status_integrasi' => 'SUDAH_JADI_ARSIP',
                ]);
            }

            return redirect()->route('arsip.index')
                ->with('success', 'Arsip berhasil ditambahkan.');

        } catch (\Exception $e) {
            \Log::error('Error: '.$e->getMessage());

            return back()->withInput()
                ->with('error', 'Gagal menyimpan arsip: '.$e->getMessage());
        }
    }

    public function hitungRetensi($aktif_tahun, $inaktif_tahun, $keterangan_jra, $tanggal_arsip, $tanggal_referensi = null)
    {
        $result = [
            'aktif_sampai' => null,
            'inaktif_sampai' => null,
            'status_arsip' => 'AKTIF',
        ];

        // Cek apakah mengandung kata SETELAH
        $aktifMengandungSetelah = stripos($aktif_tahun, 'SETELAH') !== false;
        $inaktifMengandungSetelah = stripos($inaktif_tahun, 'SETELAH') !== false;

        // Jika mengandung SETELAH tapi tanggal referensi kosong, kembalikan status AKTIF saja
        if (($aktifMengandungSetelah || $inaktifMengandungSetelah) && empty($tanggal_referensi)) {
            $result['status_arsip'] = 'AKTIF';

            return $result;
        }

        // Ekstrak angka dari teks
        $aktifTahunAngka = $this->extractNumberFromText($aktif_tahun);
        $inaktifTahunAngka = $this->extractNumberFromText($inaktif_tahun);

        if (! $aktifTahunAngka || ! $inaktifTahunAngka) {
            $result['status_arsip'] = 'AKTIF';

            return $result;
        }

        // Tentukan TAHUN dasar perhitungan (bukan tanggal)
        if ($aktifMengandungSetelah || $inaktifMengandungSetelah) {
            // Jika menggunakan SETELAH, ambil tahun dari tanggal_referensi
            $tahunDasar = Carbon::parse($tanggal_referensi)->year;
        } else {
            // Jika tidak menggunakan SETELAH, ambil tahun dari tanggal_arsip
            $tahunDasar = Carbon::parse($tanggal_arsip)->year;
        }

        // Hitung TAHUN aktif sampai (hanya tahun, tanggal di-set ke 31 Desember)
        $tahunAktifSampai = $tahunDasar + $aktifTahunAngka;
        $aktifSampaiDate = Carbon::create($tahunAktifSampai, 12, 31, 0, 0, 0);

        // Hitung TAHUN inaktif sampai
        $tahunInaktifSampai = $tahunAktifSampai + $inaktifTahunAngka;
        $inaktifSampaiDate = Carbon::create($tahunInaktifSampai, 12, 31, 0, 0, 0);

        // Hitung TAHUN habis retensi (+1 tahun dari inaktif)
        $tahunHabisRetensi = $tahunInaktifSampai + 1;
        $habisRetensiDate = Carbon::create($tahunHabisRetensi, 12, 31, 0, 0, 0);

        // Tentukan status arsip berdasarkan TAHUN SEKARANG (bukan tanggal)
        $tahunSekarang = Carbon::now()->year;

        if ($keterangan_jra === 'PERMANEN') {
            $result['status_arsip'] = 'PERMANEN';
        } elseif ($keterangan_jra === 'MUSNAH') {
            if ($tahunSekarang <= $tahunAktifSampai) {
                $result['status_arsip'] = 'AKTIF';
            } elseif ($tahunSekarang <= $tahunInaktifSampai) {
                $result['status_arsip'] = 'INAKTIF';
            } elseif ($tahunSekarang <= $tahunHabisRetensi) {
                // Tahun di antara inaktif_sampai dan habis_retensi
                $result['status_arsip'] = 'HABIS_RETENSI';
            } else {
                // Setelah melewati tahun habis_retensi, tetap HABIS_RETENSI atau bisa MUSNAH
                $result['status_arsip'] = 'HABIS_RETENSI';
            }
        } else {
            // Untuk selain MUSNAH
            if ($tahunSekarang <= $tahunAktifSampai) {
                $result['status_arsip'] = 'AKTIF';
            } elseif ($tahunSekarang <= $tahunInaktifSampai) {
                $result['status_arsip'] = 'INAKTIF';
            } else {
                $result['status_arsip'] = 'INAKTIF';
            }
        }

        // Set tanggal hasil perhitungan (pakai 31 Desember)
        $result['aktif_sampai'] = $aktifSampaiDate->format('Y-m-d');
        $result['inaktif_sampai'] = $inaktifSampaiDate->format('Y-m-d');

        return $result;
    }

    /**
     * Ekstrak angka dari teks (contoh: "2 TAHUN" atau "2 TAHUN SETELAH KEGIATAN")
     */
    private function extractNumberFromText($text)
    {
        if (preg_match('/\d+/', $text, $matches)) {
            return (int) $matches[0];
        }

        return null;
    }

    // public function show(Arsip $arsip)
    // {
    //     $arsip->load(['kodeKlasifikasi', 'subBagian']);
    //     return view('arsip.show', compact('arsip'));
    // }

    // public function show(Request $request, Arsip $arsip)
    // {
    //     return view('arsip.show', [
    //         'arsip' => $arsip,
    //         'returnUrl' => $request->get('return')
    //     ]);
    // }

    public function show(Request $request, Arsip $arsip)
    {
        // Load data riwayat perpindahan
        $riwayatPindah = HistoryPindah::with('user')
            ->where('arsip_id', $arsip->id)
            ->orderBy('tanggal_pindah', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil data berita acara yang terkait dengan arsip ini
        $beritaAcaraDetail = BeritaAcaraDetail::with(['beritaAcara', 'beritaAcara.subBagian'])
            ->where('arsip_id', $arsip->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('arsip.show', [
            'arsip' => $arsip,
            'returnUrl' => $request->get('return'),
            'riwayatPindah' => $riwayatPindah,
            'beritaAcaraDetail' => $beritaAcaraDetail,
        ]);
    }

    // public function edit(Arsip $arsip)
    // {
    //     // Ambil data untuk dropdown
    //     $kodeKlasifikasiOptions = KodeKlasifikasi::orderBy('kode')->get();
    //     $subBagianOptions = SubBagian::orderBy('nama_sub_bagian')->get();

    //     return view('arsip.edit', compact(
    //         'arsip',
    //         'kodeKlasifikasiOptions',
    //         'subBagianOptions'
    //     ));
    // }

    public function update(Request $request, Arsip $arsip)
    {
        // Debug data masuk
        \Log::info('Data update yang dikirim:', $request->all());

        // =========================
        // VALIDASI
        // =========================
        $validated = $request->validate([
            // WAJIB - Data Dasar
            'kode_klasifikasi_id' => 'required|exists:kode_klasifikasis,id',
            'uraian_arsip' => 'required|string|min:30',
            'sub_bagian_id' => 'required|exists:sub_bagians,id',
            'tahun_arsip' => [
                'required',
                'integer',
                'min:2000',
                'max:'.(date('Y') + 1),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('tanggal_arsip')) {
                        $tahunTanggal = Carbon::parse($request->tanggal_arsip)->year;
                        if ((int) $value !== $tahunTanggal) {
                            $fail("Tahun arsip ({$value}) harus sama dengan tahun pada tanggal arsip ({$tahunTanggal}).");
                        }
                    }
                },
            ],
            'tanggal_arsip' => 'required|date',
            'jumlah_berkas' => 'required|integer|min:1',
            'satuan_arsip' => 'required|in:BENDEL,LEMBAR',
            'klasifikasi_keamanan' => 'required|in:Biasa/Terbuka,Terbatas,Rahasia',

            // Masa Retensi
            'aktif_tahun' => 'nullable|string|max:100',
            'inaktif_tahun' => 'nullable|string|max:100',
            'tanggal_referensi' => 'nullable|date',
            'keterangan_jra' => 'nullable|in:PERMANEN,MUSNAH',

            // Hasil hitung (akan dihitung ulang)
            'aktif_sampai' => 'nullable|date',
            'inaktif_sampai' => 'nullable|date',
            'status_arsip' => 'nullable|in:AKTIF,INAKTIF,HABIS_RETENSI,MUSNAH,PERMANEN',

            // Optional
            'rak_id' => 'required|exists:master_raks,id',
            'box_id' => 'required|exists:master_box,id',
            'nomor_sampul' => 'nullable|string|max:100',
            'lokasi_arsip' => 'required|in:SUB_BAGIAN,RECORD_CENTER_PERMANEN,RECORD_CENTER_INAKTIF',
            'tingkat_perkembangan' => 'nullable|in:ASLI,COPY,SALINAN',
            'keterangan' => 'nullable|in:BAIK,RUSAK,HILANG',
            'media_arsip' => 'nullable|string|max:255',

            // File
            'file_dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:102400',
            'link_foto' => 'nullable|url|max:1000',
            'hapus_file' => 'nullable|in:0,1',
            'tangani_duplikat' => 'nullable|in:1',
            'duplicate_reason' => 'nullable|string|max:1000',
        ]);

        try {

            // =========================
            // NORMALISASI DATA
            // =========================
            $validated['tahun_arsip'] = (string) $validated['tahun_arsip'];
            $validated['link_foto'] = $request->link_foto;

            // Default value jika kosong
            $defaults = [
                'nomor_rak' => '',
                'nomor_box' => '',
                'nomor_sampul' => '',
                'keterangan' => 'BAIK',
                'tingkat_perkembangan' => 'ASLI',
            ];

            foreach ($defaults as $field => $default) {
                if (! isset($validated[$field]) || $validated[$field] === '') {
                    $validated[$field] = $default;
                }
            }

            // =========================
            // HAPUS FILE LAMA (JIKA DIMINTA)
            // =========================
            // if (($request->hapus_file ?? '0') == '1' && $arsip->file_dokumen) {
            //     Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
            //     $validated['file_dokumen'] = null;
            // }
            if (($request->hapus_file ?? '0') == '1' && $arsip->file_dokumen) {
                Storage::disk('arsip')->delete($arsip->file_dokumen);
                $validated['file_dokumen'] = null;
            }

            // =========================
            // UPLOAD FILE BARU
            // =========================
            // if ($request->hasFile('file_dokumen')) {
            //     // hapus file lama
            //     if ($arsip->file_dokumen) {
            //         Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
            //     }

            //     $file = $request->file('file_dokumen');
            //     $fileName = time().'_'.$file->getClientOriginalName();
            //     $file->storeAs('arsip', $fileName, 'public');

            //     $validated['file_dokumen'] = $fileName;
            // }

            if ($request->hasFile('file_dokumen')) {
    if ($arsip->file_dokumen) {
        Storage::disk('arsip')->delete($arsip->file_dokumen);
    }
    $file = $request->file('file_dokumen');
    $fileName = time().'_'.$file->getClientOriginalName();
    $file->storeAs('', $fileName, 'arsip');
    $validated['file_dokumen'] = $fileName;
}

            // =========================
            // HITUNG ULANG RETENSI (SERVER SIDE)
            // =========================
            $perhitungan = null;
            if (! empty($validated['aktif_tahun']) &&
                 ! empty($validated['inaktif_tahun']) &&
                 ! empty($validated['keterangan_jra'])) {

                $perhitungan = $this->hitungRetensi(
                    $validated['aktif_tahun'],
                    $validated['inaktif_tahun'],
                    $validated['keterangan_jra'],
                    $validated['tanggal_arsip'],
                    $validated['tanggal_referensi'] ?? null
                );

                $validated['aktif_sampai'] = $perhitungan['aktif_sampai'];
                $validated['inaktif_sampai'] = $perhitungan['inaktif_sampai'];
                $validated['status_arsip'] = $perhitungan['status_arsip'];

            } else {
                // kalau tidak diisi, kosongkan
                $validated['aktif_sampai'] = null;
                $validated['inaktif_sampai'] = null;

                // status default (opsional, kamu bisa ubah)
                $validated['status_arsip'] = $validated['status_arsip'] ?? 'AKTIF';
            }
            // =========================
            // PENANGANAN DUPLIKAT
            // =========================
            $validated['duplicate_reason'] = $request->duplicate_reason;
if ($request->tangani_duplikat == '1') {

    if (! $request->duplicate_reason) {
        return back()
            ->withInput()
            ->with('error', 'Alasan wajib diisi untuk penanganan duplikat.');
    }

    $validated['status_arsip'] = 'NON_ARSIP';
    $validated['is_duplicate'] = 0;

    Arsip::where('uraian_arsip', $arsip->uraian_arsip)
        ->where('tahun_arsip', $arsip->tahun_arsip)
        ->where('tingkat_perkembangan', $arsip->tingkat_perkembangan)
        ->where('id', '!=', $arsip->id)
        ->where('status_arsip', '!=', 'NON_ARSIP')
        ->update([
            'is_duplicate' => 0,
        ]);

    $validated['duplicate_reason'] = $request->duplicate_reason;
}

            unset($validated['status_pindah']);

            \Log::info('Hasil hitung retensi update:', [
                'perhitungan' => $perhitungan,
            ]);

            // =========================
            // UPDATE DATA
            // =========================
            $arsip->update($validated);

            // return redirect()
            //     ->route('arsip.show', $arsip->id)
            //     ->with('success', 'Arsip berhasil diperbarui.');

            return redirect($request->redirect_url ?? route('arsip.index'))
                ->with('success', 'Arsip berhasil diupdate');

        } catch (\Exception $e) {

            \Log::error('Gagal update arsip: '.$e->getMessage());
            \Log::error($e->getTraceAsString());

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui arsip: '.$e->getMessage());
        }
    }

    public function destroy(Arsip $arsip)
    {
        // Hapus file jika ada
        // if ($arsip->file_dokumen && Storage::disk('public')->exists('arsip/'.$arsip->file_dokumen)) {
        //     Storage::disk('public')->delete('arsip/'.$arsip->file_dokumen);
        // }
        if ($arsip->file_dokumen && Storage::disk('arsip')->exists($arsip->file_dokumen)) {
    Storage::disk('arsip')->delete($arsip->file_dokumen);
}


        $arsip->delete();

        return redirect()->route('arsip.index')
            ->with('success', 'Arsip berhasil dihapus.');
    }

    public function viewFile(Arsip $arsip)
{
    if (! $arsip->file_dokumen || ! Storage::disk('arsip')->exists($arsip->file_dokumen)) {
        abort(404, 'File dokumen tidak ditemukan di server ini.');
    }

    return Storage::disk('arsip')->response($arsip->file_dokumen);
}

public function downloadFile(Arsip $arsip)
{
    if (! $arsip->file_dokumen || ! Storage::disk('arsip')->exists($arsip->file_dokumen)) {
        abort(404, 'File dokumen tidak ditemukan di server ini.');
    }

    return Storage::disk('arsip')->download($arsip->file_dokumen);
}

    /**
     * Fungsi untuk memperbarui status semua arsip
     * Bisa dijadikan scheduled task atau dijalankan manual
     */
    public function updateStatusAll()
    {
        $arsips = Arsip::all();
        $updated = 0;

        foreach ($arsips as $arsip) {
            // Hitung ulang status berdasarkan data yang ada
            $perhitungan = $this->hitungRetensi(
                $arsip->aktif_tahun,
                $arsip->inaktif_tahun,
                $arsip->keterangan_jra,
                $arsip->tanggal_arsip,
                $arsip->tanggal_referensi
            );

            // Update status saja (tanpa mengubah tanggal)
            $arsip->status_arsip = $perhitungan['status_arsip'];
            $arsip->save();

            $updated++;
        }

        return back()->with('success', "Status {$updated} arsip berhasil diperbarui.");
    }

    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file_excel' => 'required|file|mimes:xlsx,xls'
    //     ]);

    //     try {
    //         Excel::import(new ArsipImport, $request->file('file_excel'));

    //         return redirect()->route('arsip.index')
    //             ->with('success', 'Data arsip berhasil diimport.');
    //     } catch (\Exception $e) {
    //         return back()->with('error', 'Gagal import: ' . $e->getMessage());
    //     }
    // }
    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file_excel' => 'required|file|mimes:xlsx,xls'
    //     ]);

    //     try {
    //         $import = new ArsipImport();

    //         Excel::import($import, $request->file('file_excel'));

    //         // Ambil data error (baris yang gagal)
    //         $failures = $import->failures();

    //         // Jika ada error
    //         if ($failures->isNotEmpty()) {
    //             return back()->with([
    //                 'warning' => '⚠️ Import selesai, tapi ada data yang gagal.',
    //                 'import_errors' => $failures
    //             ]);
    //         }

    //         // Jika semua sukses
    //         return redirect()->route('arsip.index')
    //             ->with('success', '✅ Semua data berhasil diimport.');

    //     } catch (\Exception $e) {
    //         return back()->with('error', '❌ Import gagal: ' . $e->getMessage());
    //     }
    // }
    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls',
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | BACA EXCEL
            |--------------------------------------------------------------------------
            */

            $rows = Excel::toArray(
                new ArsipImport,
                $request->file('file_excel')
            );

            $rows = $rows[0];

            $validatorImport = new ArsipImport;

            $validatorImport->validateExcel($rows);

            /*
            |--------------------------------------------------------------------------
            | ADA ERROR
            |--------------------------------------------------------------------------
            */

            if (! empty($validatorImport->errors)) {

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

            $import = new ArsipImport;

            Excel::import(
                $import,
                $request->file('file_excel')
            );
            // if (!empty($import->errors)) {

            //     dd($import->errors);

            // }

            // // kalau tidak ada yang berhasil diimport
            // if ($import->importedRows == 0) {

            //     dd('Tidak ada data yang berhasil diimport.');

            // }

            return back()->with(
                'success',
                "Berhasil mengimpor {$import->importedRows} data arsip."
            );

            return redirect()
                ->route('arsip.index')
                ->with(
                    'success',
                    "Berhasil mengimpor {$import->importedRows} data arsip."
                );

        } catch (\Exception $e) {

            \Log::error($e->getMessage());

            return back()->with(
                'error',
                'Import gagal: '.$e->getMessage()
            );
        }
    }
    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file_excel' => 'required|file|mimes:xlsx,xls'
    //     ]);

    //     try {
    //         $import = new ArsipImport();

    //         Excel::import($import, $request->file('file_excel'));

    //         // Tidak ada satupun data yang berhasil diproses
    //         if ($import->importedRows === 0) {
    //             return back()->with('error', 'Tidak ada data untuk diimport.');
    //         }

    //         // Ambil data yang gagal
    //         $failures = $import->failures();

    //         if ($failures->isNotEmpty()) {
    //             return back()->with([
    //                 'warning' => '⚠️ Import selesai, tetapi ada beberapa data yang gagal.',
    //                 'import_errors' => $failures
    //             ]);
    //         }

    //         return redirect()
    //             ->route('arsip.index')
    //             ->with('success', "✅ Berhasil mengimpor {$import->importedRows} data arsip.");

    //     } catch (\Exception $e) {

    //     \Log::error($e->getMessage());
    //     \Log::error(get_class($e));

    //     return back()->with(
    //         'error',
    //         'Import gagal: '.$e->getMessage()
    //     );
    // }
    // }
    public function export(Request $request)
    {
        $columns = $request->input('columns', []);

        if (count($columns) === 0) {
            return back()->with('error', 'Pilih minimal satu kolom untuk export.');
        }

        // Debug: cek apa yang diterima
        \Log::info('Export request:', $request->all());
        \Log::info('Columns selected:', $columns);

        return Excel::download(
            new ArsipExport($request, $columns),
            'data-arsip-'.date('Y-m-d-H-i-s').'.xlsx'
        );
    }

public function checkDuplicates()
{
    try {
        $duplicates = DB::table('arsips')
            ->where('status_arsip', '!=', 'NON_ARSIP')
            ->select(
                'uraian_arsip',
                'tahun_arsip',
                'tingkat_perkembangan',
                DB::raw('COUNT(*) as total'),
                DB::raw('GROUP_CONCAT(id) as ids')
            )
            ->groupBy('uraian_arsip', 'tahun_arsip', 'tingkat_perkembangan')
            ->having('total', '>', 1)
            ->get();

        $result = [];

        foreach ($duplicates as $group) {
            $ids = $group->ids ? explode(',', $group->ids) : [];

            $records = [];
            foreach ($ids as $id) {
                $records[] = [
                    'id' => $id,
                    'link' => route('arsip.show', $id),
                ];
            }

            $result[] = [
                'ids' => $ids,
                'uraian_arsip' => $group->uraian_arsip,
                'tahun_arsip' => $group->tahun_arsip,
                'tingkat_perkembangan' => $group->tingkat_perkembangan,
                'records' => $records,
            ];
        }

        return response()->json([
            'duplicates' => $result,
            'total' => count($result),
            'total_records' => $duplicates->sum('total'),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    // app/Http/Controllers/ArsipController.php

    public function updateStatusBulk()
    {
        try {
            $tahunSekarang = Carbon::now()->year;
            $arsips = Arsip::where('keterangan_jra', 'MUSNAH')->get();
            $updated = 0;

            foreach ($arsips as $arsip) {
                $tahunAktifSampai = $arsip->aktif_sampai ? Carbon::parse($arsip->aktif_sampai)->year : 0;
                $tahunInaktifSampai = $arsip->inaktif_sampai ? Carbon::parse($arsip->inaktif_sampai)->year : 0;

                // Tentukan status
                if ($tahunSekarang <= $tahunAktifSampai) {
                    $statusBaru = 'AKTIF';
                } elseif ($tahunSekarang <= $tahunInaktifSampai) {
                    $statusBaru = 'INAKTIF';
                } else {
                    $statusBaru = 'HABIS_RETENSI';
                }

                if ($arsip->status_arsip !== $statusBaru) {
                    $arsip->status_arsip = $statusBaru;
                    $arsip->save();
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil update {$updated} arsip",
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateInline(Request $request, $id)
    {
        try {
            $arsip = Arsip::findOrFail($id);

            $field = $request->field;
            $value = $request->value;

            $allowedFields = [
                'uraian_arsip', 'lokasi_arsip',
                'aktif_tahun', 'inaktif_tahun', 'keterangan_jra', 'tanggal_arsip',
            ];

            if (! in_array($field, $allowedFields)) {
                return response()->json(['error' => 'Field tidak diizinkan'], 403);
            }

            // =========================
            // VALIDASI KHUSUS
            // =========================
            if (in_array($field, ['aktif_tahun', 'inaktif_tahun'])) {

                if ($value === '' || $value === null || $value === '-') {
                    $value = null;
                } else {
                    if (! preg_match('/\d+/', $value)) {
                        return response()->json([
                            'error' => 'Format tahun harus mengandung angka',
                        ], 422);
                    }
                }
            }

            if ($field === 'keterangan_jra') {
                $value = strtoupper(trim($value)); // normalisasi
            }

            // =========================
            // UPDATE FIELD
            // =========================
            $arsip->$field = $value;

            // =========================
            // RECALCULATE RETENSI
            // =========================
            $retensiFields = ['aktif_tahun', 'inaktif_tahun', 'keterangan_jra', 'tanggal_arsip'];

            if (in_array($field, $retensiFields)) {
                $this->recalculateRetensi($arsip);
            }

            $arsip->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'aktif_sampai' => ! empty($arsip->aktif_sampai)
                        ? Carbon::parse($arsip->aktif_sampai)->format('d/m/Y')
                        : '-',

                    'inaktif_sampai' => ! empty($arsip->inaktif_sampai)
                        ? Carbon::parse($arsip->inaktif_sampai)->format('d/m/Y')
                        : '-',

                    'status_arsip' => $arsip->status_arsip,

                    'aktif_tahun' => $arsip->aktif_tahun ?? '-',
                    'inaktif_tahun' => $arsip->inaktif_tahun ?? '-',
                    'keterangan_jra' => $arsip->keterangan_jra ?? '-',   // <-- TAMBAHKAN INI
                ],
            ]);

        } catch (\Throwable $e) {

            Log::error('INLINE UPDATE ERROR', [
                'id' => $id,
                'field' => $request->field,
                'value' => $request->value,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function recalculateRetensi($arsip)
    {
        try {

            // =========================
            // VALIDASI TANGGAL
            // =========================
            if (! $arsip->tanggal_arsip) {
                $arsip->aktif_sampai = null;
                $arsip->inaktif_sampai = null;
                $arsip->status_arsip = 'AKTIF';

                return;
            }

            $aktifVal = strtoupper(trim((string) $arsip->aktif_tahun));
            $inaktifVal = strtoupper(trim((string) $arsip->inaktif_tahun));
            $keterangan = strtoupper(trim((string) $arsip->keterangan_jra));

            // =========================
            // JIKA KOSONG
            // =========================
            if (empty($aktifVal) || empty($inaktifVal)) {
                $arsip->aktif_sampai = null;
                $arsip->inaktif_sampai = null;
                $arsip->status_arsip = 'AKTIF';

                return;
            }

            // =========================
            // AMBIL ANGKA
            // =========================
            $aktifTahun = $this->extractNumber($aktifVal);
            $inaktifTahun = $this->extractNumber($inaktifVal);

            if (! is_numeric($aktifTahun) || ! is_numeric($inaktifTahun)) {
                return; // jangan lanjut kalau format salah
            }

            // =========================
            // HITUNG TANGGAL
            // =========================
            $tanggalDasar = $arsip->tanggal_arsip;

            try {
                $aktifSampai = Carbon::parse($tanggalDasar)
                    ->addYears((int) $aktifTahun)
                    ->toDateString();

                $inaktifSampai = Carbon::parse($aktifSampai)
                    ->addYears((int) $inaktifTahun)
                    ->toDateString();

            } catch (\Exception $e) {
                Log::error('Tanggal error', [
                    'tanggal' => $tanggalDasar,
                ]);

                return;
            }

            $arsip->aktif_sampai = $aktifSampai;
            $arsip->inaktif_sampai = $inaktifSampai;

            // =========================
            // STATUS
            // =========================
            $now = Carbon::now();

            try {
                $aktifDate = Carbon::parse($aktifSampai);
                $inaktifDate = Carbon::parse($inaktifSampai);
            } catch (\Exception $e) {
                return;
            }

            if ($keterangan === 'PERMANEN') {
                $arsip->status_arsip = 'PERMANEN';

            } elseif ($keterangan === 'MUSNAH') {

                if ($now <= $aktifDate) {
                    $arsip->status_arsip = 'AKTIF';
                } elseif ($now <= $inaktifDate) {
                    $arsip->status_arsip = 'INAKTIF';
                } else {
                    $arsip->status_arsip = 'HABIS_RETENSI';
                }

            } else {

                if ($now <= $aktifDate) {
                    $arsip->status_arsip = 'AKTIF';
                } else {
                    $arsip->status_arsip = 'INAKTIF';
                }
            }

        } catch (\Throwable $e) {

            Log::error('RECALCULATE ERROR', [
                'id' => $arsip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function extractNumber($text)
    {
        preg_match('/\d+/', $text, $matches);

        return $matches[0] ?? null;
    }

    private function addYears($date, $years)
    {
        return Carbon::parse($date)->addYears($years)->toDateString();
    }

    private function getStatusBadge($status)
    {
        $colors = [
            'AKTIF' => 'success',
            'INAKTIF' => 'warning',
            'HABIS_RETENSI' => 'danger',
            'PERMANEN' => 'info',
        ];

        $color = $colors[$status] ?? 'secondary';
        $label = ($status == 'HABIS_RETENSI') ? 'HABIS RETENSI' : $status;

        return "<span class='badge bg-{$color}'>{$label}</span>";
    }
}
