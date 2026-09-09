<?php

namespace App\Http\Controllers;

use App\Models\GuideBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuideBookController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;

        if (in_array($role, ['admin', 'super_admin', 'tu'])) {
            // Unit Kearsipan hanya lihat panduan kategori unit_kearsipan
            $guides = GuideBook::unitKearsipan()->latest()->get();
            $kategoriLabel = 'Unit Kearsipan';
        } else {
            // Sub Bagian hanya lihat panduan kategori subbagian
            $guides = GuideBook::subbagian()->latest()->get();
            $kategoriLabel = 'Sub Bagian';
        }

        return view('guide-book.index', compact('guides', 'kategoriLabel'));
    }

    public function store(Request $request)
    {
        $this->authorizeUpload($request->kategori);

        $request->validate([
            'kategori' => 'required|in:unit_kearsipan,subbagian',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'file' => 'required|mimes:pdf|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store('guide-books', 'public');

        GuideBook::create([
            'kategori' => $request->kategori,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Panduan berhasil diunggah.');
    }

    public function download(GuideBook $guideBook)
    {
        $this->authorizeView($guideBook->kategori);

        if (!Storage::disk('public')->exists($guideBook->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download(
            $guideBook->file_path,
            $guideBook->file_name
        );
    }

    public function destroy(GuideBook $guideBook)
    {
        $this->authorizeUpload($guideBook->kategori);

        Storage::disk('public')->delete($guideBook->file_path);
        $guideBook->delete();

        return back()->with('success', 'Panduan berhasil dihapus.');
    }

    // Hanya admin/super_admin/tu yang boleh upload & hapus (kedua kategori)
    private function authorizeUpload($kategori)
    {
        $role = auth()->user()->role;

        if (!in_array($role, ['admin', 'super_admin', 'tu'])) {
            abort(403, 'Anda tidak memiliki akses untuk mengunggah panduan.');
        }
    }

    // Kontrol siapa yang boleh download kategori tertentu
    private function authorizeView($kategori)
    {
        $role = auth()->user()->role;

        if ($kategori === 'unit_kearsipan' && !in_array($role, ['admin', 'super_admin', 'tu'])) {
            abort(403, 'Anda tidak memiliki akses ke panduan ini.');
        }
        // kategori 'subbagian' bisa diakses semua role yang login
    }
}