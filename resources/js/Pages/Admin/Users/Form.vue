<template>
    <PageHead :title="$t('admin.users.form.title')" page_type="admin" />
    <BodyHead :title="$t('admin.users.form.title')"
              :description="$t('admin.users.form.description')" />

    <form @submit.prevent="submitForm" class="max-w-md mx-auto mt-8">

        <!-- Input: Name -->
        <div class="mb-6">
            <FormLabel field="name" field-key="admin.users.form.fields.name"></FormLabel>
            <input v-model="form.name"
                   type="text"
                   name="name"
                   id="name"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   disabled
                   readonly
            >
            <FormValidationError :message="form.errors.name"></FormValidationError>
        </div>

        <!-- Input: E-Mail -->
        <div class="mb-6">
            <FormLabel field="email" field-key="admin.users.form.fields.email"></FormLabel>
            <input v-model="form.email"
                   type="text"
                   name="email"
                   id="email"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                   placeholder=""
                   disabled
                   readonly
            >
            <FormValidationError :message="form.errors.email"></FormValidationError>
        </div>

        <!-- Checkbox: Is admin -->
        <div class="mb-6">
            <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="form.is_admin"
                       type="checkbox"
                       class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-bold text-gray-900 dark:text-white uppercase">
                    {{ $t('admin.users.form.fields.is_admin.label') }}
                </span>
            </label>
        </div>

        <div class="mb-6">
            <button type="submit" class="bg-blue-400 text-white rounded py-2 px-4 hover:bg-blue-500" :disabled="isProcessing">
                {{ $t('admin.resources.form.actions.submit') }}
            </button>
        </div>

    </form>
</template>
<script setup>
import {ref} from "vue";
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
let isProcessing = ref(false);
let $page = usePage()
let form = useForm({
    id: $page.props.id ?? '',
    name: $page.props.name ?? '',
    email: $page.props.email ?? '',
    is_admin: $page.props.is_admin ?? '',
    banned_at: $page.props.banned_at ?? '',
});

// ------------------------------------------------
// Methods
// ------------------------------------------------
let submitForm = () => {
    isProcessing.value = true;
    if (form.id) {
        form.post('/admin/user/update');
    }
    isProcessing.value = false;
}
</script>
