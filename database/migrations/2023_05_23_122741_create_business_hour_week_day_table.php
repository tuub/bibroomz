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
        Schema::create('business_hour_week_day', function (Blueprint $table) {
            $table->uuid('business_hour_id')->index();
            $table->foreign('business_hour_id')->references('id')->on('business_hours')->onDelete('cascade');

            $table->bigInteger('week_day_id')->unsigned()->index();
            $table->foreign('week_day_id')->references('id')->on('week_days')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_hours_week_days');
    }
};
