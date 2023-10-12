<template>
    <div class="flex">
        <div class="flex items-center h-5">
            <input
                :id="`${name}-checkbox-${value}`"
                :name="name"
                type="checkbox"
                :value="value"
                :checked="checked"
                :aria-describedby="`${name}-checkbox-text-${value}`"
                class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                @change="$emit('update-checked', { value: $event.target.value, checked: $event.target.checked })"
            />
        </div>
        <div class="ml-2 text-sm">
            <label :for="`${name}-checkbox-${value}`" class="font-medium text-gray-900 dark:text-gray-300">
                {{ label }}
            </label>
            <p :id="`${name}-checkbox-text-${value}`" class="text-xs font-normal text-gray-500 dark:text-gray-300">
                {{ description }}
            </p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    value: {
        type: String,
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

defineEmits(["update-checked"]);
</script>
