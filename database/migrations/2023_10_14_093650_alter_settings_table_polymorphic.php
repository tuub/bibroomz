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
        Schema::table('settings', function (Blueprint $table) {
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
        Schema::table('settings', function (Blueprint $table) {
            $table->uuid('institution_id')->index();
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->dropColumn('settingable_type');
            $table->dropColumn('settingable_id');
        });
    }
};
