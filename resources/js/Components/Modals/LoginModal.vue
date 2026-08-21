<template>
    <div class="space-y-4">
        <SystemNotificationList :notifications="globalNotifications" />

        <div class="italic">
            {{ content.description }}
        </div>

        <form class="space-y-6" @submit.prevent="$emit('submit')">
            <div>
                <label class="space-y-2" for="username">
                    <span class="text-xs font-bold uppercase text-app-muted">{{
                        $t("login.form.username.label")
                    }}</span>

                    <input
                        id="username"
                        v-model="payload.username"
                        class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
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
                    <span class="text-xs font-bold uppercase text-app-muted">{{
                        $t("login.form.password.label")
                    }}</span>

                    <input
                        id="password"
                        v-model="payload.password"
                        class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
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

<script setup lang="ts">
import ModalAlert from "@/Components/Modals/ModalAlert.vue";
import SystemNotificationList from "@/Components/SystemNotificationList.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import Spinner from "@/Shared/Spinner.vue";
import { useAppStore } from "@/Stores/AppStore";
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

defineEmits<{
    (event: "submit"): void;
}>();

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const error = storeToRefs(authStore).error;
const { globalNotifications } = storeToRefs(appStore);

const usernameError = computed(() => {
    return error.value?.data?.errors?.username;
});

const passwordError = computed(() => {
    return error.value?.data?.errors?.password;
});
</script>
