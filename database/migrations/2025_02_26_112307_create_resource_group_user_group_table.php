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
        Schema::create('resource_group_user_group', function (Blueprint $table) {
            $table->uuid('resource_group_id');
            $table->foreign('resource_group_id')->references('id')->on('resource_groups')->onDelete('cascade');

            $table->uuid('user_group_id');
            $table->foreign('user_group_id')->references('id')->on('user_groups')->onDelete('cascade');

            $table->primary(['resource_group_id', 'user_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_group_user_group');
    }
};
