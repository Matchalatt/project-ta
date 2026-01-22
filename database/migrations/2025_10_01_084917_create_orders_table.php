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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_whatsapp');
            $table->string('delivery_method'); // pickup atau delivery
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_price', 15, 2);
            $table->string('status')->default('unpaid'); // unpaid, paid, dll.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
