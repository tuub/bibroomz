<template>
    <div class="inline-flex">
        <span class="current-user">
            {{ currentUser?.name }}
        </span>
        <div>
            <button v-if="!isAuthenticated" class="login-logout-button" @click="loginUser">
                {{ $t("navigation.current_login.login") }}
            </button>
            <button v-else class="login-logout-button" @click="logoutUser">
                {{ $t("navigation.current_login.logout") }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { useLoginModal } from "@/Composables/ModalActions";
import { useAuthStore } from "@/Stores/AuthStore";
import useModal from "@/Stores/Modal";

import { initFlowbite } from "flowbite";
import { storeToRefs } from "pinia";
import { onMounted } from "vue";

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const modal = useModal();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { isAuthenticated, user: currentUser } = storeToRefs(authStore);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const loginUser = async () => {
    const editModal = useLoginModal();
    modal.open(editModal.view, editModal.content, editModal.payload, editModal.actions);
};

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
.current-user {
    position: absolute;
    top: 23px;
    right: 30px;
    font-size: 10px;
}

.login-logout-button{
    position: absolute;
    right: 30px;
    top: 37px;
}
</style>
