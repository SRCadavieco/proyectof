<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printify_connections', function (Blueprint $table) {
            $table->unsignedInteger('products_pushed')->default(0)->after('shop_name');
        });
    }

    public function down(): void
    {
        Schema::table('printify_connections', function (Blueprint $table) {
            $table->dropColumn('products_pushed');
        });
    }
};
