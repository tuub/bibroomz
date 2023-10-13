<template>
    <NavigationMenu>
        <ul>
            <NavLink v-if="isMultiTenancy" icon="ri-dashboard-fill" :href="route('start')" :is-active="isPageStart">
                <li >
                    {{ $t("navigation.regular.institutions") }}
                </li>
            </NavLink>
            <NavLink
                icon="ri-home-fill"
                :href="
                        route('home', {
                            institution_slug: institution.slug,
                            resource_group_slug: resourceGroup.slug,
                        })
                    "
                :is-active="isPageHome"
                v-if="institution?.slug && resourceGroup?.slug"
            >
                <li>
                    {{ $t("navigation.regular.home", { short_title: institution?.short_title }) }}
                </li>
            </NavLink>
            <NavLink v-if="isPrivileged" icon="ri-tools-fill" :href="route('admin.dashboard')">
                <li>
                    {{ $t("navigation.regular.admin") }}
                </li>
            </NavLink>
            <ExternalLink icon="ri-question-fill" :href="$t('navigation.regular.help.uri')">
                <li>
                    {{ $t("navigation.regular.help.text") }}
                </li>
            </ExternalLink>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";

import { usePage } from "@inertiajs/vue3";
import { storeToRefs } from "pinia";
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

const { isMultiTenancy } = storeToRefs(appStore);
const { isPrivileged } = storeToRefs(authStore);

const institution = appStore.institution;
const resourceGroup = appStore.resourceGroup;

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPageStart = computed(() => {
    return inertiaPage.component === "Start";
});

const isPageHome = computed(() => {
    return inertiaPage.component === "Home";
});
</script>
