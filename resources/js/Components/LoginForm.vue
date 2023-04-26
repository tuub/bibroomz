<template>
    <h1 class="text-xl font-bold">Login</h1>
    {{ errors }}
    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="username">
                Username
            </label>
            <input v-model="form.username"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="username"
                   id="username"
            >
            <!--<FormValidationError :messages="form.errors.username"></FormValidationError>-->
        </div>
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="password">
                Password
            </label>
            <input v-model="form.password"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="password"
                   id="password"
            >
            <!--<FormValidationError :messages="form.errors.password"></FormValidationError>-->
        </div>
        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>
    </form>

</template>

<script setup>
import FormValidationError from "../Shared/FormValidationError.vue";
import { storeToRefs } from 'pinia';
import { useAuthStore } from '../Stores/AuthStore';
import {reactive, ref} from "vue";

// Auth store
const authStore = useAuthStore();
const { user: authUser } = storeToRefs(authStore);

defineProps({
    errors: Object
})

const errors = ref([])
const form = reactive({
    username: '',
    password: '',
})

let submitForm = () => {
    return authStore.login(form.username, form.password)
        .catch((err) => {
            console.log(err.response.data.errors)
            errors.value = Object.values(err.response.data.errors).flat()
    });
}
</script>
