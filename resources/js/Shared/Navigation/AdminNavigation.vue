<template>
    <nav class="">
        <div class="">
            <Brand></Brand>

            <div class="">
                <CurrentLogin v-show="isAuthenticated" class="CurrentLogin"></CurrentLogin>
                <LangSwitch></LangSwitch>
            </div>

            <button
                data-collapse-toggle="mobile-menu-2"
                type="button"
                class=""
                aria-controls="mobile-menu-2"
                aria-expanded="false"
            >
                <span class="sr-only">Open main menu</span>
                <svg
                    class="w-6 h-6"
                    aria-hidden="true"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        fill-rule="evenodd"
                        d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                        clip-rule="evenodd"
                    ></path>
                </svg>
            </button>

            <div
                id="mobile-menu-2"
                class="items-center justify-between hidden float-right w-full md:flex md:w-auto md:order-1"
            >
                <ul
                    class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700"
                >
                    <li>
                        <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')" :is_active="isPageDashboard">
                            {{ $t("navigation.admin.dashboard") }}
                        </NavLink>
                    </li>
                    <li>
                        <NavLink
                            icon="ri-calendar-event-fill"
                            :href="route('admin.happening.index')"
                            :is_active="isPageHappenings"
                        >
                            {{ $t("navigation.admin.happenings") }}
                        </NavLink>
                    </li>
                    <li v-if="canViewInstitutions()">
                        <NavLink
                            icon="ri-home-smile-fill"
                            :href="route('admin.institution.index')"
                            :is_active="isPageInstitutions"
                        >
                            {{ $t("navigation.admin.institutions") }}
                        </NavLink>
                    </li>
                    <li v-if="hasPermission('view resources')">
                        <NavLink
                            icon="ri-map-pin-fill"
                            :href="route('admin.resource.index')"
                            :is_active="isPageResources"
                        >
                            {{ $t("navigation.admin.resources") }}
                        </NavLink>
                    </li>
                    <li v-if="hasPermission('view users')">
                        <NavLink icon="ri-user-fill" :href="route('admin.user.index')" :is_active="isPageUsers">
                            {{ $t("navigation.admin.users") }}
                        </NavLink>
                    </li>
                    <li v-if="hasPermission('view roles')">
                        <NavLink icon="ri-group-line"
                                 :href="route('admin.role.index')"
                                 :is_active="isPageRoles">
                            {{ $t('navigation.admin.roles') }}
                        </NavLink>
                    </li>
                    <li v-if="hasPermission('view statistics')">
                        <NavLink
                            icon="ri-bar-chart-fill"
                            :href="route('admin.statistic.index')"
                            :is_active="isPageStats"
                        >
                            {{ $t("navigation.admin.stats") }}
                        </NavLink>
                    </li>
                    <li>
                        <NavLink icon="ri-shut-down-line" :href="getExitUri">
                            {{ $t("navigation.admin.exit") }}
                        </NavLink>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import NavLink from "../NavLink.vue";
import Brand from "@/Shared/Brand.vue";
import CurrentLogin from "@/Shared/CurrentLogin.vue";
import LangSwitch from "@/Shared/LangSwitch.vue";
import { usePage } from "@inertiajs/vue3";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let inertiaPage = usePage();
let institutionSlug = appStore.institutionSlug;

const { isAuthenticated } = storeToRefs(authStore);
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
    let isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "institution";
    return (
        inertiaPage.component.startsWith("Admin/Institutions") ||
        inertiaPage.component.startsWith("Admin/Settings") ||
        isClosingsPage
    );
});

const isPageResources = computed(() => {
    let isClosingsPage = inertiaPage.props.closable_type && inertiaPage.props.closable_type === "resource";
    return inertiaPage.component.startsWith("Admin/Resources") || isClosingsPage;
});

const isPageUsers = computed(() => {
    return inertiaPage.component.startsWith("Admin/Users");
});

const isPageRoles = computed(() => {
    return inertiaPage.component.startsWith('Admin/Roles')
})

const isPageStats = computed(() => {
    return inertiaPage.component.startsWith("Admin/Stats");
});

const getExitUri = computed(() => {
    if (institutionSlug) {
        return "/" + institutionSlug;
    }
    return "/";
});
</script>
