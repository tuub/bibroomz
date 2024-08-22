<template>
    <FormLayout :title="$t('admin.happenings.form.title')" :description="$t('admin.happenings.form.description')">
        <!-- Input: Start Date & Start Time -->
        <div class="grid gap-6 md:grid-cols-2">
            <FormInput
                v-model="form.start_date"
                field="start_date"
                field-key="admin.happenings.form.fields.start_date"
                :error="form.errors.start_date"
            />
            <FormInput
                v-model="form.start_time"
                field="start_time"
                field-key="admin.happenings.form.fields.start_time"
                :error="form.errors.start_time"
            />
        </div>

        <!-- Input: End Date & End Time -->
        <div class="grid gap-6 md:grid-cols-2">
            <FormInput
                v-model="form.end_date"
                field="end_date"
                field-key="admin.happenings.form.fields.end_date"
                :error="form.errors.end_date"
            />
            <FormInput
                v-model="form.end_time"
                field="end_time"
                field-key="admin.happenings.form.fields.end_time"
                :error="form.errors.end_time"
            />
        </div>

        <!-- Select: Resource -->
        <FormSelect
            v-model="form.resource_id"
            field="resource_id"
            field-key="admin.happenings.form.fields.resource"
            :options="
                resources.map((resource) => ({
                    key: resource.id,
                    value: resource.id.toString(),
                    label: translate(resource.title),
                }))
            "
            :placeholder="{ value: '-1' }"
            :error="form.errors.resource_id"
        />

        <!-- Select: User 1 -->
        <FormSelect
            v-model="form.user_id_01"
            field="user_01"
            field-key="admin.happenings.form.fields.user_01"
            :options="
                users.map((user) => ({
                    key: user.id,
                    value: user.id.toString(),
                    label: user.name,
                }))
            "
            :error="form.errors.user_id_01"
        />

        <!-- Select: User 2 / Verifier -->
        <div>
            <div>
                <FormLabel
                    v-if="form.is_verified"
                    field="user_02"
                    field-key="admin.happenings.form.fields.user_02"
                ></FormLabel>
                <FormLabel v-else field="verifier" field-key="admin.happenings.form.fields.verifier"></FormLabel>
            </div>
            <div v-if="isHappeningToVerify" class="grid gap-6 md:grid-cols-2">
                <select
                    id="user_id_02"
                    v-model="form.user_id_02"
                    name="user_id_02"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                    @change="updateVerifier($event)"
                >
                    <option value="">Choose</option>
                    <option
                        v-for="user in users.filter((user) => user.id !== form.user_id_01)"
                        :key="user.id"
                        :value="user.id"
                    >
                        {{ user.name }}
                    </option>
                </select>
                <input
                    v-if="!form.is_verified"
                    id="verifier"
                    v-model="form.verifier"
                    type="text"
                    name="verifier"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-red-500 dark:focus:ring-red-500"
                    :placeholder="$t('admin.happenings.form.fields.verifier.placeholder')"
                    @change="updateUser2($event)"
                />
            </div>
            <div v-else class="italic">
                {{ $t("admin.happenings.form.general.not_required") }}
            </div>
            <div>
                <FormValidationError :message="form.errors.user_id_02"></FormValidationError>
                <FormValidationError :message="form.errors.verifier"></FormValidationError>
            </div>
        </div>

        <!-- Input: Label -->
        <TranslatableFormInput
            v-model="form.label"
            field="label"
            field-key="admin.happenings.form.fields.label"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Checkbox: Is verified -->
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_verified" input-id="is_verified" />
            <FormLabel field="is_verified" field-key="admin.happenings.form.fields.is_verified" class="inline-block" />
            <FormValidationError :message="form.errors.is_verified"></FormValidationError>
        </div>

        <FormAction :form="form" model="happening" cancel-route="admin.happening.index" />
    </FormLayout>
</template>

<script setup>
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormSelect from "@/Shared/Form/FormSelect.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";

import { useForm } from "@inertiajs/vue3";
import ToggleSwitch from "primevue/toggleswitch";
import { computed, watch } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    happening: {
        type: Object,
        default: () => ({}),
    },
    resources: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
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
    id: props.happening.id ?? "",
    resource_id: props.happening.resource_id ?? "-1",
    start_date: props.happening.start_date ?? "",
    start_time: props.happening.start_time ?? "",
    end_date: props.happening.end_date ?? "",
    end_time: props.happening.end_time ?? "",
    user_id_01: props.happening.user_id_01 ?? "",
    user_id_02: props.happening.user_id_02 ?? "",
    verifier: props.happening.verifier ?? "",
    is_verified: props.happening.is_verified ?? false,
    label: props.happening.label ?? {},
});

const savedVerifier = form["verifier"];

// ------------------------------------------------
// Methods
// ------------------------------------------------
const updateVerifier = (event) => {
    const verifier = props.users.find((x) => x.id === event.target.value);

    if (!verifier) {
        form.verifier = savedVerifier;
    } else {
        form.verifier = verifier.name;
    }
};

const updateUser2 = (event) => {
    const user2 = props.users.filter((x) => x.id !== form.user_id_01).find((x) => x.name === event.target.value);

    if (user2) {
        form.user_id_02 = user2.id;
    } else {
        form.user_id_02 = "";
    }
};

// ------------------------------------------------
// Computed
// ------------------------------------------------
const currentUser = computed(() => {
    return props.users?.find((x) => x.id === form.user_id_01);
});

const currentResource = computed(() => {
    return props.resources?.find((x) => x.id === form.resource_id);
});

const isHappeningToVerify = computed(() => {
    const currentInstitutionId = currentResource.value?.institution_id;

    if (!currentUser.value || !currentResource.value) {
        return true;
    }

    if (currentUser.value.permissions[currentInstitutionId]?.includes("no_verifier")) {
        return false;
    }

    if (!currentResource.value.is_verification_required) {
        return false;
    }

    return true;
});

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(form, () => {
    form.end_date = form.start_date;
});
</script>
