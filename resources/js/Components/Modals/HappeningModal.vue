<!-- HappeningModal.vue -->
<template>
    <div class="text-3xl font-bold inline-flex">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>

    <HappeningInfo :happening="happening" />
    <HappeningForm
        v-if="editable"
        :happening="happening"
        @update-happening="$emit('update:payload', $event)"
        @submit="$emit('submit')"
    />
</template>

<script setup>
import HappeningInfo from "@/Components/HappeningInfo.vue";
import HappeningForm from "@/Components/Modals/HappeningForm.vue";
import { useAppStore } from "@/Stores/AppStore";

import { reactive, toRaw } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
    payload: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Stores
// ------------------------------------------------
const appStore = useAppStore();

// ------------------------------------------------
// Emits
// ------------------------------------------------
defineEmits(["update:payload", "submit"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happening = reactive({
    id: props.payload.id,
    resource: toRaw(props.payload.resource),
    start: appStore.formatDateTime(props.payload.start, true),
    end: appStore.formatDateTime(props.payload.end, true),
    isVerificationRequired: props.payload.isVerificationRequired,
    verifier: props.payload.user_02,
    label: isPlainObject(props.payload.label) ? props.payload.label : {},
});

const editable = props.payload?.editable ?? false;

// ------------------------------------------------
// Methods
// ------------------------------------------------
function isPlainObject(obj) {
    return typeof obj === "object" && obj !== null && !Array.isArray(obj);
}
</script>
