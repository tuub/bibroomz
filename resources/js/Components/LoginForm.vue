<template>
    <h1 class="text-xl font-bold">
        {{ $t('login.header') }}
    </h1>

    <div v-if="errors.length > 0">{{ errors }}</div>

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="username">
                {{ $t('login.form.username.label') }}
            </label>
            <input v-model="form.username"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="username"
                   id="username"
                   :placeholder="$t('login.form.username.placeholder')"
            >
            <FormValidationError :messages="form.errors.username"></FormValidationError>
        </div>
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="password">
                {{ $t('login.form.password.label') }}
            </label>
            <input v-model="form.password"
                   class="border border-gray-400 p-2 w-full"
                   type="password"
                   name="password"
                   id="password"
                   :placeholder="$t('login.form.password.placeholder')"
            >
            <FormValidationError :messages="form.errors.password"></FormValidationError>
        </div>
        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                {{ $t('login.form.submit.label') }}
            </button>
        </div>
    </form>

</template>

<script setup>
import FormValidationError from "../Shared/Form/FormValidationError.vue";
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../Stores/AuthStore';
import {reactive, ref} from "vue";
import {useForm} from "@inertiajs/vue3";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    errors: Object
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const authStore = useAuthStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const { user: authUser } = storeToRefs(authStore);
const errors = ref([])
const form = useForm({
    username: '',
    password: '',
})

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = () => {
    // FIXME: not catching! err = undefined
    return authStore.login(form.username, form.password)
    /*
    .catch((err) => {
        console.log(err)
        console.log(err.response.data.errors)
        errors.value = Object.values(err.response.data.errors).flat()
    });
    */
}
</script>
