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
            'admin_users' => [
                'en' => 'Administrators',
                'de' => 'Administratoren',
            ],
            'closings' => [
                'en' => 'Closings',
                'de' => 'Schließungen',
            ],
            'happenings' => [
                'en' => 'Bookings',
                'de' => 'Buchungen',
            ],
            'institution' => [
                'en' => 'Institution',
                'de' => 'Einrichtung',
            ],
            'institutions' => [
                'en' => 'Institutions',
                'de' => 'Einrichtungen',
            ],
            'mails' => [
                'en' => 'Mails',
                'de' => 'Mails',
            ],
            'permission_groups' => [
                'en' => 'Permission Groups',
                'de' => 'Berechtigungsgruppen',
            ],
            'permissions' => [
                'en' => 'Permissions',
                'de' => 'Berechtigungen',
            ],
            'resource_groups' => [
                'en' => 'Resource Groups',
                'de' => 'Ressourcengruppen',
            ],
            'resources' => [
                'en' => 'Resources',
                'de' => 'Ressourcen',
            ],
            'roles' => [
                'en' => 'Roles',
                'de' => 'Rollen',
            ],
            'settings' => [
                'en' => 'Settings',
                'de' => 'Einstellungen',
            ],
            'users' => [
                'en' => 'Users',
                'de' => 'Benutzer',
            ],
            'user_groups' => [
                'en' => 'User Groups',
                'de' => 'Benutzergruppen',
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
            'closings',
            'happenings',
            'institutions',
            'mails',
            'resource_groups',
            'resources',
            'roles',
            'users',
            'user_groups',
        ]);

        $this->createPermissions([
            'view',
            'edit',
            'delete',
        ], [
            'admin_users',
            'institution',
        ]);

        $this->createPermissions([
            'view',
            'edit',
        ], [
            'permission_groups',
            'permissions',
            'settings',
        ]);

        $this->createPermission(
            'unlimited_quotas',
            [
                'en' => 'Unlimited quotas',
                'de' => 'Unbegrenzte Kontingente',
            ],
        );

        $this->createPermission(
            'no_verifier',
            [
                'en' => 'No verification necessary',
                'de' => 'Keine Bestätigung notwendig',
            ],
        );
    }

    private function createPermission(string $key, array $name): ?Permission
    {
        if (Permission::where('key', '=', $key)->exists()) {
            return null;
        }

        return Permission::create([
            'key' => $key,
            'name' => $name,
        ]);
    }

    private function createPermissions(array $verbs, array $groups)
    {
        foreach ($verbs as $verbKey) {
            foreach ($groups as $groupKey) {
                $verbName = $this->verbs->get($verbKey);
                $groupName = $this->groups->get($groupKey);

                $permission = $this->createPermission(
                    $verbKey . '_' . $groupKey,
                    [
                        'en' => ucfirst($verbName['en']) . ' ' . lcfirst($groupName['en']),
                        'de' => ucfirst($groupName['de']) . ' ' . lcfirst($verbName['de']),
                    ],
                );

                $group = PermissionGroup::where('key', '=', $groupKey)->first();
                $permission?->group()->associate($group)->save();
            }
        }
    }

    private function createPermissionGroup(string $key, array $name): ?PermissionGroup
    {
        if (PermissionGroup::where('key', '=', $key)->exists()) {
            return null;
        }

        return PermissionGroup::create([
            'key' => $key,
            'name' => $name,
        ]);
    }
}
