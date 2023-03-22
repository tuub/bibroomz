<template>
    <h1 class="text-xl font-bold">Login</h1>
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
            <FormValidationError :message="form.errors.username"></FormValidationError>
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
            <FormValidationError :message="form.errors.password"></FormValidationError>
        </div>
        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>
        <p>Credentials: {{ form }}</p>
    </form>

</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import FormValidationError from "../Shared/FormValidationError.vue";

defineProps({
    errors: Object
})

let form = useForm({
    username: '',
    password: '',
})

let submitForm = () => {
    console.log('AUTH')
    form.post('/login');
}
</script>
