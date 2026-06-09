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
        Schema::table('users', function (Blueprint $table): void {
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
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_admin', 'last_login', 'is_logged_in']);
        });
    }
};
