<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupService
{
    protected string $disk = 'local';           // disk khusus penyimpanan hasil backup
    protected string $backupFolder = 'backups';   // storage/app/backups

    public function run(callable $progressCallback = null): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_His');
        $filename  = "backup-{$timestamp}.zip";

        $tempDir = storage_path('app/tmp-backup-' . $timestamp);
        File::ensureDirectoryExists($tempDir);

        // 1) Dump database ke file .sql
        $sqlPath = $tempDir . '/database.sql';
        $this->dumpDatabase($sqlPath);
        if ($progressCallback) $progressCallback(30, 'Database dump selesai');

        // 2) Siapkan lokasi zip final
        Storage::disk($this->disk)->makeDirectory($this->backupFolder);
        $zipPath = Storage::disk($this->disk)->path("{$this->backupFolder}/{$filename}");

        // 3) Buat zip: masukkan database.sql + seluruh isi storage/app (kecuali folder backups & tmp)
        $this->buildZip($zipPath, $sqlPath, $progressCallback);

        // 4) Bersihkan file sementara
        File::deleteDirectory($tempDir);

        if ($progressCallback) $progressCallback(100, 'Backup selesai');

        return "{$this->backupFolder}/{$filename}";
    }

    protected function dumpDatabase(string $outputPath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        // Pakai env var utk password, biar tidak kelihatan di process list (ps aux)
       $process = new Process([
    config('backup.mysqldump_path'),   // <-- pakai config, bukan hardcode 'mysqldump'
    '-h', $config['host'],
    '-P', (string)($config['port'] ?? 3306),
    '-u', $config['username'],
    '--single-transaction',
    '--quick',
    '--lock-tables=false',
    $config['database'],
]);
        $process->setEnv(['MYSQL_PWD' => $config['password']]);
        $process->setTimeout(3600); // 1 jam, sesuaikan kalau DB besar

        $process->run(function ($type, $buffer) use ($outputPath) {
            file_put_contents($outputPath, $buffer, FILE_APPEND);
        });

        if (!$process->isSuccessful()) {
            Log::error('Backup DB gagal: ' . $process->getErrorOutput());
            throw new \RuntimeException('mysqldump gagal dijalankan. Cek apakah mysqldump ter-install & PATH sudah benar.');
        }
    }

    // protected function buildZip(string $zipPath, string $sqlPath, callable $progressCallback = null): void
    // {
    //     $zip = new ZipArchive();
    //     if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    //         throw new \RuntimeException("Gagal membuat file zip di {$zipPath}");
    //     }

    //     // masukkan sql dump
    //     $zip->addFile($sqlPath, 'database.sql');

    //     // kumpulkan semua file di storage/app (sesuaikan sumbernya, bisa juga public/storage)
    //     $sourcePath = storage_path('app');
    //     $excludeDirs = [
    //         storage_path('app/' . $this->backupFolder),
    //         storage_path('app/framework'), // cache framework, biasanya gak perlu
    //     ];

    //     $files = new \RecursiveIteratorIterator(
    //         new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS)
    //     );

    //     $total = iterator_count($files);
    //     $files->rewind();
    //     $count = 0;

    //     foreach ($files as $file) {
    //         $count++;
    //         $filePath = $file->getRealPath();

    //         // skip folder backup itu sendiri & folder tmp
    //         foreach ($excludeDirs as $ex) {
    //             if (str_starts_with($filePath, $ex)) {
    //                 continue 2;
    //             }
    //         }
    //         if (str_starts_with($filePath, storage_path('app/tmp-backup-'))) {
    //             continue;
    //         }

    //         if ($file->isFile()) {
    //             $relativePath = 'storage/' . substr($filePath, strlen($sourcePath) + 1);
    //             $zip->addFile($filePath, $relativePath);
    //         }

    //         // update progress tiap 200 file biar gak spam callback
    //         if ($progressCallback && $count % 200 === 0) {
    //             $percent = 30 + (int) round(($count / max($total, 1)) * 60); // 30-90%
    //             $progressCallback($percent, "Mengarsipkan file ({$count}/{$total})");
    //         }
    //     }

    //     $zip->close();
    // }

    protected function buildZip(string $zipPath, string $sqlPath, callable $progressCallback = null): void
{
      Log::info('ENV langsung: [' . env('ARSIP_STORAGE_PATH') . ']');
   Log::info('Config backup lengkap: ' . json_encode(config('backup')));
   Log::info('Config arsip_storage_path: [' . config('backup.arsip_storage_path') . ']');
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new \RuntimeException("Gagal membuat file zip di {$zipPath}");
    }

    $zip->addFile($sqlPath, 'database.sql');

    $sourcePath = storage_path('app');
    $excludeDirs = [
        storage_path('app/' . $this->backupFolder),
        storage_path('app/framework'),
    ];

    // path arsip eksternal (bisa beda drive per instalasi kab/kota)
    $arsipPath = rtrim(config('backup.arsip_storage_path'), '\\/');
    $isArsipExternal = $arsipPath
        && is_dir($arsipPath)
        && !str_starts_with(realpath($arsipPath), realpath($sourcePath));

    // hitung total file dari kedua sumber, biar progress bar akurat
    $mainFiles = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS)
    );
    $total = iterator_count($mainFiles);

    if ($isArsipExternal) {
        $arsipFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($arsipPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $total += iterator_count($arsipFiles);
    }

    $count = 0;

    // --- 1) proses storage/app seperti biasa ---
    $mainFiles->rewind();
    foreach ($mainFiles as $file) {
        $count++;
        $filePath = $file->getRealPath();

        foreach ($excludeDirs as $ex) {
            if (str_starts_with($filePath, $ex)) {
                continue 2;
            }
        }
        if (str_starts_with($filePath, storage_path('app/tmp-backup-'))) {
            continue;
        }

        if ($file->isFile()) {
            $relativePath = 'storage/' . substr($filePath, strlen($sourcePath) + 1);
            $zip->addFile($filePath, $relativePath);
        }

        if ($progressCallback && $count % 200 === 0) {
            $percent = 30 + (int) round(($count / max($total, 1)) * 60);
            $progressCallback($percent, "Mengarsipkan file ({$count}/{$total})");
        }
    }

    // --- 2) proses folder arsip eksternal, kalau memang di luar storage/app ---
    if ($isArsipExternal) {
        $arsipFiles->rewind();
        foreach ($arsipFiles as $file) {
            $count++;
            $filePath = $file->getRealPath();

            if ($file->isFile()) {
                $relativePath = 'arsip-eksternal/' . substr($filePath, strlen($arsipPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }

            if ($progressCallback && $count % 200 === 0) {
                $percent = 30 + (int) round(($count / max($total, 1)) * 60);
                $progressCallback($percent, "Mengarsipkan file eksternal ({$count}/{$total})");
            }
        }
    }

    $zip->close();
}
}