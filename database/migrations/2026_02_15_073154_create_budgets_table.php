<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            
            // Siapa yang buat budget ini (Nanti dikaitkan dengan household_id untuk keluarga)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Kategori apa yang dibudgetin (Misal: SPP, Dapur, Listrik)
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            
            // Nominal target maksimal per bulan
            $table->decimal('amount', 15, 2); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};