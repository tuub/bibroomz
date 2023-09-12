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
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('roles_name_unique');
            $table->text('name')->change();
            $table->text('description')->change();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex('permissions_name_unique');
            $table->text('name')->change();
            $table->text('description')->change();
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->text('title')->change();
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->text('title')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->unique()->change();
            $table->string('description')->change();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('name')->unique()->change();
            $table->string('description')->change();
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->string('title')->change();
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->string('title')->change();
        });
    }
};
