// database/migrations/xxxx_xx_xx_xxxxxx_create_cart_items_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_option_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Mencegah duplikasi item yang sama persis (produk + varian) dalam satu keranjang
            $table->unique(['cart_id', 'product_id', 'product_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};