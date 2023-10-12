<template>
    <NavigationMenu v-if="isPrivileged">
        <ul>
            <NavLink icon="ri-tools-fill" :href="route('admin.dashboard')">
                <li>
                    {{ $t("navigation.regular.admin") }}
                </li>
            </NavLink>
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
