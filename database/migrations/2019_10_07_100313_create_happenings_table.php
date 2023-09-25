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
        Schema::create('happenings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->uuid('user_id_01');
            $table->foreign('user_id_01')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->uuid('user_id_02')->nullable();
            $table->foreign('user_id_02')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->uuid('resource_id')->nullable();
            $table->foreign('resource_id')->references('id')->on('resources')->onUpdate('cascade')->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->string('verifier')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->dateTime('reserved_at');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('happenings');
    }
};
