<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sesuaikan Tabel Accounts
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->change(); // Sekarang boleh kosong
        });

        // 2. Sesuaikan Tabel Categories
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->change();
        });

        // 3. Sesuaikan Tabel Transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->change();
        });

        // 4. BUAT TABEL BARU: GOALS (Target Tabungan)
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name'); // Misal: "Liburan ke Jepang", "Dana Darurat"
            $table->decimal('target_amount', 15, 2); // Target nominal
            $table->decimal('current_amount', 15, 2)->default(0); // Uang yang sudah terkumpul
            $table->date('deadline')->nullable(); // Target tercapai kapan
            $table->string('status')->default('active'); // active / achieved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};