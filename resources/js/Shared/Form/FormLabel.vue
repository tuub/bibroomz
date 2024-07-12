<template>
    <label v-if="field" :for="field" :class="labelClasses">
        {{ $t(fieldKey + ".label") }}
        <span v-if="language">({{ language }})</span>
    </label>
    <div v-else :class="labelClasses">
        {{ $t(fieldKey + ".label") }}
    </div>
    <div v-if="hint" class="mb-2 text-xs">
        {{ $t(fieldKey + ".hint") }}
    </div>
</template>

<script setup>
defineProps({
    field: {
        type: String,
        default: "",
    },
    fieldKey: {
        type: String,
        required: true,
    },
    hint: {
        type: Boolean,
        default: true,
    },
    language: {
        type: String,
        default: "",
    },
});

const labelClasses = "block mb-2 text-sm font-bold text-gray-900 dark:text-white uppercase";
</script>
