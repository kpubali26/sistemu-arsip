<?php

// database/seeders/GuideBookSeeder.php
namespace Database\Seeders;

use App\Models\GuideBook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GuideBookSeeder extends Seeder
{
    public function run(): void
    {
        $files = [
            [
                'kategori'   => 'unit_kearsipan',
                'judul'      => 'Panduan Unit Kearsipan',
                'deskripsi'  => 'Panduan penggunaan sistem untuk Unit Kearsipan',
                'source'     => database_path('seeders/files/panduan-unit-kearsipan.pdf'),
                'file_name'  => 'panduan-unit-kearsipan.pdf',
            ],
            [
                'kategori'   => 'subbagian',
                'judul'      => 'Panduan Sub Bagian',
                'deskripsi'  => 'Panduan penggunaan sistem untuk Sub Bagian',
                'source'     => database_path('seeders/files/panduan-subbagian.pdf'),
                'file_name'  => 'panduan-subbagian.pdf',
            ],
        ];

        foreach ($files as $item) {

            // skip kalau judul+kategori sudah ada (supaya tidak dobel tiap kali seed ulang)
            $exists = GuideBook::where('judul', $item['judul'])
                ->where('kategori', $item['kategori'])
                ->exists();

            if ($exists) {
                continue;
            }

            if (!file_exists($item['source'])) {
                $this->command->warn("File tidak ditemukan: {$item['source']}");
                continue;
            }

            // salin file ke storage/app/public/guide-books
            $destination = 'guide-books/' . $item['file_name'];
            Storage::disk('public')->put(
                $destination,
                file_get_contents($item['source'])
            );

            GuideBook::create([
                'kategori'    => $item['kategori'],
                'judul'       => $item['judul'],
                'deskripsi'   => $item['deskripsi'],
                'file_path'   => $destination,
                'file_name'   => $item['file_name'],
                'uploaded_by' => null, // atau isi id user admin default
            ]);
        }
    }
}