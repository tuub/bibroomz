<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('resources');
        Schema::create('resources', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->uuid('resource_group_id');
            $table->foreign('resource_group_id')->references('id')->on('resource_groups')->onDelete('cascade');
            $table->text('title');
            $table->text('location')->nullable();
            $table->text('location_uri')->nullable();
            $table->text('description')->nullable();
            $table->string('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verification_required')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('resources');
    }
};
