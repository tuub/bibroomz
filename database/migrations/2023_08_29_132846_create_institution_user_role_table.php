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
        Schema::create('institution_user_role', function (Blueprint $table) {
            $table->uuid('institution_id');
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->uuid('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            $table->primary(['institution_id', 'user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('institution_user_role');
    }
};
