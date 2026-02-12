<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('design_generations', function (Blueprint $table) {
            $table->id();
            $table->string('prompt');
            $table->string('image_url')->nullable();
            $table->string('task_id');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_generations');
    }
};
