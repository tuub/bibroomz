<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Every foreign key column needs an index, and MariaDB made one for
        // each of these three columns when the table was created:
        // happenings_resource_id_foreign and the two user_id ones. It then
        // deleted all three when up() ran, because an index that starts with
        // the same column serves the constraint just as well and makes the
        // single-column one redundant.
        //
        // Each constraint therefore relies on a composite index above now, and
        // MariaDB refuses to drop an index a constraint relies on: "needed in a
        // foreign key constraint". Recreating the single-column indexes first
        // gives the constraints something else to rely on, which frees the
        // composite ones to be dropped. SQLite creates no index for a foreign
        // key, so it has none to recreate.
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('happenings', function (Blueprint $table): void {
                $table->index('resource_id', 'happenings_resource_id_foreign');
                $table->index('user_id_01', 'happenings_user_id_01_foreign');
                $table->index('user_id_02', 'happenings_user_id_02_foreign');
            });
        }

        Schema::table('happenings', function (Blueprint $table): void {
            $table->dropIndex('happenings_resource_id_start_end_index');
            $table->dropIndex('happenings_user_id_01_start_end_index');
            $table->dropIndex('happenings_user_id_02_start_end_index');
        });
    }
};
