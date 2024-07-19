<?php

namespace Database\Seeders;

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
        $keys = [
            'happening_created',
            'happening_created_with_verification',
            'happening_updated',
            'happening_deleted',
            'happening_verified',
            'closing_created',
            'closing_updated',
            'closing_deleted',
        ];

        foreach ($keys as $key) {
            if (DB::table('mail_types')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('mail_types')->insert(['key' => $key, 'description' => '']);
        }
    }
}
