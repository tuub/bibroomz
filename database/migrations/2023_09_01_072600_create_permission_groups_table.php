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
        Schema::create('permission_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->foreignUUid('group_id')->nullable()->references('id')->on('permission_groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // MariaDB will not drop a table while another table still references
        // it, and permissions.group_id references permission_groups, so that
        // reference has to go first. The column goes with it: up() added it,
        // and leaving it behind would make the next run of up() fail on a
        // duplicate column.
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('permission_groups');
    }
};
