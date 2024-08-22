<template>
    <div>
        <label :for="field" class="text-sm font-bold uppercase text-gray-900 dark:text-white">
            {{ label }}

            <span v-if="language">
                {{ `(${language})` }}
            </span>
        </label>

        <div v-if="hint" class="text-xs">
            {{ hint }}
        </div>
    </div>
</template>

<script setup>
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

const props = defineProps({
    field: {
        type: String,
        required: true,
    },
    fieldKey: {
        type: String,
        required: true,
    },
    language: {
        type: String,
        default: null,
    },
});

const hint = computed(() => trans(props.fieldKey + ".hint").trim());
const label = computed(() => trans(props.fieldKey + ".label"));
</script>
