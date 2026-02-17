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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(); // Jangan cascade delete, biar history aman
            
            $table->string('product_name'); // Snapshot nama produk saat beli (jaga2 kalau nama asli berubah)
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2); // Harga saat transaksi
            $table->decimal('subtotal', 15, 2); // quantity * unit_price
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
