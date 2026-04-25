<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service', 50);              // 'together', 'chutes', 'gemini', 'rnbulktools'
            $table->string('model', 100)->nullable();   // model name used
            $table->string('operation', 50)->default('generate'); // 'generate', 'remove_bg', 'img2img'
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('cost_usd', 10, 6)->default(0); // estimated cost
            $table->boolean('success')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_logs');
    }
};
