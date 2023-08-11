<template>
    <PageHead :title="$t('admin.happenings.form.title')" page_type="admin" />
    <BodyHead :title="$t('admin.happenings.form.title')"
              :description="$t('admin.happenings.form.description')" />

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="start_date" field-key="admin.happenings.form.fields.start_date"></FormLabel>
                <input v-model="form.start_date"
                       type="text"
                       id="start_date"
                       name="start_date"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.happenings.form.fields.start_date.placeholder')"
                >
                <FormValidationError :message="form.errors.start_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="start_time" field-key="admin.happenings.form.fields.start_time"></FormLabel>
                <input v-model="form.start_time"
                       type="text"
                       id="start_time"
                       name="start_time"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.happenings.form.fields.start_time.placeholder')"
                >
                <FormValidationError :message="form.errors.start_time"></FormValidationError>
            </div>
        </div>

        <!-- Input: End Date & End Time -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="end_date" field-key="admin.happenings.form.fields.end_date"></FormLabel>
                <input v-model="form.end_date"
                       type="text"
                       id="end_date"
                       name="end_date"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.happenings.form.fields.end_date.placeholder')">
                <FormValidationError :message="form.errors.end_date"></FormValidationError>
            </div>
            <div>
                <FormLabel field="end_time" field-key="admin.happenings.form.fields.end_time"></FormLabel>
                <input v-model="form.end_time"
                       type="text"
                       id="end_time"
                       name="end_time"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.happenings.form.fields.end_time.placeholder')">
                <FormValidationError :message="form.errors.end_time"></FormValidationError>
            </div>
        </div>

        <!-- Select: Resource -->
        <div class="mb-6">
            <FormLabel field="resource_id" field-key="admin.happenings.form.fields.resource"></FormLabel>
            <select v-model="form.resource_id"
                    id="resource_id"
                    name="resource_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option value="-1">Choose</option>
                <option v-for="resource in resources"
                        :value="resource.id"
                        :key="resource.id"
                        @click="toggleResourceVerificationField(resource)">
                    {{ resource.title }}
                </option>
            </select>
            <FormValidationError :message="form.errors.resource_id"></FormValidationError>
        </div>

        <!-- Select: User 1 -->
        <div class="mb-6">
            <FormLabel field="user_01" field-key="admin.happenings.form.fields.user_01"></FormLabel>
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

        <!-- Select: User 2 / Verifier -->
        <div class="grid gap-6 mb-6 md:grid-cols-2">
            <div>
                <FormLabel field="user_02" field-key="admin.happenings.form.fields.user_02"></FormLabel>
                <select v-if="isHappeningToVerify"
                        v-model="form.user_id_02"
                        id="user_id_02"
                        name="user_id_02"
                        @change="updateVerifier($event)"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option value="">Choose</option>
                    <option v-for="user in users" :value="user.id" :key="user.id">
                        {{ user.name }}
                    </option>
                </select>
                <div v-else class="italic">
                    {{ $t('admin.happenings.form.general.not_required') }}
                </div>
                <FormValidationError :message="form.errors.user_id_02"></FormValidationError>
            </div>
            <div>
                <FormLabel field="verifier" field-key="admin.happenings.form.fields.verifier"></FormLabel>
                <input v-if="isHappeningToVerify"
                       v-model="form.verifier"
                       type="text"
                       id="verifier"
                       name="verifier"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                       :placeholder="$t('admin.happenings.form.fields.verifier.placeholder')">
                <span v-else class="italic">
                    {{ $t('admin.happenings.form.general.not_required') }}
                </span>
                <FormValidationError :message="form.errors.verifier"></FormValidationError>
            </div>
        </div>

        <!-- Checkbox: Is verified -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_verified"
                       type="checkbox"
                       class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t('admin.happenings.form.fields.is_verified.label') }}
                </span>
            </label>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="form.processing">
                {{ $t('admin.happenings.form.actions.submit') }}
            </button>
        </div>
    </form>
</template>
<script setup>
import {onMounted, ref, watch} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import PageHead from "@/Shared/PageHead.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    errors: Object,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
let processing = ref(false);
let $page = usePage()
let resources = ref([]);
let users = ref([]);
let form = useForm({
    id: $page.props.id ?? '',
    resource_id: $page.props.resource_id ?? '-1',
    start_date: $page.props.start_date ?? '',
    start_time: $page.props.start_time ?? '',
    end_date: $page.props.end_date ?? '',
    end_time: $page.props.end_time ?? '',
    user_id_01: $page.props.user_id_01 ?? '',
    user_id_02: $page.props.user_id_02 ?? '',
    verifier: $page.props.verifier ?? '',
    is_verified: $page.props.is_verified ?? false,
    resource: $page.props.resource ?? {},
});

console.log($page.props.is_verified)

// Save original verifier for later rollback
const savedVerifier = form['verifier']

let isHappeningToVerify = ref(true);

// ------------------------------------------------
// Methods
// ------------------------------------------------
const syncDateFields = (e) => {
    console.log(e)
}

const toggleResourceVerificationField = (resource) => {{}
    if (resource && resource.is_verification_required !== undefined) {
        isHappeningToVerify.value = resource.is_verification_required
    }
}

const updateVerifier = (event) => {
    let index = users.value.findIndex(x => x.id === event.target.value)
    if (index === -1) {
        form['verifier'] = savedVerifier
        form['is_verified'] = false
    } else {
        form['verifier'] = users.value[index].name
        form['is_verified'] = true
    }
}

const getResources = () => {
    // FIXME: institutions
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

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(form, () => {
    form.end_date = form.start_date
})

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted( () => {
    getResources();
    getUsers();
    toggleResourceVerificationField(form.resource);
});
</script>
