<template>
    <div>
        <span class="current-User">
            {{ currentUser?.name }}
        </span>
        <ul class="py-2 logoutUser" aria-labelledby="user-menu-button">
            <li class="logoutUser">
                <a href="#" class="" @click="logoutUser">
                    {{ $t("navigation.current_login.logout") }}
                </a>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { useAuthStore } from "@/Stores/AuthStore";
import { storeToRefs } from "pinia";
import { initFlowbite } from "flowbite";
import { onMounted } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { user: currentUser } = storeToRefs(authStore);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const logoutUser = async () => {
    try {
        return await authStore.logout();
    } catch (error) {
        return console.log(error);
    }
};

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    initFlowbite();
});
</script>

<style>
.current-User {
    padding-right: 12px;
    float: left;
}
.logoutUser {
    display: contents;
}
</style>
