<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::create('guide_books', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['unit_kearsipan', 'subbagian']); // target pengguna
            $table->string('judul');
            $table->string('deskripsi')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_books');
    }
};
