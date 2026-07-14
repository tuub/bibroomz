<template>
    <div class="space-y-4">
        <div class="italic">
            {{ content.description }}
        </div>

        <HappeningInfo :happening="happening" />

        <HappeningForm
            v-if="editable"
            :happening="happening"
            @update-happening="$emit('update:payload', $event)"
            @submit="$emit('submit')"
        />
    </div>
</template>

<script setup lang="ts">
import HappeningInfo from "@/Components/HappeningInfo.vue";
import HappeningForm from "@/Components/Modals/HappeningForm.vue";
import { useAppStore } from "@/Stores/AppStore";
import type { HappeningEditPayload } from "@/Stores/HappeningStore";

import { reactive, toRaw } from "vue";

type HappeningModalPayload = HappeningEditPayload & {
    editable?: boolean;
};

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
defineEmits<{
    (event: "update:payload", payload: HappeningModalPayload): void;
    (event: "submit"): void;
}>();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happening = reactive({
    id: props.payload.id,
    resource: toRaw(props.payload.resource),
    start: appStore.formatDateTime(props.payload.start, true),
    end: appStore.formatDateTime(props.payload.end, true),
    user_01: props.payload.user_01 ?? null,
    isVerificationRequired: props.payload.isVerificationRequired,
    verifier: props.payload.user_02,
    label: isPlainObject(props.payload.label) ? props.payload.label : {},
});

const editable = props.payload?.editable ?? false;

// ------------------------------------------------
// Methods
// ------------------------------------------------
function isPlainObject(obj: unknown): obj is Record<string, string> {
    return typeof obj === "object" && obj !== null && !Array.isArray(obj);
}
</script>
