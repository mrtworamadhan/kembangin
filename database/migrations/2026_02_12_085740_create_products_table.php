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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable(); // Stock Keeping Unit (Kode Barang)
            $table->decimal('price', 15, 2)->default(0); // Harga Jual
            $table->decimal('cost', 15, 2)->default(0); // Harga Modal (HPP)
            $table->integer('stock')->default(0);
            $table->boolean('is_service')->default(false); // True = Jasa (Gak pake stok)
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
