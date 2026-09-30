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
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropForeign(['institution_id']);
            $table->uuid('institution_id')->nullable()->default(null)->change();
            $table->string('settingable_type')->after('id');
            $table->uuid('settingable_id')->after('settingable_type');
            $table->index(['settingable_id', 'settingable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // up() stopped recording who a setting belongs to in institution_id and
        // started recording it in settingable_type/settingable_id, so
        // institution_id is NULL on every row written since. Making the column
        // NOT NULL again, with a foreign key, means filling it in first.
        //
        // A setting belonging to an institution can move back: settingable_id
        // already holds that institution's id. A setting belonging to a
        // resource group cannot -- once the two columns below are gone, nothing
        // is left to say which resource group it configures -- so it is
        // deleted. settingable_type holds a class name because the application
        // registers no morph map.
        DB::table('settings')
            ->where('settingable_type', 'App\Models\Institution')
            ->update(['institution_id' => DB::raw('settingable_id')]);

        DB::table('settings')->whereNull('institution_id')->delete();

        Schema::table('settings', function (Blueprint $table): void {
            $table->dropIndex(['settingable_id', 'settingable_type']);
            $table->dropColumn(['settingable_type', 'settingable_id']);
        });

        Schema::table('settings', function (Blueprint $table): void {
            // No index is added here: up() dropped the foreign key but left
            // settings_institution_id_index in place, and the constraint below
            // reuses it.
            $table->uuid('institution_id')->nullable(false)->change();
            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
        });
    }
};
