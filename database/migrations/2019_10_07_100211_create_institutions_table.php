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
        Schema::dropIfExists('institutions');
        Schema::create('institutions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->text('title');
            $table->string('short_title');
            $table->string('slug');
            $table->string('location')->nullable();
            $table->string('home_uri')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_uri')->nullable();
            $table->string('teaser_uri')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('institutions');
    }
};
