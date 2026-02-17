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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Pemilik (Kamu)
            $table->string('name');
            $table->string('slug')->nullable();
            $table->enum('type', ['personal', 'business'])->default('business');
            $table->foreignId('business_type_id')->nullable()->constrained('business_types')->nullOnDelete();
            $table->string('currency')->default('IDR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
