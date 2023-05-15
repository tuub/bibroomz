<template>
    <input
        :value="input"
        @input="$emit('update:input', $event.target.value)"
        class="block py-2.5 px-0 w-full text-sm text-gray-900 bg-transparent border-0 border-b-2 border-gray-300 appearance-none dark:text-white dark:border-gray-600 dark:focus:border-blue-500 focus:outline-none focus:ring-0 focus:border-blue-600 peer"
    />
</template>

<script setup>
const props = defineProps({ input: String });
const emit = defineEmits(["update:input"]);
</script>
