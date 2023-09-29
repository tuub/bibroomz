<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MailTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('mail_types')->insert(['key' => 'happening_created', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'happening_created_with_verification', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'happening_updated', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'happening_deleted', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'happening_verified', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'closing_created', 'description' => '']);
        DB::table('mail_types')->insert(['key' => 'closing_updated', 'description' => '']);
    }
}
