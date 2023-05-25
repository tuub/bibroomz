<template>
    <Head title="Happening Form" />
    <h1 class="text-3xl">Happening Form</h1>

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Input: Start / End -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <label for="start" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Start
                </label>
                <input v-model="form.start"
                       type="text"
                       id="start"
                       name="start"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.start"></FormValidationError>
            </div>
            <div>
                <label for="end" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    End
                </label>
                <input v-model="form.end"
                       type="text"
                       id="end"
                       name="end"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.end"></FormValidationError>
            </div>
        </div>

        <!-- Select: Resource -->
        <div class="mb-6">
            <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                Resource
            </label>
            <select v-model="form.resource_id"
                    id="resource_id"
                    name="resource_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">Choose</option>
                <option v-for="resource in resources" :value="resource.id" :key="resource.id">
                    {{ resource.title }}
                </option>
            </select>
            <FormValidationError :message="form.errors.resource_id"></FormValidationError>
        </div>

        <!-- Select: User 1 -->
        <div class="mb-6">
            <label for="user_id_01" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                User 1
            </label>
            <select v-model="form.user_id_01"
                    id="user_id_02"
                    name="user_id_02"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="">Choose</option>
                <option v-for="user in users" :value="user.id" :key="user.id">
                    {{ user.name }}
                </option>
            </select>
            <FormValidationError :message="form.errors.user_id_01"></FormValidationError>
        </div>

        <!-- Select: User 2 / Confirmer -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <label for="user_id_02" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    User 2
                </label>
                <select v-model="form.user_id_02"
                        id="user_id_02"
                        name="user_id_02"
                        @change="updateConfirmer($event)"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="">Choose</option>
                    <option v-for="user in users" :value="user.id" :key="user.id">
                        {{ user.name }}
                    </option>
                </select>
                <FormValidationError :message="form.errors.user_id_02"></FormValidationError>
            </div>
            <div>
                <label for="confirmer" class="block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Confirmer
                </label>
                <input v-model="form.confirmer"
                       type="text"
                       id="confirmer"
                       name="confirmer"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       placeholder=""
                >
                <FormValidationError :message="form.errors.confirmer"></FormValidationError>
            </div>
        </div>

        <!-- Checkbox: Is confirmed -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_confirmed"
                       type="checkbox"
                       class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    Is confirmed
                </span>
            </label>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                Submit
            </button>
        </div>
    </form>
</template>
<script setup>
import {onMounted, ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import FormValidationError from "../../../Shared/FormValidationError.vue";

defineProps({
    errors: Object,
});

let processing = ref(false);
let $page = usePage()

let form = useForm({
    id: $page.props.id ?? '',
    resource_id: $page.props.resource_id ?? '',
    start: $page.props.start ?? '',
    end: $page.props.end ?? '',
    user_id_01: $page.props.user_id_01 ?? '',
    user_id_02: $page.props.user_id_02 ?? '',
    confirmer: $page.props.confirmer ?? '',
    is_confirmed: $page.props.is_confirmed === 1,
});

let resources = ref([]);
let users = ref([]);

// Save original confirmer for later rollback
const savedConfirmer = form['confirmer']

const updateConfirmer = (event) => {
    let index = users.value.findIndex(x => x.id === event.target.value)
    if (index === -1) {
        form['confirmer'] = savedConfirmer
        form['is_confirmed'] = false
    } else {
        form['confirmer'] = users.value[index].name
        form['is_confirmed'] = true
    }
}

const getResources = () => {
    axios.get('/admin/form/resources').then((response) => {
        resources.value = response.data
    })
}

const getUsers = () => {
    axios.get('/admin/form/users').then((response) => {
        users.value = response.data
    })
}

let submitForm = () => {
    processing.value = true;
    if (form.id) {
        form.post('/admin/happening/update');
    } else {
        form.post('/admin/happening/store');
    }
}

onMounted( () => {
    getResources();
    getUsers();
});

</script>
