<template>
    <FormLayout
        :title="$t('admin.user_groups.import.title')"
        :description="$t('admin.user_groups.import.description', { title })"
    >
        <div class="flex justify-stretch gap-2">
            <div class="w-full space-y-2">
                <FormLabel field="valid-from" field-key="admin.user_groups.import.fields.valid_from" />
                <DatePicker v-model="validFrom" show-button-bar show-icon class="w-full" input-id="valid-from" />
                <FormValidationError :message="form.errors.valid_from" />
            </div>

            <div class="w-full space-y-2">
                <FormLabel field="valid-until" field-key="admin.user_groups.import.fields.valid_until" />
                <DatePicker v-model="validUntil" show-button-bar show-icon class="w-full" input-id="valid-until" />
                <FormValidationError :message="form.errors.valid_until" />
            </div>
        </div>

        <div class="space-y-2">
            <FormLabel field="users" field-key="admin.user_groups.import.fields.users" />
            <Textarea id="users" v-model="users" rows="5" class="w-full" auto-resize />
            <FormValidationError :message="form.errors.users" />
        </div>

        <FormAction :form="form" model="user_group.users" action="import" cancel-route="admin.user_group.index" />
    </FormLayout>
</template>

<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const translate = useAppStore().translate;

function toDateString(date) {
    return date ? date.toDateString() : date;
}

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    // eslint-disable-next-line vue/prop-name-casing
    user_group: {
        type: Object,
        required: true,
    },
    languages: {
        type: Array,
        required: true,
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const users = ref("");

const validFrom = ref();
const validUntil = ref();

const form = useForm({
    id: props.user_group.id,

    users: computed(() => [
        ...new Set(
            users.value
                .split("\n")
                .filter((name) => name)
                .map((name) => ({
                    name: name.trim(),
                })),
        ),
    ]),

    valid_from: computed(() => toDateString(validFrom.value)),
    valid_until: computed(() => toDateString(validUntil.value)),
});

const title = computed(() => translate(props.user_group.title));
</script>
