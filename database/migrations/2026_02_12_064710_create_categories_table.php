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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['income', 'expense', 'transfer']);
            
            $table->enum('group', ['business', 'personal'])->default('business'); 
            $table->enum('nature', ['need', 'want', 'saving'])->nullable(); // Kebutuhan vs Keinginan
            $table->enum('productivity', ['productive', 'consumptive', 'neutral'])->nullable(); // Produktif vs Konsumtif
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
