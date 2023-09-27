<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PermissionSeeder extends Seeder
{
    private Collection $verbs;
    private Collection $groups;

    public function __construct()
    {
        $this->verbs = collect([
            'view' => [
                'en' => 'view',
                'de' => 'anzeigen',
            ],
            'create' => [
                'en' => 'create',
                'de' => 'erstellen',
            ],
            'edit' => [
                'en' => 'edit',
                'de' => 'bearbeiten',
            ],
            'delete' => [
                'en' => 'delete',
                'de' => 'löschen',
            ],
        ]);

        $this->groups = collect([
            'institutions' => [
                'en' => 'Institutions',
                'de' => 'Einrichtungen',
            ],
            'resources' => [
                'en' => 'Rooms',
                'de' => 'Räume',
            ],
            'closings' => [
                'en' => 'Closings',
                'de' => 'Schließungen',
            ],
            'happenings' => [
                'en' => 'Bookings',
                'de' => 'Buchungen',
            ],
            'users' => [
                'en' => 'Users',
                'de' => 'Benutzer',
            ],
            'roles' => [
                'en' => 'Roles',
                'de' => 'Rollen',
            ],
            'institution' => [
                'en' => 'Institution',
                'de' => 'Einrichtung',
            ],
            'admin_users' => [
                'en' => 'Administrators',
                'de' => 'Administratoren',
            ],
            'mails' => [
                'en' => 'Mails',
                'de' => 'Mails',
            ],
            'settings' => [
                'en' => 'Settings',
                'de' => 'Einstellungen',
            ],
        ]);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->groups as $key => $name) {
            $this->createPermissionGroup($key, $name);
        }

        $this->createPermissions([
            'view',
            'create',
            'edit',
            'delete',
        ], [
            'institutions',
            'resources',
            'closings',
            'happenings',
            'users',
            'roles',
            'mails',
        ]);

        $this->createPermissions([
            'view',
            'edit',
            'delete',
        ], [
            'institution',
        ]);

        $this->createPermissions([
            'view',
            'edit',
            'delete',
        ], [
            'admin_users',
        ]);

        $this->createPermissions([
            'view',
            'edit',
        ], [
            'settings',
        ]);

        Permission::create([
            'key' => 'unlimited_quotas',
            'name' => [
                'en' => 'Unlimited quotas',
                'de' => 'Unbegrenzte Kontingente',
            ],
        ]);

        Permission::create([
            'key' => 'no_verifier',
            'name' => [
                'en' => 'No verification necessary',
                'de' => 'Keine Bestätigung notwendig',
            ],
        ]);
    }

    private function createPermissions(array $verbs, array $groups)
    {
        foreach ($verbs as $verbKey) {
            foreach ($groups as $groupKey) {
                $verbName = $this->verbs->get($verbKey);
                $groupName = $this->groups->get($groupKey);

                $permission = Permission::create([
                    'key' => $verbKey . '_' . $groupKey,
                    'name' => [
                        'en' => ucfirst($verbName['en']) . ' ' . lcfirst($groupName['en']),
                        'de' => ucfirst($groupName['de']) . ' ' . lcfirst($verbName['de']),
                    ],
                ]);

                $group = PermissionGroup::where('key', '=', $groupKey)->first();
                $permission->group()->associate($group)->save();
            }
        }
    }

    private function createPermissionGroup(string $key, array $name)
    {
        PermissionGroup::create([
            'key' => $key,
            'name' => $name,
        ]);
    }
}
