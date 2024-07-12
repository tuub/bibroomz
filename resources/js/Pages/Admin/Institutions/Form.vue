<template>
    <PageHead :title="$t('admin.institutions.form.title')" page-type="admin" />
    <BodyHead :title="$t('admin.institutions.form.title')" :description="$t('admin.institutions.form.description')" />

    <form class="max-w mx-auto mt-8">
        <!-- Input: Title -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.institutions.form.fields.title"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Short title -->
        <div class="mb-6">
            <FormLabel field="short_title" field-key="admin.institutions.form.fields.short_title"></FormLabel>
            <input
                id="short_title"
                v-model="form.short_title"
                type="text"
                name="short_title"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
                required
            />
            <FormValidationError :message="form.errors.short_title"></FormValidationError>
        </div>

        <!-- Input: Slug -->
        <div class="mb-6">
            <FormLabel field="slug" field-key="admin.institutions.form.fields.slug"></FormLabel>
            <input
                id="slug"
                v-model="form.slug"
                type="text"
                name="slug"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
                required
            />
            <FormValidationError :message="form.errors.slug"></FormValidationError>
        </div>

        <!-- Input: Location -->
        <div class="mb-6">
            <FormLabel field="location" field-key="admin.institutions.form.fields.location"></FormLabel>
            <input
                id="location"
                v-model="form.location"
                type="text"
                name="location"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
                required
            />
            <FormValidationError :message="form.errors.location"></FormValidationError>
        </div>

        <!-- Checkbox: Week Days -->
        <div class="mb-6">
            <FormLabel field="week_days" field-key="admin.institutions.form.fields.week_days"></FormLabel>
            <span v-for="dayOfWeek in daysOfWeek" :key="dayOfWeek.id" class="mr-2">
                <input v-model="form.week_days" type="checkbox" name="week_days[]" :value="dayOfWeek.id" />
                {{ $t("admin.general.week_days." + dayOfWeek.key + ".short_label") }}
            </span>
            <FormValidationError :message="form.errors.week_days"></FormValidationError>
        </div>

        <!-- Input: Home URI -->
        <div class="mb-6">
            <FormLabel field="home_uri" field-key="admin.institutions.form.fields.home_uri"></FormLabel>
            <input
                id="home_uri"
                v-model="form.home_uri"
                type="text"
                name="home_uri"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.home_uri"></FormValidationError>
        </div>

        <!-- Input: Email -->
        <div class="mb-6">
            <FormLabel field="email" field-key="admin.institutions.form.fields.email"></FormLabel>
            <input
                id="email"
                v-model="form.email"
                type="text"
                name="email"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.email"></FormValidationError>
        </div>

        <!-- Input: Logo URI -->
        <div class="mb-6">
            <FormLabel field="logo_uri" field-key="admin.institutions.form.fields.logo_uri"></FormLabel>
            <input
                id="logo_uri"
                v-model="form.logo_uri"
                type="text"
                name="logo_uri"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.logo_uri"></FormValidationError>
        </div>

        <!-- Input: Teaser URI -->
        <div class="mb-6">
            <FormLabel field="teaser_uri" field-key="admin.institutions.form.fields.teaser_uri"></FormLabel>
            <input
                id="teaser_uri"
                v-model="form.teaser_uri"
                type="text"
                name="teaser_uri"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                placeholder=""
            />
            <FormValidationError :message="form.errors.teaser_uri"></FormValidationError>
        </div>

        <!-- Checkbox: Is active -->
        <div class="mb-6">
            <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="form.is_active" type="checkbox" class="peer sr-only" />
                <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-0.5 after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-red-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:ring-4 peer-focus:ring-red-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-red-800"
                ></div>
                <span class="ml-3 text-sm font-bold uppercase text-gray-900 dark:text-white">
                    {{ $t("admin.institutions.form.fields.is_active.label") }}
                </span>
            </label>
        </div>

        <FormAction :form="form" model="institution" cancel-route="admin.institution.index" />
    </form>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import BodyHead from "@/Shared/BodyHead.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import PageHead from "@/Shared/PageHead.vue";

import { useForm } from "@inertiajs/vue3";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import { onMounted } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    institution: {
        type: Object,
        default: () => null,
    },
    daysOfWeek: {
        type: Array,
        default: () => [],
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// DayJS
// ------------------------------------------------
dayjs.extend(utc);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const form = useForm({
    id: props.institution?.id ?? "",
    title: props.institution?.title ?? {},
    short_title: props.institution?.short_title ?? "",
    slug: props.institution?.slug ?? "",
    location: props.institution?.location ?? "",
    week_days: [],
    home_uri: props.institution?.home_uri ?? "",
    email: props.institution?.email ?? "",
    logo_uri: props.institution?.logo_uri ?? "",
    teaser_uri: props.institution?.teaser_uri ?? "",
    is_active: props.institution?.is_active ?? false,
});

// ------------------------------------------------
// Mount
// ------------------------------------------------
onMounted(() => {
    props.institution?.week_days.forEach((week_day) => {
        form.week_days.push(week_day.id);
    });
});
</script>
