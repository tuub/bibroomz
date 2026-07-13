<template>
    <input
        :value="input"
        class="peer block w-full appearance-none border-0 border-b-2 border-gray-300 bg-transparent px-0 py-2.5 text-sm text-gray-900 focus:border-red-600 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-red-500"
        @input="updateInput"
    />
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        input?: string;
    }>(),
    {
        input: "",
    },
);

const emit = defineEmits<{
    "update:input": [value: string];
}>();

const updateInput = (event: Event) => {
    emit("update:input", (event.target as HTMLInputElement).value);
};
</script>
