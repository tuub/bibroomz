<template>
    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between mx-auto p-4">
            <Brand></Brand>

            <div class="flex items-center md:order-2">
                <CurrentLogin v-show="isAuthenticated"></CurrentLogin>
                <LangSwitch></LangSwitch>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import { storeToRefs } from "pinia";
import Brand from "@/Shared/Brand.vue";
import CurrentLogin from "@/Shared/CurrentLogin.vue";
import LangSwitch from "@/Shared/LangSwitch.vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
let { isAuthenticated } = storeToRefs(authStore);
</script>
