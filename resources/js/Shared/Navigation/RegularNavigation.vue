<template>
    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between mx-auto p-4">
            <Brand></Brand>

            <div class="flex items-center md:order-2">
                <CurrentLogin v-show="isAuthenticated"></CurrentLogin>
                <LangSwitch></LangSwitch>
                <button data-collapse-toggle="mobile-menu-2"
                        type="button"
                        class="inline-flex items-center p-2 ml-1 text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                        aria-controls="mobile-menu-2"
                        aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-6 h-6"
                         aria-hidden="true"
                         fill="currentColor"
                         viewBox="0 0 20 20"
                         xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                              d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                              clip-rule="evenodd">
                        </path>
                    </svg>
                </button>
            </div>
            <div id="mobile-menu-2"
                 class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white dark:bg-gray-800 md:dark:bg-gray-900 dark:border-gray-700">
                    <li v-if="isMultiTenancy">
                        <NavLink icon="ri-dashboard-fill"
                                 :href="route('start')"
                                 :is_active="isPageStart">
                            {{ $t('navigation.regular.institutions') }}
                        </NavLink>
                    </li>
                    <li v-if="institutionSlug">
                        <NavLink icon="ri-home-fill"
                                 :href="route('home', {slug: institutionSlug})"
                                 :is_active="isPageHome">
                            {{ $t('navigation.regular.home', {short_title: institutionShortTitle}) }}
                        </NavLink>
                    </li>
                    <li v-if="isAdmin || isInstitutionAdmin">
                        <NavLink icon="ri-tools-fill"
                                 :href="route('admin.dashboard')">
                            {{ $t('navigation.regular.admin') }}
                        </NavLink>
                    </li>
                    <li>
                        <NavLink icon="ri-government-fill"
                                 :href="route('privacy-statement')"
                                 :is_active="isPrivacyStatement">
                            {{ $t('navigation.regular.privacy_statement') }}
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
import NavLink from "../NavLink.vue";
import { storeToRefs } from "pinia";
import { computed } from "vue";
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
let inertiaPage = usePage()
let { institutionShortTitle, institutionSlug, isMultiTenancy } = storeToRefs(appStore);
let { isAuthenticated, isAdmin, isInstitutionAdmin, user } = storeToRefs(authStore);

// ------------------------------------------------
// Computed
// ------------------------------------------------
const isPageStart = computed(() => {
    return inertiaPage.component === 'Start'
})

const isPageHome = computed(() => {
    return inertiaPage.component === 'Home'
})

const isPrivacyStatement = computed(() => {
    return inertiaPage.component === 'PrivacyStatement'
})
</script>
