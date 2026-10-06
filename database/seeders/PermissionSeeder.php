<?php

namespace Database\Seeders;

use App\Enums\PermissionKey;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PermissionSeeder extends Seeder
{
    /** @var Collection<string, array{en: string, de: string}> */
    private readonly Collection $verbs;

    /** @var Collection<string, array{en: string, de: string}> */
    private readonly Collection $groups;

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
     */
    public function run(): void
    {
        foreach ($this->groups as $key => $name) {
            $this->createPermissionGroup($key, $name);
        }

        foreach (PermissionKey::cases() as $permission) {
            $this->createRegisteredPermission($permission);
        }
    }

    /** @param array<string, string> $name */
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

    private function createRegisteredPermission(PermissionKey $permissionKey): void
    {
        $verbKey = $permissionKey->verbKey();
        $groupKey = $permissionKey->groupKey();

        if ($verbKey === null || $groupKey === null) {
            $this->createPermission($permissionKey->value, match ($permissionKey) {
                PermissionKey::UnlimitedQuotas => [
                    'en' => 'Unlimited quotas',
                    'de' => 'Unbegrenzte Kontingente',
                ],
                PermissionKey::NoVerifier => [
                    'en' => 'No verification necessary',
                    'de' => 'Keine Bestätigung notwendig',
                ],
                default => throw new \LogicException('Missing permission translation.'),
            });

            return;
        }

        /** @var array{en: string, de: string} $verbName */
        $verbName = $this->verbs->get($verbKey) ?? throw new \LogicException('Unknown permission verb.');
        /** @var array{en: string, de: string} $groupName */
        $groupName = $this->groups->get($groupKey) ?? throw new \LogicException('Unknown permission group.');

        $permission = $this->createPermission(
            $permissionKey->value,
            [
                'en' => ucfirst($verbName['en']).' '.lcfirst($groupName['en']),
                'de' => ucfirst($groupName['de']).' '.lcfirst($verbName['de']),
            ],
        );

        $group = PermissionGroup::where('key', '=', $groupKey)->first();
        $permission?->group()->associate($group)->save();
    }

    /** @param array<string, string> $name */
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
