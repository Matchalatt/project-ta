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
        Schema::table('order_items', function (Blueprint $table) {
            // Menambahkan kolom product_id setelah order_id
            // nullable() agar data lama tidak error
            // constrained() untuk membuat foreign key ke tabel products
            // nullOnDelete() agar jika produk dihapus, histori tetap ada (product_id menjadi null)
            $table->foreignId('product_id')->after('order_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Hapus foreign key constraint sebelum menghapus kolom
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};