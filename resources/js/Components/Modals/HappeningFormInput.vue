<template>
    <input
        :value="input"
        class="peer border-app-border text-app-text focus:border-tub dark:border-app-border dark:text-app-text dark:focus:border-tub block w-full appearance-none border-0 border-b-2 bg-transparent px-0 py-2.5 text-sm focus:ring-0 focus:outline-hidden"
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
