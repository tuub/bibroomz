<template>
    <NavigationMenu :is-always-mobile="true">
        <ul>
            <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')" :is-active="isPageDashboard">
                <li>
                    {{ $t("navigation.admin.dashboard") }}
                </li>
            </NavLink>
            <NavLink icon="ri-calendar-event-fill" :href="route('admin.happening.index')" :is-active="isPageHappenings">
                <li>
                    {{ $t("navigation.admin.happenings") }}
                </li>
            </NavLink>
            <NavLink
                v-if="canViewInstitutions()"
                icon="ri-home-smile-fill"
                :href="route('admin.institution.index')"
                :is-active="isPageInstitutions"
            >
                <li>
                    {{ $t("navigation.admin.institutions") }}
                </li>
            </NavLink>
            <NavLink
                v-if="resourceGroupIndexHref"
                icon="ri-map-pin-fill"
                :href="resourceGroupIndexHref"
                :is-active="isPageResourceGroups"
            >
                <li>
                    {{ $t("navigation.admin.resource_groups") }}
                </li>
            </NavLink>
            <NavLink
                v-if="hasPermission('view_users')"
                icon="ri-user-fill"
                :href="route('admin.user.index')"
                :is-active="isPageUsers"
            >
                <li>
                    {{ $t("navigation.admin.users") }}
                </li>
            </NavLink>
            <NavLink
                v-if="hasPermission('view_roles')"
                icon="ri-group-line"
                :href="route('admin.role.index')"
                :is-active="isPageRoles"
            >
                <li>
                    {{ $t("navigation.admin.roles") }}
                </li>
            </NavLink>
            <NavLink
                v-if="hasPermission('view_user_groups')"
                icon="ri-shield-keyhole-fill"
                :href="route('admin.user_group.index')"
                :is-active="isPageUserGroups"
            >
                <li>
                    {{ $t("navigation.admin.user_groups") }}
                </li>
            </NavLink>
            <NavLink icon="ri-shut-down-line" :href="getExitUri">
                <li>
                    {{ $t("navigation.admin.exit") }}
                </li>
            </NavLink>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/Navigation/InternalLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { usePage } from "@inertiajs/vue3";
import { computed, inject } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();
const route = inject("ziggyRoute");

// ------------------------------------------------
// Variables
// ------------------------------------------------
const inertiaPage = usePage();
const institution = appStore.institution;
const resourceGroup = appStore.resourceGroup;

const { hasPermission, canViewInstitutions } = authStore;

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPageDashboard = computed(() => {
    return inertiaPage.component.startsWith("Admin/Dashboard");
});

const isPageHappenings = computed(() => {
    return inertiaPage.component.startsWith("Admin/Happenings");
});

const isPageInstitutions = computed(() => {
    const isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "institution";
    const isSettingsPage = inertiaPage.props.settingable_type && inertiaPage.props.settingable_type === "institution";

    return (
        inertiaPage.component.startsWith("Admin/Institutions") ||
        inertiaPage.component.startsWith("Admin/Mails") ||
        isClosingsPage ||
        isSettingsPage
    );
});

const isPageResourceGroups = computed(() => {
    const isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "resource";
    const isSettingsPage =
        inertiaPage.props.settingable_type && inertiaPage.props.settingable_type === "resource_group";

    return (
        inertiaPage.component.startsWith("Admin/ResourceGroups") ||
        inertiaPage.component.startsWith("Admin/Resources") ||
        isClosingsPage ||
        isSettingsPage
    );
});

const isPageUsers = computed(() => {
    return inertiaPage.component.startsWith("Admin/Users");
});

const isPageRoles = computed(() => {
    return inertiaPage.component.startsWith("Admin/Roles");
});

const isPageUserGroups = computed(() => {
    return inertiaPage.component.startsWith("Admin/UserGroups");
});

const findInstitutionIdForPermission = (permission) => {
    for (const [institutionId, permissions] of Object.entries(authStore.permissions ?? {})) {
        if (Array.isArray(permissions) && permissions.includes(permission)) {
            return institutionId;
        }
    }

    return null;
};

const currentInstitutionId = computed(() => {
    if (typeof inertiaPage.props.institution?.id === "string") {
        return inertiaPage.props.institution.id;
    }

    if (typeof inertiaPage.props.institution_id === "string") {
        return inertiaPage.props.institution_id;
    }

    if (typeof inertiaPage.props.resourceGroup?.institution_id === "string") {
        return inertiaPage.props.resourceGroup.institution_id;
    }

    if (typeof inertiaPage.props.resource_group?.institution_id === "string") {
        return inertiaPage.props.resource_group.institution_id;
    }

    if (inertiaPage.props.closable_type === "institution" && typeof inertiaPage.props.closable?.id === "string") {
        return inertiaPage.props.closable.id;
    }

    if (inertiaPage.props.settingable_type === "institution" && typeof inertiaPage.props.settingable?.id === "string") {
        return inertiaPage.props.settingable.id;
    }

    if (
        inertiaPage.props.settingable_type === "resource_group" &&
        typeof inertiaPage.props.settingable?.institution_id === "string"
    ) {
        return inertiaPage.props.settingable.institution_id;
    }

    return null;
});

const resourceGroupInstitutionId = computed(() => {
    return (
        currentInstitutionId.value ??
        findInstitutionIdForPermission("view_resource_groups") ??
        findInstitutionIdForPermission("create_resource_groups") ??
        findInstitutionIdForPermission("edit_resource_groups") ??
        findInstitutionIdForPermission("delete_resource_groups")
    );
});

const resourceGroupIndexHref = computed(() => {
    if (resourceGroupInstitutionId.value) {
        return route("admin.resource_group.index", { institution_id: resourceGroupInstitutionId.value });
    }

    if (canViewInstitutions()) {
        return route("admin.institution.index");
    }

    return null;
});

const getExitUri = computed(() => {
    if (institution?.slug && resourceGroup?.slug) {
        return "/" + institution?.slug + "/" + resourceGroup?.slug + "/home";
    }

    return "/";
});
</script>
