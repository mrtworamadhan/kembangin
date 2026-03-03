<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('base_price', 15, 2)->after('quantity')->nullable();
            $table->decimal('sale_price', 15, 2)->after('base_price')->nullable();
            $table->decimal('total_base_price', 15, 2)->after('subtotal')->nullable(); 
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'sale_price', 'total_base_price']);
        });
    }
};