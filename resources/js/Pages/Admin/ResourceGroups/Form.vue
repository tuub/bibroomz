<template>
    <FormLayout
        :title="$t('admin.resource_groups.form.title')"
        :description="$t('admin.resource_groups.form.description')"
    >
        <!-- Select: Institution -->
        <div>
            <FormLabel field="institution_id" field-key="admin.resource_groups.form.fields.institution"></FormLabel>
            <select
                id="institution_id"
                v-model="form.institution_id"
                name="institution_id"
                class="block w-full rounded-lg border border-app-border bg-app-field p-2.5 text-sm text-app-text placeholder-app-subtle focus:border-tub focus:ring-tub dark:border-app-border dark:bg-app-field dark:text-app-text dark:focus:border-tub dark:focus:ring-tub"
                required
            >
                <option v-for="i in institutions" :key="i.id" :value="i.id">
                    {{ translate(i.title) }}
                </option>
            </select>
            <FormValidationError
                v-if="form.errors.institution_id"
                :message="form.errors.institution_id"
            ></FormValidationError>
        </div>

        <!-- Input: Title -->
        <TranslatableFormInput
            v-model="form.title"
            field="title"
            field-key="admin.resource_groups.form.fields.title"
            :placeholder="$t('admin.resource_groups.form.fields.title.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Slug -->
        <FormInput
            v-model="form.slug"
            field="slug"
            field-key="admin.resource_groups.form.fields.slug"
            :error="form.errors.slug"
            :is-required="true"
        />

        <!-- Input: Term singular -->
        <TranslatableFormInput
            v-model="form.term_singular"
            field="term_singular"
            field-key="admin.resource_groups.form.fields.term_singular"
            :placeholder="$t('admin.resource_groups.form.fields.term_singular.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Input: Term plural -->
        <TranslatableFormInput
            v-model="form.term_plural"
            field="term_plural"
            field-key="admin.resource_groups.form.fields.term_plural"
            :placeholder="$t('admin.resource_groups.form.fields.term_plural.placeholder')"
            :languages="languages"
            :errors="form.errors"
        ></TranslatableFormInput>

        <!-- Textarea: Description -->
        <TranslatableFormInput
            v-model="form.description"
            field="description"
            field-key="admin.resource_groups.form.fields.description"
            :placeholder="$t('admin.resource_groups.form.fields.description.placeholder')"
            :languages="languages"
            :errors="form.errors"
            type="textarea"
            rows="4"
        ></TranslatableFormInput>

        <!-- Input: Help URI -->
        <FormInput
            v-model="form.help_uri"
            field="help_uri"
            field-key="admin.resource_groups.form.fields.help_uri"
            :error="form.errors.help_uri"
        />

        <!-- Input: Order -->
        <FormInput
            v-model="form.order"
            field="order"
            field-key="admin.resource_groups.form.fields.order"
            :error="form.errors.order"
        />

        <!-- Checkbox: Is active -->
        <div class="space-x-2">
            <ToggleSwitch v-model="form.is_active" input-id="is_active" />
            <FormLabel field="is_active" field-key="admin.resource_groups.form.fields.is_active" class="inline-block" />
            <FormValidationError :message="form.errors.is_active"></FormValidationError>
        </div>

        <fieldset class="space-y-4">
            <legend class="space-y-2">
                <div class="text-sm font-bold uppercase text-app-text dark:text-app-text">
                    {{ $t("admin.resource_groups.form.fields.user_groups.label") }}
                </div>

                <div class="text-xs">
                    {{ $t("admin.resource_groups.form.fields.user_groups.hint") }}
                </div>
            </legend>

            <MultiSelect
                v-model="form.user_groups"
                :options="selectedInstitution?.user_groups ?? []"
                :option-label="(userGroup) => translate(userGroup.title)"
                :option-value="(userGroup) => userGroup.id"
                :show-toggle-all="false"
                :invalid="!!form.errors.user_groups"
                :placeholder="$t('admin.resource_groups.form.fields.user_groups.placeholder')"
                display="chip"
                input-id="user-groups"
                class="w-full"
            />

            <FormValidationError :message="form.errors.user_groups"></FormValidationError>
        </fieldset>

        <FormAction
            :form="form"
            model="resource_group"
            cancel-route="admin.resource_group.index"
            :cancel-route-params="{ institution_id: form.institution_id }"
        />
    </FormLayout>
</template>
<script setup lang="ts">
import FormAction from "@/Components/Admin/FormAction.vue";
import TranslatableFormInput from "@/Components/Admin/TranslatableFormInput.vue";
import FormInput from "@/Shared/Form/FormInput.vue";
import FormLabel from "@/Shared/Form/FormLabel.vue";
import FormLayout from "@/Shared/Form/FormLayout.vue";
import FormValidationError from "@/Shared/Form/FormValidationError.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { AdminInstitution, ResourceGroup } from "@/Types/Admin";

import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = withDefaults(
    defineProps<{
        institution?: AdminInstitution;
        // Inertia provides this page prop in snake_case from the backend contract.
        // eslint-disable-next-line vue/prop-name-casing
        resource_group?: ResourceGroup;
        institutions?: AdminInstitution[];
        languages: string[];
    }>(),
    {
        institution: () => ({}),
        resource_group: () => ({}),
        institutions: () => [],
    },
);

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const translate = appStore.translate;

const form = useForm({
    id: props.resource_group?.id ?? "",
    institution_id: props.resource_group?.institution_id ?? props.institution.id,
    title: props.resource_group?.title ?? {},
    slug: props.resource_group?.slug ?? "",
    term_singular: props.resource_group?.term_singular ?? {},
    term_plural: props.resource_group?.term_plural ?? {},
    description: props.resource_group?.description ?? {},
    help_uri: props.resource_group?.help_uri ?? "",
    is_active: props.resource_group?.is_active ?? false,
    order: props.resource_group?.order?.toString() ?? "0",
    user_groups: props.resource_group?.user_groups?.map((userGroup) => userGroup.id) ?? [],
});

const selectedInstitution = computed(() => {
    return props.institutions.find((institution) => institution.id === form.institution_id);
});
</script>
