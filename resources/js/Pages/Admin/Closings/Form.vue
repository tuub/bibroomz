<template>
    <FormLayout :title="title" :description="$t('admin.closings.form.description')">
        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 md:grid-cols-2">
            <FormInput
                v-model="form.start_date"
                field="start_date"
                field-key="admin.closings.form.fields.start_date"
                :error="form.errors.start_date"
            />
            <FormInput
                v-model="form.start_time"
                field="start_time"
                field-key="admin.closings.form.fields.start_time"
                :error="form.errors.start_time"
            />
        </div>

        <!-- Input: End Date & End Time -->
        <div class="grid gap-6 md:grid-cols-2">
            <FormInput
                v-model="form.end_date"
                field="end_date"
                field-key="admin.closings.form.fields.end_date"
                :error="form.errors.end_date"
            />
            <FormInput
                v-model="form.end_time"
                field="end_time"
                field-key="admin.closings.form.fields.end_time"
                :error="form.errors.end_time"
            />
        </div>

        <!-- Textarea: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.closings.form.fields.description"
            :placeholder="$t('admin.closings.form.fields.description.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="4"
        ></TranslatableFormInput>

        <FormAction
            :form="form"
            model="closing"
            cancel-route="admin.closing.index"
            :cancel-route-params="{ closable_id: closable.id, closable_type: closable_type }"
        />
    </FormLayout>
</template>
<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    closing: {
        type: Object,
        default: () => ({}),
    },
    closable: {
        type: Object,
        default: () => ({}),
    },
    // eslint-disable-next-line vue/prop-name-casing
    closable_type: {
        type: String,
        default: "",
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;

const form = useForm({
    id: props.closing.id ?? "",
    start_date: props.closing.start_date ?? "",
    start_time: props.closing.start_time ?? "",
    end_date: props.closing.end_date ?? "",
    end_time: props.closing.end_time ?? "",
    description: props.closing.description ?? {},
    closable_id: props.closable.id,
    closable_type: props.closable_type,
});

const title = computed(() =>
    trans("admin.closings.form.title", {
        type: props.closable_type,
        title: translate(props.closable.title),
    }),
);
</script>
