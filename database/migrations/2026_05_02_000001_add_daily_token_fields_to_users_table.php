<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tracks total tokens granted in the current calendar month (upfront + daily).
            // Used to enforce the per-plan monthly cap without ever zeroing the balance.
            $table->integer('tokens_given_this_month')->default(0)->after('tokens_reset_at');

            // Tracks when daily tokens were last granted so we can skip duplicates.
            $table->timestamp('daily_tokens_given_at')->nullable()->after('tokens_given_this_month');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tokens_given_this_month', 'daily_tokens_given_at']);
        });
    }
};
