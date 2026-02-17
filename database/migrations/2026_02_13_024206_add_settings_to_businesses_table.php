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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('logo')->nullable(); // Path gambar logo
            $table->string('invoice_theme')->default('default'); // 'modern', 'classic', 'simple'
            $table->boolean('use_stock_management')->default(true); // Toggle Fitur Stok Global
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['logo', 'invoice_theme', 'use_stock_management']);
        });
    }
};
