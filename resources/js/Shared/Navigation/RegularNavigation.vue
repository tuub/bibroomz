<template>
    <nav class="w-full">
        <div class="">
            <Brand></Brand>
        </div>
        <div id="nav-footer-wrapper" class="block w-full h-8 pt-3.5">

                   
                    <div class="LangSwitch-CurrentLogin-wapper w-40p">
                        <CurrentLogin class="" v-show="isAuthenticated"></CurrentLogin>
                        <LangSwitch class=""></LangSwitch>
                        
                    </div>

                    <button data-collapse-toggle="mobile-menu-2"
                            type="button"
                            class=""
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
                
                    <div id="mobile-menu-2"
                        class="w-60p mt-3px float-right">
                        <ul class="">
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
                                <ExternalLink icon="ri-question-fill" :href="$t('navigation.regular.help.uri')">
                                    {{ $t('navigation.regular.help.text') }}
                                </ExternalLink>
                            </li>
                            <li>
                                <NavLink icon="ri-government-fill"
                                        :href="route('privacy_statement')"
                                        :is_active="isPrivacyStatement">
                                    {{ $t('navigation.regular.privacy_statement') }}
                                </NavLink>
                            </li>
                            <li>
                                <NavLink icon="ri-copyright-fill"
                                        :href="route('site_credits')"
                                        :is_active="isSiteCredits">
                                    {{ $t('navigation.regular.site_credits') }}
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
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";

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

const isSiteCredits = computed(() => {
    return inertiaPage.component === 'SiteCredits'
})
</script>

<style>
.w-20p{
    width: 20%;
}
.w-40p{
    width: 40%;
}

.w-60p{
    width: 60%;
}
.w-80p{
    width: 80%;
}
.mt-3px{
    margin-top: -3px;
}
#mobile-menu-2  > ul > li{
    float: right;
    margin-left: 20px;
}
#nav-footer-wrapper > button{
    float: right;
    display: none;
}
.LangSwitch-CurrentLogin-wapper{
    width: 20%;
    display: contents;
}
nav{
    background-color: aqua;
    padding: 30px;
    height: 7em;
}

</style>