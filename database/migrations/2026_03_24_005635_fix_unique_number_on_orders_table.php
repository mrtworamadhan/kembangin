<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // 1. Hapus unique key yang lama (biasanya nama indexnya: orders_number_unique)
            $table->dropUnique(['number']); 

            // 2. Buat unique key baru gabungan number dan business_id
            $table->unique(['number', 'business_id']);
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['number', 'business_id']);
            $table->unique(['number']); // Kembalikan ke asal jika rollback
        });
    }
};
