<template>
    <Head title="Create Resource" />
    <h1 class="text-3xl">Create New Resource</h1>

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="institution_id">
                Institution
            </label>
            <input v-model="form.institution_id"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="institution_id"
                   id="institution_id"
            >
            <div v-if="errors.institution_id" v-text="errors.institution_id" class="text-red-500 text-xs mt-1"></div>
        </div>
        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="title">
                Title
            </label>
            <input v-model="form.title"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="title"
                   id="title"
            >
            <div v-if="errors.title" v-text="errors.title" class="text-red-500 text-xs mt-1"></div>
        </div>

        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="location">
                Location
            </label>
            <input v-model="form.location"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="location"
                   id="location"
            >
            <div v-if="errors.location" v-text="errors.location" class="text-red-500 text-xs mt-1"></div>
        </div>

        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="description">
                Description
            </label>
            <input v-model="form.description"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="description"
                   id="description"
            >
            <div v-if="errors.description" v-text="errors.description" class="text-red-500 text-xs mt-1"></div>
        </div>

        <div class="mb-6">
            <label class="block mb-2 uppercase font-bold text-xs text-gray-700" for="capacity">
                Capacity
            </label>
            <input v-model="form.capacity"
                   class="border border-gray-400 p-2 w-full"
                   type="text"
                   name="capacity"
                   id="capacity"
            >
            <div v-if="errors.capacity" v-text="errors.capacity" class="text-red-500 text-xs mt-1"></div>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="processing">
                Submit
            </button>
        </div>

    </form>
</template>
<script setup>
import { reactive, ref } from "vue";
import { router } from "@inertiajs/vue3";
//import { useForm} from "@inertiajs/vue3";

defineProps({
    errors: Object,
});

let processing = ref(false);

let form = reactive({
    institution_id: '1',
    title: '',
    location: '',
    description: '',
    capacity: 0,
});

let submitForm = () => {
    processing.value = true;
    router.post('/admin/resources', form, {
        onStart: () => { processing.value = true },
        onFinish: () => { processing.value = false },
    });
}

</script>
