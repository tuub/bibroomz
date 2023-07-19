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
            $table->uuid('institution_id');
            $table->foreign('institution_id')->references('id')->on('institutions');
            $table->string('title');
            $table->text('location')->nullable();
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
