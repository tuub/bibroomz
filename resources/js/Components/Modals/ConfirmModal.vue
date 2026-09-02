<template>
    <div class="text-center">
        <svg
            class="text-app-subtle dark:text-app-text mx-auto mb-4 h-12 w-12"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 20 20"
        >
            <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
            />
        </svg>

        <h3 class="text-app-muted dark:text-app-subtle text-lg font-normal">
            {{ modal.content?.message }}
        </h3>
    </div>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Imports
// ------------------------------------------------
import useModal from "@/Stores/Modal";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();
</script>
