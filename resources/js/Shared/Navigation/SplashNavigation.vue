<template>
    <NavigationMenu>
        <ul>
            <NavLink v-if="isPrivileged" icon="ri-tools-fill" :href="route('admin.dashboard')">
                <li>
                    {{ $t("navigation.regular.admin") }}
                </li>
            </NavLink>
            <ExternalLink icon="ri-question-fill" :href="$t('navigation.regular.help.uri')">
                <li>{{ $t("navigation.regular.help.text") }}</li>
            </ExternalLink>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
import ExternalLink from "@/Shared/Navigation/ExternalLink.vue";
import NavigationMenu from "@/Shared/Navigation/NavigationMenu.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isPrivileged } = storeToRefs(authStore);
</script>
