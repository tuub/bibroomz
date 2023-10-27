<template>
    <div>
        <div class="text-3xl font-bold inline-flex">
            {{ content.title }}
        </div>
        <div class="italic mt-4 mb-4">
            {{ content.description }}
        </div>

        <form @submit.prevent="$emit('submit')">
            <div class="mb-6">
                <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="username">
                    {{ $t("login.form.username.label") }}
                </label>

                <input
                    id="username"
                    v-model="username"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    type="text"
                    name="username"
                    autocomplete="username"
                    :placeholder="$t('login.form.username.placeholder')"
                />

                <FormValidationError
                    v-for="(message, index) in usernameError"
                    :key="index"
                    :message="message"
                ></FormValidationError>
            </div>

            <div class="mb-6">
                <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="password">
                    {{ $t("login.form.password.label") }}
                </label>

                <input
                    id="password"
                    v-model="password"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-red-500 dark:focus:border-red-500"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    :placeholder="$t('login.form.password.placeholder')"
                />

                <FormValidationError
                    v-for="(message, index) in passwordError"
                    :key="index"
                    :message="message"
                ></FormValidationError>
            </div>

            <button type="submit" hidden />
        </form>

        <Spinner v-if="authStore.isProcessingLogin" size="small" />

        <ModalAlert
            v-if="!error?.data?.errors && error?.data?.message"
            :error="error.data.message"
            @close="error = null"
        />
    </div>
</template>

<script setup>
import ModalAlert from "@/Components/Modals/ModalAlert.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import Spinner from "@/Shared/Spinner.vue";
import { useAuthStore } from "@/Stores/AuthStore";

import { storeToRefs } from "pinia";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
    payload: {
        type: Object,
        default: () => ({
            username: "",
            password: "",
        }),
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits(["update:payload", "submit"]);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const error = storeToRefs(authStore).error;

const usernameError = computed(() => {
    return error.value?.data?.errors?.username;
});

const passwordError = computed(() => {
    return error.value?.data?.errors?.password;
});

const username = computed({
    get() {
        return props.payload.username;
    },
    set(value) {
        emit("update:payload", { username: value, password });
    },
});

const password = computed({
    get() {
        return props.payload.password;
    },
    set(value) {
        emit("update:payload", { username, password: value });
    },
});
</script>
<style>
.login-wapper {
    float: left;
    width: 100%;
}
</style>
