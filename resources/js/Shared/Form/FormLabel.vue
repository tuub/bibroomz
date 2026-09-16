<template>
    <div>
        <component
            :is="asLabelledby ? 'span' : 'label'"
            :id="asLabelledby ? `${field}-label` : undefined"
            :for="asLabelledby ? undefined : field"
            class="text-app-text dark:text-app-text text-sm font-bold uppercase"
        >
            {{ label }}

            <span v-if="language">
                {{ `(${language})` }}
            </span>
        </component>

        <div v-if="hint" class="text-xs">
            {{ hint }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        field: string;
        fieldKey: string;
        language?: string | null;
        // Use when the target field isn't a labelable HTML element (e.g. a PrimeVue Select's
        // non-editable trigger renders as a <span>). Renders as a plain <span id="{field}-label">
        // instead of a <label for>, since a <label> with no for/wrapped control is itself invalid;
        // reference it from the field via aria-labelledby="{field}-label".
        asLabelledby?: boolean;
    }>(),
    {
        language: null,
        asLabelledby: false,
    },
);

const hint = computed(() => trans(props.fieldKey + ".hint").trim());
const label = computed(() => trans(props.fieldKey + ".label"));
</script>
