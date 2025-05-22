<template>
    <div class="space-y-4">
        <div class="italic">
            {{ content.description }}
        </div>

        <form class="space-y-6" @submit.prevent="$emit('submit')">
            <div>
                <label class="space-y-2" for="username">
                    <span class="text-xs font-bold uppercase text-gray-700">{{ $t("login.form.username.label") }}</span>

                    <input
                        id="username"
                        v-model="payload.username"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                        type="text"
                        name="username"
                        autocomplete="username"
                        autofocus
                        :placeholder="$t('login.form.username.placeholder')"
                    />
                </label>

                <FormValidationError
                    v-for="(message, index) in usernameError"
                    :key="index"
                    :message="message"
                ></FormValidationError>
            </div>

            <div>
                <label class="space-y-2" for="password">
                    <span class="text-xs font-bold uppercase text-gray-700">{{ $t("login.form.password.label") }}</span>

                    <input
                        id="password"
                        v-model="payload.password"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        :placeholder="$t('login.form.password.placeholder')"
                    />
                </label>

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
// Models, Props, Emits
// ------------------------------------------------
const payload = defineModel("payload", { type: Object, default: () => ({ username: "", password: "" }) });

defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits(["submit"]);

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
</script>
