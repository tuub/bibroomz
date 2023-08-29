<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $verbs = [
            'view',
            'create',
            'edit',
            'delete',
        ];

        $models = [
            'institutions',
            'resources',
            'closings',
            'happenings',
            'users',
            'roles',
        ];

        foreach ($verbs as $verb) {
            foreach ($models as $model) {
                Permission::create(['name' => $verb . ' ' . $model]);
            }
        }

        Permission::create(['name' => 'view institution']);
        Permission::create(['name' => 'edit institution']);
        Permission::create(['name' => 'delete institution']);

        Permission::create(['name' => 'view admin users']);
        Permission::create(['name' => 'edit admin users']);
        Permission::create(['name' => 'delete admin users']);

        Permission::create(['name' => 'unlimited quotas']);
        Permission::create(['name' => 'no verifier']);
    }
}
