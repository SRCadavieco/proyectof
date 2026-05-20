<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etsy_listings', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->string('title');
            $table->string('price')->nullable();
            $table->string('url')->unique();
            $table->string('image')->nullable();
            $table->json('tags')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etsy_listings');
    }
};
