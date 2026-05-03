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
        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32)->default('stripe');
            $table->string('event_type', 64);
            $table->string('description')->nullable();
            $table->string('plan', 32)->nullable();
            $table->integer('tokens')->nullable();
            $table->decimal('amount_usd', 8, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('reference', 191)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->unique(['source', 'reference', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_events');
    }
};
