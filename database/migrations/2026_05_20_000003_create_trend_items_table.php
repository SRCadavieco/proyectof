<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trend_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')
                ->constrained('trend_clusters')
                ->cascadeOnDelete();
            $table->foreignId('listing_id')
                ->constrained('etsy_listings')
                ->cascadeOnDelete();
            $table->float('similarity_score')->default(0);
            $table->timestamps();

            $table->unique(['cluster_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trend_items');
    }
};
