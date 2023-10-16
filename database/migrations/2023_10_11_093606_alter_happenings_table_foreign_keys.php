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
        Schema::table('happenings', function (Blueprint $table) {
            $table->dropForeign(['user_id_01']);
            $table->uuid('user_id_01')->nullable()->change();
            $table->foreign('user_id_01')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();

            $table->dropForeign(['user_id_02']);
            $table->foreign('user_id_02')->references('id')->on('users')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('happenings', function (Blueprint $table) {
            $table->dropForeign(['user_id_01']);
            $table->foreign('user_id_01')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();

            $table->dropForeign(['user_id_02']);
            $table->foreign('user_id_02')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('happenings', function (Blueprint $table) {
            $table->uuid('user_id_01')->nullable(false)->change();
        });
    }
};
