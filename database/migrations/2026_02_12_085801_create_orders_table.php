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
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('number')->unique(); // INV-20230001
            $table->decimal('total_amount', 15, 2)->default(0);
            
            // Status Order
            $table->enum('status', ['new', 'processing', 'completed', 'cancelled'])->default('new');
            
            // Status Pembayaran
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            
            $table->date('order_date');
            $table->date('due_date')->nullable(); // Jatuh tempo
            $table->text('notes')->nullable();
            
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
