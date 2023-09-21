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
        Schema::dropIfExists('mail_contents');
        Schema::create('mail_contents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            $table->uuid('institution_id');
            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->bigInteger('mail_type_id')->unsigned()->index();
            $table->foreign('mail_type_id')->references('id')->on('mail_types')->onDelete('cascade');
            $table->text('subject');
            $table->text('title')->nullable();
            $table->text('salutation')->nullable();
            $table->text('intro')->nullable();
            $table->text('outro')->nullable();
            $table->string('action_uri', 255)->nullable();
            $table->string('action_uri_label', 255)->nullable();
            $table->text('farewell')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_contents');
    }
};
