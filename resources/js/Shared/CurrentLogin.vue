<template>
    <div>
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
import { useAuthStore } from "@/Stores/AuthStore";
import { storeToRefs } from "pinia";
import { initFlowbite } from "flowbite";
import { onMounted } from "vue";
import { useLoginModal } from "@/Composables/ModalActions";
import useModal from "@/Stores/Modal";

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
    padding-right: 12px;
    float: left;
}
</style>
