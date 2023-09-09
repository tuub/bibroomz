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

import dayjs from "dayjs";
import { toRaw } from "vue";

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
// Emits
// ------------------------------------------------
defineEmits(["update:payload", "submit"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happening = {
    id: props.payload.id,
    resource: toRaw(props.payload.resource),
    start: dayjs.utc(props.payload.start).format("YYYY-MM-DDTHH:mm:ss"),
    end: dayjs.utc(props.payload.end).format("YYYY-MM-DDTHH:mm:ss"),
    isVerificationRequired: props.payload.isVerificationRequired,
    verifier: props.payload.user_02,
};

const editable = props.payload?.editable ?? false;
</script>
