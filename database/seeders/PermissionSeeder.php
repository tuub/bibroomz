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

        foreach ($models as $model) {
            $this->createPermissions($verbs, $model);
        }

        $this->createPermissions(['view', 'edit', 'delete'], 'institution');
        $this->createPermissions(['view', 'edit', 'delete'], 'admin users');

        Permission::create([
            'key' => 'unlimited_quotas',
            'name' => [
                'en' => 'Unlimited quotas',
            ],
        ]);

        Permission::create([
            'key' => 'no_verifier',
            'name' => [
                'en' => 'No verifier',
            ],
        ]);
    }

    private function createPermissions(array $verbs, string $model)
    {
        foreach ($verbs as $verb) {
            $this->createPermission($verb, $model);
        }
    }

    private function createPermission(String $verb, String $model)
    {
        Permission::create([
            'key' => implode('_', [$verb, str_replace(' ', '_', $model)]),
            'name' => [
                'en' => implode(' ', [ucfirst($verb), $model]),
            ],
        ]);
    }
}
