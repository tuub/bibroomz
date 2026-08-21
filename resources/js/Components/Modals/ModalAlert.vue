<template>
    <div
        id="alert-2"
        class="mb-4 flex rounded-lg bg-feedback-danger-soft p-4 text-feedback-danger-text dark:bg-app-surface"
        role="alert"
    >
        <svg
            aria-hidden="true"
            class="h-5 w-5 flex-shrink-0"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd"
            ></path>
        </svg>
        <span class="sr-only">Info</span>
        <div class="ml-3 text-sm font-medium">
            {{ error }}
        </div>
        <button
            type="button"
            class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 rounded-lg bg-feedback-danger-soft p-1.5 text-feedback-danger hover:bg-feedback-danger-soft-hover focus:ring-2 focus:ring-feedback-danger-ring dark:bg-app-surface dark:hover:bg-app-field"
            data-dismiss-target="#alert-2"
            aria-label="Close"
            @click="close"
        >
            <span class="sr-only">Close</span>
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                ></path>
            </svg>
        </button>
    </div>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    error: {
        type: String,
        required: true,
    },
});

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits<{
    (event: "close"): void;
}>();

// ------------------------------------------------
// Methods
// ------------------------------------------------
const close = () => {
    emit("close");
};
</script>
