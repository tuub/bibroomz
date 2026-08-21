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
                class="h-4 w-4 rounded border-app-border bg-app-page text-feedback-danger focus:ring-2 focus:ring-tub dark:border-app-border dark:bg-app-field dark:ring-offset-app-surface dark:focus:ring-tub"
                @change="
                    $emit('update-checked', {
                        value: props.value,
                        checked: ($event.target as HTMLInputElement).checked,
                    })
                "
            />
        </div>
        <div class="ml-2 text-sm">
            <label :for="`${name}-checkbox-${value}`" class="font-medium text-app-text dark:text-app-muted">
                {{ label }}
            </label>
            <p :id="`${name}-checkbox-text-${value}`" class="text-xs font-normal text-app-muted dark:text-app-muted">
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
