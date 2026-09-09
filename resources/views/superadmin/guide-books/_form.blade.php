@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label class="form-label">Kategori</label>
    <select name="kategori" class="form-select" required>
        <option value="">-- Pilih Kategori --</option>
        <option value="unit_kearsipan" {{ old('kategori', $guideBook->kategori ?? '') == 'unit_kearsipan' ? 'selected' : '' }}>Unit Kearsipan (Admin/TU)</option>
        <option value="subbagian" {{ old('kategori', $guideBook->kategori ?? '') == 'subbagian' ? 'selected' : '' }}>Sub Bagian (User)</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Judul</label>
    <input type="text" name="judul" class="form-control" value="{{ old('judul', $guideBook->judul ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Deskripsi (opsional)</label>
    <textarea name="deskripsi" class="form-control" rows="2">{{ old('deskripsi', $guideBook->deskripsi ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">File PDF {{ isset($guideBook) ? '(kosongkan jika tidak ingin mengganti)' : '' }}</label>
    <input type="file" name="file" class="form-control" accept="application/pdf" {{ isset($guideBook) ? '' : 'required' }}>
    @if(isset($guideBook))
        <small class="text-muted d-block mt-1">
            File saat ini: <i class="bi bi-file-earmark-pdf text-danger"></i> {{ $guideBook->file_name }}
        </small>
    @endif
</div>