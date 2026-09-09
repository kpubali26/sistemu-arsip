<div class="d-flex justify-content-between align-items-center border-bottom py-3">
    <div>
        <strong><i class="bi bi-file-earmark-pdf text-danger"></i> {{ $guide->judul }}</strong>
        @if($guide->deskripsi)
            <p class="mb-0 text-muted fs-sm">{{ $guide->deskripsi }}</p>
        @endif
        <small class="text-muted">Diunggah oleh {{ $guide->uploader->name ?? '-' }} • {{ $guide->created_at->format('d M Y') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('guide-book.download', $guide) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-download"></i> Unduh
        </a>
        @if($canManage)
        <form action="{{ route('guide-book.destroy', $guide) }}" method="POST" onsubmit="return confirm('Hapus panduan ini?')">
            @csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
        </form>
        @endif
    </div>
</div>