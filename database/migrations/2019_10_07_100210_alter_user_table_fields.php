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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->string('name')->unique()->change();
            $table->string('email')->nullable()->change();
            $table->boolean('is_admin')->default(false)->after('email');
            $table->timestamp('last_login')->nullable()->after('is_admin');
            $table->boolean('is_logged_in')->default(false)->after('last_login');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->dropColumn(['is_admin', 'last_login', 'is_logged_in']);
    }
};
