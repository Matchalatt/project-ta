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
        // Tabel ini untuk menyimpan varian, misal: Pastel Kecil, Pastel Besar
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke produk utamanya
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nama varian, cth: "Kecil"
            $table->decimal('price', 10, 2); // Harga spesifik untuk varian ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};