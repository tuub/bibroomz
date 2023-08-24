<!-- HappeningModal.vue -->
<template>
    <div class="text-3xl font-bold inline-flex">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>

    <HappeningInfo :happening="happening" />
    <HappeningForm v-if="editable" v-model:happening="happening" @submit="$emit('submit')" />
</template>

<script setup>
import HappeningInfo from "@/Components/HappeningInfo.vue";
import HappeningForm from "@/Components/Modals/HappeningForm.vue";
import { unref, reactive, watchEffect } from "vue";
import dayjs from "dayjs";

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
const emit = defineEmits(["update:payload", "submit"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const happening = reactive({
    id: props.payload.id,
    resource: props.payload.resource,
    start: dayjs.utc(props.payload.start).format("YYYY-MM-DDTHH:mm:ss"),
    end: dayjs.utc(props.payload.end).format("YYYY-MM-DDTHH:mm:ss"),
    isVerificationRequired: props.payload.isVerificationRequired,
    verifier: props.payload.user_02,
});

const { editable } = unref(props.payload);

// ------------------------------------------------
// Watchers
// ------------------------------------------------
if (editable) {
    watchEffect(() => {
        emit("update:payload", happening);
    });
}
</script>
