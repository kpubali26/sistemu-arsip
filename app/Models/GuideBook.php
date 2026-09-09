<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuideBook extends Model
{
    protected $fillable = ['kategori', 'judul', 'deskripsi', 'file_path', 'file_name', 'uploaded_by'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeUnitKearsipan($query)
    {
        return $query->where('kategori', 'unit_kearsipan');
    }

    public function scopeSubbagian($query)
    {
        return $query->where('kategori', 'subbagian');
    }
}