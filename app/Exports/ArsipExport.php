<?php

namespace App\Exports;

use App\Models\Arsip;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\{
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\{
    Alignment,
    Border
};
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use App\Models\Satker;

class ArsipExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected Request $request;
    protected array $columns;
    protected array $selectedIds;

    /**
     * Constructor sekarang menerima selectedIds (array ID yang dicentang user)
     */
    public function __construct(Request $request, array $columns, array $selectedIds = [])
    {
        $this->request = $request;
        $this->columns = $columns;
        $this->selectedIds = $selectedIds;
    }

    /**
     * Query: jika ada selectedIds, hanya ambil data itu.
     * Jika tidak, gunakan semua filter yang sudah ada.
     *
     * PERBAIKAN: eager load relasi 'rak' dan 'box' supaya kolom
     * nomor_rak & nomor_box bisa diambil lewat relasi, bukan
     * lewat kolom langsung di tabel arsips yang tidak ada.
     */
    public function query()
    {
        $query = Arsip::query()->with(['kodeKlasifikasi', 'subBagian', 'rak', 'box']);

        // ==========================================
        // KONDISIONAL BERDASARKAN SUB_BAGIAN_ID USER
        // ==========================================
        if (auth()->user()->sub_bagian_id) {
            // USER BIASA (punya sub_bagian_id)
            // Hanya arsip milik sub_bagiannya DAN status_pindah = 'BELUM'
            $query->where('sub_bagian_id', auth()->user()->sub_bagian_id)
                  ->where('status_pindah', 'BELUM');
        } else {
            // ADMIN (tidak punya sub_bagian_id)
            // Bisa disesuaikan: misal export semua arsip atau dengan filter status tertentu
            // Contoh: tetap gunakan whereIn seperti sebelumnya
            $query->whereIn('status_pindah', ['DIPINDAHKAN', 'LANGSUNG']);
            // Atau jika ingin export semua tanpa filter status_pindah:
            // $query->whereNotNull('id'); // semua data
        }

        // ==========================================
        // JIKA ADA SELECTED IDS (checkbox dari user)
        // ==========================================
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
            return $query;
        }

        // ==========================================
        // FILTER TAMBAHAN DARI REQUEST
        // ==========================================
        if ($this->request->filled('tahun_arsip')) {
            $query->where('tahun_arsip', $this->request->tahun_arsip);
        }
        if ($this->request->filled('status_arsip')) {
            $query->where('status_arsip', $this->request->status_arsip);
        }
        if ($this->request->filled('sub_bagian_id')) {
            $query->where('sub_bagian_id', $this->request->sub_bagian_id);
        }
        if ($this->request->filled('kode_klasifikasi_id')) {
            $query->where('kode_klasifikasi_id', $this->request->kode_klasifikasi_id);
        }

        // PERBAIKAN: filter nomor_rak & nomor_box seharusnya mengarah
        // ke rak_id / box_id (FK), bukan ke kolom nomor_rak/nomor_box
        // yang tidak ada di tabel arsips.
        if ($this->request->filled('nomor_rak')) {
            $query->where('rak_id', $this->request->nomor_rak);
        }
        if ($this->request->filled('nomor_box')) {
            $query->where('box_id', $this->request->nomor_box);
        }

        if ($this->request->filled('keterangan')) {
            $query->where('keterangan', $this->request->keterangan);
        }

        $query->orderBy('tahun_arsip', 'asc');
        return $query;
    }

    /**
     * Header kolom hanya sesuai dengan kolom yang dipilih user.
     * Tidak ada padding paksa ke 10 kolom.
     */
    public function headings(): array
    {
        $map = [
            'kode_klasifikasi' => 'Kode Klasifikasi',
            'uraian_arsip'     => 'Judul Arsip',
            'jumlah'           => 'Jumlah',
            'tahun_arsip'      => 'Tahun',
            'nomor_rak'        => 'Rak',
            'nomor_box'        => 'Box',
            'lokasi_arsip'     => 'Lokasi Arsip',
            'no_sampul'        => 'No Sampul',
            'aktif_sampai'     => 'Aktif Sampai',
            'inaktif_sampai'   => 'Inaktif Sampai',
            'status_arsip'     => 'Status Arsip',
            'klasifikasi_keamanan' => 'Klasifikasi Keamanan',
            'sub_bagian'       => 'Sub Bagian',
            'keterangan'       => 'Keterangan',
            'tingkat_perkembangan' => 'Tingkat Perkembangan',
            'keterangan_jra'   => 'Keterangan JRA',
        ];

        $headings = [];
        foreach ($this->columns as $col) {
            $headings[] = $map[$col] ?? ucfirst(str_replace('_', ' ', $col));
        }

        return $headings;
    }

    /**
     * Data per baris: hanya kolom yang dipilih.
     *
     * PERBAIKAN: nomor_rak & nomor_box diambil dari relasi rak()/box(),
     * bukan dari properti langsung $arsip->nomor_rak / $arsip->nomor_box
     * yang tidak ada di model Arsip (yang ada rak_id / box_id).
     *
     * NOTE: sesuaikan nama field 'nomor_rak' pada MasterRak dan
     * 'nomor_box' pada MasterBox dengan nama kolom sebenarnya
     * di masing-masing tabel (misal bisa jadi 'kode_rak', 'nama_rak', dst).
     */
    public function map($arsip): array
    {
        $data = [];

        foreach ($this->columns as $col) {
            $data[] = match ($col) {
                'kode_klasifikasi' => $arsip->kodeKlasifikasi->kode ?? '-',
                'uraian_arsip'     => $arsip->uraian_arsip ?? '-',
                'jumlah'           => $this->formatJumlah($arsip),
                'tahun_arsip'      => $arsip->tahun_arsip ?? '-',
                'nomor_rak'        => $arsip->rak->nomor_rak ?? '-',
                'nomor_box'        => $arsip->box->nomor_box ?? '-',
                'lokasi_arsip'     => $arsip->lokasi_arsip ? \App\Models\Arsip::getLokasiArsipLabel($arsip->lokasi_arsip) : '-',
                'no_sampul'        => $arsip->no_sampul ?? '-',
                'aktif_sampai'     => $arsip->aktif_sampai ? \Carbon\Carbon::parse($arsip->aktif_sampai)->format('d-m-Y') : '-',
                'inaktif_sampai'   => $arsip->inaktif_sampai ? \Carbon\Carbon::parse($arsip->inaktif_sampai)->format('d-m-Y') : '-',
                'status_arsip'     => $this->formatStatus($arsip->status_arsip),
                'klasifikasi_keamanan' => $arsip->klasifikasi_keamanan ?? '-',
                'sub_bagian'       => $arsip->subBagian->nama_sub_bagian ?? '-',
                'keterangan'       => $arsip->keterangan ?? '-',
                'tingkat_perkembangan' => $arsip->tingkat_perkembangan ?? '-',
                'keterangan_jra'   => $arsip->keterangan_jra ?? '-',
                default            => '-',
            };
        }

        return $data;
    }

    /**
     * Format jumlah: misal "5 Bendel"
     */
    private function formatJumlah($arsip): string
    {
        if ($arsip->jumlah_berkas && $arsip->satuan_arsip) {
            return "{$arsip->jumlah_berkas} {$arsip->satuan_arsip}";
        }
        return '-';
    }

    /**
     * Format status arsip huruf kapital
     */
    private function formatStatus($status): string
    {
        return [
            'AKTIF' => 'AKTIF',
            'INAKTIF' => 'INAKTIF',
            'HABIS_RETENSI' => 'HABIS RETENSI',
            'PERMANEN' => 'PERMANEN',
            'MUSNAH' => 'MUSNAH'
        ][$status] ?? $status;
    }

    /**
     * Event untuk styling, border, dan penulisan total.
     * Sekarang semua dimensi berdasarkan jumlah kolom riil.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Jumlah kolom yang SEBENARNYA dipilih user
                $columnCount = count($this->columns);
                // Minimal 1 kolom (jika kosong tidak mungkin, tapi safety)
                if ($columnCount < 1) {
                    $columnCount = 1;
                }
                $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells("A1:{$lastColumn}1");

                $satkerAktif = Satker::aktif();
                $namaSatker = $satkerAktif ? $satkerAktif->nama_satker : 'KPU PROVINSI BALI';
                $sheet->setCellValue('A1', 'DAFTAR ARSIP ' . strtoupper($namaSatker));
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Header berada di baris 3 (baris 2 kosong)
                $headerRow = 3;
                $lastRow = $sheet->getHighestRow();

                // ===== 1b. Bold + Center untuk baris heading =====
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")
                    ->applyFromArray([
                        'font' => ['bold' => true],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER
                        ]
                    ]);

                // ===== 2. Border untuk seluruh data =====
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                        ]
                    ]);

                // ===== 3. Hitung total Bendel dan Lembar dari data YANG SAMA dengan query =====
                // Gunakan query yang sama (termasuk selectedIds jika ada)
                $query = $this->query(); // reuse logic dari method query()

                $totalBendel = 0;
                $totalLembar = 0;

                foreach ($query->cursor() as $arsip) {
                    $satuan = strtolower($arsip->satuan_arsip);
                    if ($satuan === 'bendel') {
                        $totalBendel += $arsip->jumlah_berkas;
                    } elseif ($satuan === 'lembar') {
                        $totalLembar += $arsip->jumlah_berkas;
                    }
                }

                $totalRow = $lastRow + 2;
                $startCell = "A{$totalRow}";
                $endCell = "{$lastColumn}{$totalRow}";

                if ($lastColumn !== 'A') {
                    $sheet->mergeCells("{$startCell}:{$endCell}");
                }

                $sheet->setCellValue($startCell, "TOTAL: {$totalBendel} Bendel, {$totalLembar} Lembar");
                $sheet->getStyle($startCell)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
                ]);

                foreach (range(1, $columnCount) as $i) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1);
            }
        ];
    }
}