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
        Schema::create('user_group_user', function (Blueprint $table) {
            $table->uuid('user_group_id');
            $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->primary(['user_group_id', 'user_id']);

            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_group_user');
    }
};
