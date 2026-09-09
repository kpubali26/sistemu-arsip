<?php


namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\GuideBook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuideBookManagementController extends Controller
{
    // public function __construct()
    // {
    //     // extra safety, walau sudah dibungkus middleware role di routes
    //     $this->middleware(function ($request, $next) {
    //         if (!in_array(auth()->user()->role, ['super_admin'])) {
    //             abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    //         }
    //         return $next($request);
    //     });
    // }

    public function index(Request $request)
    {
        $query = GuideBook::query()->with('uploader')->latest();

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->filled('search')) {
            $query->where('judul', 'like', '%'.$request->search.'%');
        }

        $guides = $query->paginate(10)->withQueryString();

        return view('superadmin.guide-books.index', compact('guides'));
    }

    public function create()
    {
        return view('superadmin.guide-books.create');
    }

    public function store(Request $request)
    {
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

        return redirect()->route('superadmin.guide-books.index')
            ->with('success', 'Panduan berhasil ditambahkan.');
    }

    public function edit(GuideBook $guideBook)
    {
        return view('superadmin.guide-books.edit', compact('guideBook'));
    }

    public function update(Request $request, GuideBook $guideBook)
    {
        $request->validate([
            'kategori' => 'required|in:unit_kearsipan,subbagian',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string|max:500',
            'file' => 'nullable|mimes:pdf|max:20480',
        ]);

        $data = [
            'kategori' => $request->kategori,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
        ];

        // ganti file kalau ada upload baru
        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($guideBook->file_path);

            $file = $request->file('file');
            $path = $file->store('guide-books', 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
        }

        $guideBook->update($data);

        return redirect()->route('superadmin.guide-books.index')
            ->with('success', 'Panduan berhasil diperbarui.');
    }

    public function destroy(GuideBook $guideBook)
    {
        Storage::disk('public')->delete($guideBook->file_path);
        $guideBook->delete();

        return back()->with('success', 'Panduan berhasil dihapus.');
    }

    public function download(GuideBook $guideBook)
    {
        if (!Storage::disk('public')->exists($guideBook->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return Storage::disk('public')->download($guideBook->file_path, $guideBook->file_name);
    }
}