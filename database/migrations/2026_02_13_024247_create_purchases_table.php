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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            
            $table->string('number'); // No. Nota dari Suplier / Generate Sendiri
            $table->date('date');
            
            // Status Barang (Mempengaruhi Stok)
            $table->enum('status', ['pending', 'ordered', 'received', 'cancelled'])->default('pending');
            
            // Status Pembayaran (Mempengaruhi Kas/Expense)
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable(); // Foto Nota/Faktur
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
