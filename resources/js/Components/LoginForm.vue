<template>
    <h1 class="text-xl font-bold mt-4">
        {{ $t("login.header") }}
    </h1>

    <form class="max-w-md mx-auto mt-4 login-wapper" @submit.prevent="submitForm">
        <div class="mb-6">
            <label
                class="block mb-2 uppercase font-bold text-xs text-gray-700"
                for="username"
            >
                {{ $t("login.form.username.label") }}
            </label>

            <input
                id="username"
                v-model="form.username"
                class="border border-gray-400 p-2 w-full"
                type="text"
                name="username"
                :placeholder="$t('login.form.username.placeholder')"
            />

            <FormValidationError
                v-for="(message, index) in errors?.errors?.username"
                :key="index"
                :message="message"
            ></FormValidationError>
        </div>
        <div class="mb-6">
            <label
                class="block mb-2 uppercase font-bold text-xs text-gray-700"
                for="password"
            >
                {{ $t("login.form.password.label") }}
            </label>

            <input
                id="password"
                v-model="form.password"
                class="border border-gray-400 p-2 w-full"
                type="password"
                name="password"
                :placeholder="$t('login.form.password.placeholder')"
            />

            <FormValidationError
                v-for="(message, index) in errors?.errors?.password"
                :key="index"
                :message="message"
            ></FormValidationError>

            <FormValidationError
                v-if="!errors.errors"
                :message="errors?.message"
            ></FormValidationError>
        </div>
        <div class="mb-6">
            <button
                type="submit"
                class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500"
                :disabled="form.processing"
            >
                {{ $t("login.form.submit.label") }}
            </button>
        </div>
    </form>
</template>

<script setup>
import FormValidationError from "../Shared/Form/FormValidationError.vue";
import { useAuthStore } from "../Stores/AuthStore";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { trans } from "laravel-vue-i18n";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const toast = useToast();

const errors = ref([]);

const form = useForm({
    username: "",
    password: "",
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = async () => {
    try {
        return await authStore.login(form.username, form.password);
    } catch (error) {
        toast.error(trans("toast.login.error"));

        errors.value = error.response.data;
    }
};
</script>
<style>
.login-wapper{
    float: left;
    width: 100%;
}
</style>
