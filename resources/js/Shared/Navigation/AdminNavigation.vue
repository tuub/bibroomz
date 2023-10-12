<template>
    <NavigationMenu :is-always-mobile="true">
        <ul>
            <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')" :is-active="isPageDashboard">
                <li>
                    {{ $t("navigation.admin.dashboard") }}
                </li>
            </NavLink>
            <NavLink
                icon="ri-calendar-event-fill"
                :href="route('admin.happening.index')"
                :is-active="isPageHappenings"
            >
                <li>
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
                v-if="hasPermission('view_resource_groups')"
                icon="ri-map-pin-fill"
                :href="route('admin.resource_group.index')"
                :is-active="isPageResourceGroups"
            >
                <li>
                    {{ $t("navigation.admin.resource_groups") }}
                </li>
            </NavLink>
            <NavLink v-if="hasPermission('view_users')" icon="ri-user-fill" :href="route('admin.user.index')" :is-active="isPageUsers">
                <li>
                    {{ $t("navigation.admin.users") }}
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
            <NavLink v-if="hasPermission('view_roles')" icon="ri-group-line" :href="route('admin.role.index')" :is-active="isPageRoles">
                <li>
                    {{ $t("navigation.admin.roles") }}
                </li>
            </NavLink>
            <NavLink
                v-if="hasPermission('view_permissions')"
                icon="ri-door-lock-fill"
                :href="route('admin.permission.index')"
                :is-active="isPagePermissions"
            >
                <li>
                    {{ $t("navigation.admin.permissions") }}
                </li>
            </NavLink>
            <NavLink
                v-if="hasPermission('view_permission_groups')"
                icon="ri-shield-keyhole-fill"
                :href="route('admin.permission_group.index')"
                :is-active="isPagePermissionGroups"
            >
                <li >
                    {{ $t("navigation.admin.permission_groups") }}
                </li>
            </NavLink>
            <NavLink v-if="hasPermission('view_statistics')" icon="ri-bar-chart-fill" :href="route('admin.statistic.index')" :is-active="isPageStats">
                <li>
                        {{ $t("navigation.admin.stats") }}
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
import NavLink from "@/Shared/NavLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

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
    const isSettingsPage = inertiaPage.props.settingable_type && inertiaPage.props.settingable_type === "resource_group";

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

const isPagePermissions = computed(() => {
    return inertiaPage.component.startsWith("Admin/Permissions");
});

const isPagePermissionGroups = computed(() => {
    return inertiaPage.component.startsWith("Admin/PermissionGroups");
});

const isPageStats = computed(() => {
    return inertiaPage.component.startsWith("Admin/Stats");
});

const getExitUri = computed(() => {
    if (institution?.slug && resourceGroup?.slug) {
        return "/" + institution?.slug + "/" + resourceGroup?.slug + "/home";
    }

    return "/";
});
</script>
