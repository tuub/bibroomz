<template>
    <div class="flex">
        <div class="flex h-5 items-center">
            <input
                :id="`${name}-checkbox-${value}`"
                :name="name"
                type="checkbox"
                :value="value"
                :checked="checked"
                :aria-describedby="`${name}-checkbox-text-${value}`"
                class="border-app-border bg-app-page text-feedback-danger focus:ring-tub dark:border-app-border dark:bg-app-field dark:ring-offset-app-surface dark:focus:ring-tub h-4 w-4 rounded-sm focus:ring-2"
                @change="
                    $emit('update-checked', {
                        value: props.value,
                        checked: ($event.target as HTMLInputElement).checked,
                    })
                "
            />
        </div>
        <div class="ml-2 text-sm">
            <label :for="`${name}-checkbox-${value}`" class="text-app-text dark:text-app-muted font-medium">
                {{ label }}
            </label>
            <p :id="`${name}-checkbox-text-${value}`" class="text-app-muted dark:text-app-muted text-xs font-normal">
                {{ description }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { LabeledCheckboxUpdatePayload } from "@/Types/Admin";

const props = defineProps({
    value: {
        type: [String, Number],
        required: true,
    },
    checked: {
        type: Boolean,
    },
    name: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
});

defineEmits<{
    (event: "update-checked", payload: LabeledCheckboxUpdatePayload): void;
}>();
</script>
