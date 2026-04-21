<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printify_connections', function (Blueprint $table) {
            $table->text('api_token')->change();
        });
    }

    public function down(): void
    {
        Schema::table('printify_connections', function (Blueprint $table) {
            $table->string('api_token')->change();
        });
    }
};
