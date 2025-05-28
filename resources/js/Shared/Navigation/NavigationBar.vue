<script setup>
import { useLogin } from "@/Composables/Login";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import InternalLink from "@/Shared/Navigation/InternalLink.vue";
import LanguageSwitch from "@/Shared/Navigation/LanguageSwitch.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { inject } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    isResponsive: {
        type: Boolean,
        default: false,
    },
    isPrivileged: {
        type: Boolean,
        default: false,
    },
    isMultiTenancy: {
        type: Boolean,
        default: false,
    },
});

const route = inject("ziggyRoute");
const { isAuthenticated, user: currentUser } = storeToRefs(useAuthStore());
const { loginUser, logoutUser } = useLogin();
</script>
<template>
    <nav id="menu" role="navigation" :aria-label="$t('accessibility.aria_label.navigation.regular')">
        <ul>
            <li :class="isResponsive ? 'block' : 'inline-block'">
                <InternalLink
                    :href="route('start')"
                    icon="pi pi-home"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    title="Start"
                >
                    {{ $t("navigation.home") }}
                </InternalLink>
            </li>
            <li :class="isResponsive ? 'block' : 'inline-block'">
                <ExternalLink
                    :href="$t('navigation.help.uri')"
                    icon="pi pi-question-circle"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    title="Help"
                >
                    {{ $t("navigation.help.label") }}
                </ExternalLink>
            </li>
            <li v-if="isPrivileged" :class="isResponsive ? 'block' : 'inline-block'">
                <InternalLink
                    :href="route('admin.dashboard')"
                    icon="pi pi-cog"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    title="Admin"
                >
                    {{ $t("navigation.admin") }}
                </InternalLink>
            </li>
            <li :class="isResponsive ? 'block' : 'inline-block'">
                <a
                    v-if="isAuthenticated"
                    id="auth"
                    href="#"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    title="Logout"
                    @click="logoutUser"
                >
                    <i class="pi pi-user"></i>
                    {{ $t("navigation.logout") }}
                    ({{ currentUser?.name }})
                </a>
                <a
                    v-else
                    id="auth"
                    href="#"
                    class="block rounded px-3 py-2 text-tub hover:bg-tub hover:text-white"
                    title="Login"
                    @click="loginUser"
                >
                    <i class="pi pi-user"></i>
                    {{ $t("navigation.login") }}
                </a>
            </li>
            <li :class="isResponsive ? 'block' : 'inline-block'">
                <div id="i18n" class="block px-3 py-2 text-tub">
                    <LanguageSwitch />
                </div>
            </li>
        </ul>
    </nav>
</template>
