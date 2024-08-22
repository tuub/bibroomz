<template>
    <FormLayout :title="$t('admin.institutions.form.title')" :description="$t('admin.institutions.form.description')">
        <!-- Input: Title -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.institutions.form.fields.title"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Short title -->
        <FormInput
            v-model="form.short_title"
            field="short_title"
            field-key="admin.institutions.form.fields.short_title"
            :error="form.errors.short_title"
            :is-required="true"
        />

        <!-- Input: Slug -->
        <FormInput
            v-model="form.slug"
            field="slug"
            field-key="admin.institutions.form.fields.slug"
            :error="form.errors.slug"
            :is-required="true"
        />

        <!-- Input: Location -->
        <FormInput
            v-model="form.location"
            field="location"
            field-key="admin.institutions.form.fields.location"
            :error="form.errors.location"
            :is-required="true"
        />

        <!-- Checkbox: Week Days -->
        <div>
            <FormLabel field="week_days" field-key="admin.institutions.form.fields.week_days"></FormLabel>
            <span v-for="dayOfWeek in daysOfWeek" :key="dayOfWeek.id" class="mr-2">
                <input v-model="form.week_days" type="checkbox" name="week_days[]" :value="dayOfWeek.id" />
                {{ $t("admin.general.week_days." + dayOfWeek.key + ".short_label") }}
            </span>
            <FormValidationError :message="form.errors.week_days"></FormValidationError>
        </div>

        <!-- Input: Home URI -->
        <FormInput
            v-model="form.home_uri"
            field="home_uri"
            field-key="admin.institutions.form.fields.home_uri"
            :error="form.errors.home_uri"
        />

        <!-- Input: Email -->
        <FormInput
            v-model="form.email"
            field="email"
            field-key="admin.institutions.form.fields.email"
            :error="form.errors.email"
        />

        <!-- Input: Logo URI -->
        <FormInput
            v-model="form.logo_uri"
            field="logo_uri"
            field-key="admin.institutions.form.fields.logo_uri"
            :error="form.errors.logo_uri"
        />

        <!-- Input: Teaser URI -->
        <FormInput
            v-model="form.teaser_uri"
            field="teaser_uri"
            field-key="admin.institutions.form.fields.teaser_uri"
            :error="form.errors.teaser_uri"
        />

        <!-- Checkbox: Is active -->
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_active" input-id="is_active" />
            <FormLabel field="is_active" field-key="admin.institutions.form.fields.is_active" class="inline-block" />
            <FormValidationError :message="form.errors.is_active"></FormValidationError>
        </div>

        <FormAction :form="form" model="institution" cancel-route="admin.institution.index" />
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";

import { useForm } from "@inertiajs/vue3";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import ToggleSwitch from "primevue/toggleswitch";
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
