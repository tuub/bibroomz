<template>
    <NavigationMenu v-if="isPrivileged">
        <ul>
            <li>
                <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')">
                    {{ $t("navigation.regular.admin") }}
                </NavLink>
            </li>
        </ul>
    </NavigationMenu>
</template>

<script setup>
import NavLink from "@/Shared/NavLink.vue";
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
