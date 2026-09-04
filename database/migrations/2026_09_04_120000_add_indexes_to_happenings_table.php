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
        Schema::table('happenings', function (Blueprint $table): void {
            $table->index(['resource_id', 'start', 'end'], 'happenings_resource_id_start_end_index');
            $table->index(['user_id_01', 'start', 'end'], 'happenings_user_id_01_start_end_index');
            $table->index(['user_id_02', 'start', 'end'], 'happenings_user_id_02_start_end_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('happenings', function (Blueprint $table): void {
            $table->dropIndex('happenings_resource_id_start_end_index');
            $table->dropIndex('happenings_user_id_01_start_end_index');
            $table->dropIndex('happenings_user_id_02_start_end_index');
        });
    }
};
