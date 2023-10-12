<template>
    <input
        :value="input"
        class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-red-500 focus:outline-none focus:ring-0 focus:border-red-600 peer"
        @input="$emit('update:input', $event.target.value)"
    />
</template>

<script setup>
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    input: {
        type: String,
        default: "",
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
defineEmits(["update:input"]);
</script>
