<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('summary')->nullable();
            $table->json('top_keywords')->nullable();
            $table->json('embedding_vector')->nullable();
            $table->float('score')->default(0);
            $table->float('growth_rate')->default(0);
            $table->float('competition_score')->default(0);
            $table->integer('listing_count')->default(0);
            $table->string('keyword')->nullable()->index();
            $table->timestamps();

            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_clusters');
    }
};
