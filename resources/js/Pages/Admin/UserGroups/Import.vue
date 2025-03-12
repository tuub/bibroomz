<template>
    <FormLayout
        :title="$t('admin.user_groups.import.title')"
        :description="$t('admin.user_groups.import.description', { title })"
    >
        <div class="flex justify-stretch gap-2">
            <div class="w-full space-y-2">
                <FormLabel field="valid-from-date" field-key="admin.user_groups.import.fields.valid_from" />

                <div>
                    <DatePicker
                        v-model="validFromDate"
                        show-button-bar
                        show-icon
                        :disabled="!!validFromText"
                        input-id="valid-from-date"
                        class="w-full"
                    />
                    <FormValidationError :message="form.errors.valid_from_date" />
                </div>

                <div>
                    <InputText
                        id="valid-from-text"
                        v-model="validFromText"
                        :disabled="!!validFromDate"
                        class="w-full"
                    />
                    <FormValidationError :message="form.errors.valid_from_text" />
                </div>
            </div>

            <div class="w-full space-y-2">
                <FormLabel field="valid-until-date" field-key="admin.user_groups.import.fields.valid_until" />

                <div>
                    <DatePicker
                        v-model="validUntilDate"
                        show-button-bar
                        show-icon
                        :disabled="!!validUntilNumber"
                        input-id="valid-until-date"
                        class="w-full"
                    />
                    <FormValidationError :message="form.errors.valid_until_date" />
                </div>

                <div>
                    <InputGroup>
                        <InputNumber
                            v-model="validUntilNumber"
                            input-id="valid-until-unit"
                            class="w-1/2"
                            :disabled="!!validUntilDate"
                        />
                        <Select
                            v-model="validUntilUnit"
                            :options="units"
                            option-label="label"
                            option-value="value"
                            :disabled="!!validUntilDate"
                            class="w-1/2"
                        />
                    </InputGroup>
                    <FormValidationError :message="form.errors.valid_until_text" />
                </div>
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
import { trans } from "laravel-vue-i18n";
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

const validFromDate = ref();
const validUntilDate = ref();

const validFromText = ref();

const units = computed(() => [
    { value: "days", label: trans("admin.user_groups.import.fields.units.days") },
    { value: "weeks", label: trans("admin.user_groups.import.fields.units.weeks") },
    { value: "months", label: trans("admin.user_groups.import.fields.units.months") },
    { value: "years", label: trans("admin.user_groups.import.fields.units.years") },
]);

const validUntilNumber = ref();
const validUntilUnit = ref(units.value[0].value);

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

    valid_from_date: computed(() => toDateString(validFromDate.value)),
    valid_until_date: computed(() => toDateString(validUntilDate.value)),

    valid_from_text: computed(() => validFromText.value),
    valid_until_text: computed(() =>
        validUntilNumber.value ? [validUntilNumber.value, validUntilUnit.value].join(" ") : null,
    ),
});

const title = computed(() => translate(props.user_group.title));
</script>
