<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    public const CHUNK_SIZE    = 2 * 1024 * 1024;    // 2 MB per potongan (dibaca juga oleh JS)
    public const MAX_FILE_SIZE = 500 * 1024 * 1024;  // 500 MB  <-- ubah angka di sini kalau perlu
    public const ALLOWED_EXT   = ['pdf', 'jpg', 'jpeg', 'png'];
    public const ALLOWED_MIME  = ['application/pdf', 'image/jpeg', 'image/png'];

    /**
     * Batas maksimal dalam MB (untuk ditampilkan di form & pesan error).
     */
    public static function maxSizeMb(): int
    {
        return (int) (self::MAX_FILE_SIZE / 1024 / 1024);
    }

    private function disk()
    {
        return Storage::disk('local');
    }

    private function chunkDir(string $uploadId): string
    {
        // Dipisah per user supaya user lain tidak bisa menyentuh upload orang lain
        return 'chunks/' . Auth::id() . '/' . $uploadId;
    }

    private function tmpPath(string $fileName): string
    {
        return 'arsip_tmp/' . Auth::id() . '/' . $fileName;
    }

    private function validateUploadId(Request $request): string
    {
        // Hanya huruf, angka, dan strip -> mencegah path traversal (../)
        $request->validate([
            'upload_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{10,64}$/'],
        ]);

        return $request->upload_id;
    }

    /**
     * Cek potongan mana saja yang sudah diterima server (untuk resume).
     */
    public function status(Request $request)
    {
        $uploadId = $this->validateUploadId($request);
        $dir = $this->chunkDir($uploadId);

        $uploaded = [];
        if ($this->disk()->exists($dir)) {
            foreach ($this->disk()->files($dir) as $file) {
                if (preg_match('/^(\d+)\.part$/', basename($file), $m)) {
                    $uploaded[] = (int) $m[1];
                }
            }
        }
        sort($uploaded);

        return response()->json([
            'uploaded_chunks' => $uploaded,
            'chunk_size'      => self::CHUNK_SIZE,
        ]);
    }

    /**
     * Terima satu potongan file.
     */
    public function chunk(Request $request)
    {
        $uploadId = $this->validateUploadId($request);

        $request->validate([
            'chunk_index'  => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file_name'    => 'required|string|max:255',
            'file_size'    => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
            'chunk'        => 'required|file|max:' . (int) ceil(self::CHUNK_SIZE / 1024),
        ], [
            'file_size.max' => 'Ukuran file maksimal ' . self::maxSizeMb() . ' MB.',
        ]);

        $ext = strtolower(pathinfo($request->file_name, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT)) {
            return response()->json(['message' => 'Format file harus PDF, JPG, JPEG, atau PNG.'], 422);
        }

        $index         = (int) $request->chunk_index;
        $totalChunks   = (int) $request->total_chunks;
        $expectedTotal = (int) ceil($request->file_size / self::CHUNK_SIZE);

        if ($totalChunks !== $expectedTotal || $index >= $expectedTotal) {
            return response()->json(['message' => 'Data potongan tidak valid.'], 422);
        }

        // Semua potongan kecuali yang terakhir wajib berukuran penuh
        $chunkFile = $request->file('chunk');
        if ($index < $totalChunks - 1 && $chunkFile->getSize() !== self::CHUNK_SIZE) {
            return response()->json(['message' => 'Ukuran potongan tidak sesuai.'], 422);
        }

        $dir = $this->chunkDir($uploadId);

        $this->disk()->put($dir . '/meta.json', json_encode([
            'file_name'    => $request->file_name,
            'file_size'    => (int) $request->file_size,
            'total_chunks' => $totalChunks,
        ]));

        $chunkFile->storeAs($dir, $index . '.part', 'local');

        return response()->json(['ok' => true, 'chunk_index' => $index]);
    }

    /**
     * Satukan semua potongan menjadi satu file sementara.
     */
    public function finish(Request $request)
    {
        $uploadId = $this->validateUploadId($request);
        @set_time_limit(600);

        $disk = $this->disk();
        $dir  = $this->chunkDir($uploadId);

        if (!$disk->exists($dir . '/meta.json')) {
            return response()->json(['message' => 'Sesi upload tidak ditemukan, silakan upload ulang.'], 422);
        }

        $meta  = json_decode($disk->get($dir . '/meta.json'), true);
        $total = (int) $meta['total_chunks'];

        if ((int) $meta['file_size'] > self::MAX_FILE_SIZE) {
            $disk->deleteDirectory($dir);
            return response()->json(['message' => 'Ukuran file maksimal ' . self::maxSizeMb() . ' MB.'], 422);
        }

        for ($i = 0; $i < $total; $i++) {
            if (!$disk->exists("{$dir}/{$i}.part")) {
                return response()->json(['message' => 'Potongan ke-' . ($i + 1) . ' belum diterima.'], 422);
            }
        }

        $ext       = strtolower(pathinfo($meta['file_name'], PATHINFO_EXTENSION));
        $baseName  = Str::limit(Str::slug(pathinfo($meta['file_name'], PATHINFO_FILENAME)), 150, '') ?: 'dokumen';
        $finalName = time() . '_' . Str::random(5) . '_' . $baseName . '.' . $ext;
        $tmpRel    = $this->tmpPath($finalName);

        $disk->makeDirectory(dirname($tmpRel));

        // Gabungkan secara streaming (hemat memori)
        $out = fopen($disk->path($tmpRel), 'wb');
        for ($i = 0; $i < $total; $i++) {
            $in = fopen($disk->path("{$dir}/{$i}.part"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);

        $fullPath = $disk->path($tmpRel);

        if (filesize($fullPath) !== (int) $meta['file_size']) {
            $disk->delete($tmpRel);
            $disk->deleteDirectory($dir);
            return response()->json(['message' => 'Ukuran file tidak sesuai, silakan upload ulang.'], 422);
        }

        // Cek isi file sebenarnya, bukan hanya ekstensi
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($fullPath);
        if (!in_array($mime, self::ALLOWED_MIME)) {
            $disk->delete($tmpRel);
            $disk->deleteDirectory($dir);
            return response()->json(['message' => 'Isi file bukan PDF/JPG/PNG yang valid.'], 422);
        }

        $disk->deleteDirectory($dir);

        return response()->json([
            'token'     => $finalName,
            'file_name' => $meta['file_name'],
            'size'      => filesize($fullPath),
        ]);
    }

    /**
     * Batalkan upload & hapus potongan.
     */
    public function cancel(Request $request)
    {
        $uploadId = $this->validateUploadId($request);
        $this->disk()->deleteDirectory($this->chunkDir($uploadId));

        return response()->json(['ok' => true]);
    }

    /**
     * Dipanggil dari controller arsip saat store/update.
     * Memindahkan file sementara ke lokasi penyimpanan arsip.
     *
     * - Sub bagian : moveToArsip($token)                 -> disk 'public', folder 'arsip'
     * - Admin      : moveToArsip($token, 'arsip', '')    -> disk 'arsip', langsung di root disk
     *
     * Return nama file, atau null bila token tidak valid/kedaluwarsa.
     */
    public static function moveToArsip(?string $token, string $diskName = 'public', string $folder = 'arsip'): ?string
    {
        if (!$token) {
            return null;
        }

        $token  = basename($token); // cegah path traversal
        $local  = Storage::disk('local');
        $tmpRel = 'arsip_tmp/' . Auth::id() . '/' . $token;

        if (!$local->exists($tmpRel)) {
            return null;
        }

        $folder  = trim($folder, '/\\');
        $target  = Storage::disk($diskName);
        $destRel = $folder !== '' ? $folder . '/' . $token : $token;

        $srcAbs  = $local->path($tmpRel);
        $destAbs = $target->path($destRel);
        $destDir = dirname($destAbs);

        // Pastikan folder tujuan ada (pakai PHP native supaya pesan error jelas)
        if (!is_dir($destDir)) {
            if (file_exists($destDir) || is_link($destDir)) {
                throw new \RuntimeException(
                    "Lokasi penyimpanan \"{$destDir}\" bukan folder (kemungkinan file atau shortcut/junction yang rusak). " .
                    'Hapus/ganti nama item tersebut lalu buat folder biasa dengan nama yang sama.'
                );
            }

            error_clear_last();
            if (!@mkdir($destDir, 0777, true) && !is_dir($destDir)) {
                $reason = error_get_last()['message'] ?? 'tidak diketahui';
                throw new \RuntimeException("Folder \"{$destDir}\" tidak bisa dibuat: {$reason}");
            }
        }

        if (!is_writable($destDir)) {
            throw new \RuntimeException("Folder \"{$destDir}\" tidak bisa ditulis (cek Read-only / izin folder / antivirus).");
        }

        // Pindahkan file: rename dulu (instan), kalau gagal pakai copy
        error_clear_last();
        if (!@rename($srcAbs, $destAbs)) {
            if (!@copy($srcAbs, $destAbs)) {
                $reason = error_get_last()['message'] ?? 'tidak diketahui';
                throw new \RuntimeException("File tidak bisa dipindahkan ke \"{$destDir}\": {$reason}");
            }
            @unlink($srcAbs);
        }

        return $token;
    }
}