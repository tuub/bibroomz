<?php

namespace App\Enums;

enum PermissionKey: string
{
    case ViewAdminUsers = 'view_admin_users';
    case EditAdminUsers = 'edit_admin_users';
    case DeleteAdminUsers = 'delete_admin_users';

    case ViewClosings = 'view_closings';
    case CreateClosings = 'create_closings';
    case EditClosings = 'edit_closings';
    case DeleteClosings = 'delete_closings';

    case ViewHappenings = 'view_happenings';
    case CreateHappenings = 'create_happenings';
    case EditHappenings = 'edit_happenings';
    case DeleteHappenings = 'delete_happenings';

    case ViewInstitution = 'view_institution';
    case EditInstitution = 'edit_institution';
    case DeleteInstitution = 'delete_institution';

    case ViewInstitutions = 'view_institutions';
    case CreateInstitutions = 'create_institutions';
    case EditInstitutions = 'edit_institutions';
    case DeleteInstitutions = 'delete_institutions';

    case ViewMails = 'view_mails';
    case CreateMails = 'create_mails';
    case EditMails = 'edit_mails';
    case DeleteMails = 'delete_mails';

    case ViewPermissionGroups = 'view_permission_groups';
    case EditPermissionGroups = 'edit_permission_groups';

    case ViewPermissions = 'view_permissions';
    case EditPermissions = 'edit_permissions';

    case ViewResourceGroups = 'view_resource_groups';
    case CreateResourceGroups = 'create_resource_groups';
    case EditResourceGroups = 'edit_resource_groups';
    case DeleteResourceGroups = 'delete_resource_groups';

    case ViewResources = 'view_resources';
    case CreateResources = 'create_resources';
    case EditResources = 'edit_resources';
    case DeleteResources = 'delete_resources';

    case ViewRoles = 'view_roles';
    case CreateRoles = 'create_roles';
    case EditRoles = 'edit_roles';
    case DeleteRoles = 'delete_roles';

    case ViewSettings = 'view_settings';
    case EditSettings = 'edit_settings';

    case ViewUserGroups = 'view_user_groups';
    case CreateUserGroups = 'create_user_groups';
    case EditUserGroups = 'edit_user_groups';
    case DeleteUserGroups = 'delete_user_groups';

    case ViewUsers = 'view_users';
    case CreateUsers = 'create_users';
    case EditUsers = 'edit_users';
    case DeleteUsers = 'delete_users';

    case UnlimitedQuotas = 'unlimited_quotas';
    case NoVerifier = 'no_verifier';

    public function verbKey(): ?string
    {
        foreach (['view', 'create', 'edit', 'delete'] as $verb) {
            if (str_starts_with($this->value, $verb.'_')) {
                return $verb;
            }
        }

        return null;
    }

    public function groupKey(): ?string
    {
        $verb = $this->verbKey();

        return $verb === null ? null : substr($this->value, strlen($verb) + 1);
    }
}
