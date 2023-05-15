<!-- HappeningModal.vue -->
<template>
    <div class="text-3xl font-bold inline-flex">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>
    <HappeningInfo :happening="happening"></HappeningInfo>
    <HappeningForm :happening="happening" v-if="editable"></HappeningForm>
</template>

<script setup>
import HappeningInfo from "@/Components/HappeningInfo.vue";
import HappeningForm from "@/Components/Modals/HappeningForm.vue";
import { reactive, watchEffect } from "vue";
import dayjs from "dayjs";

const props = defineProps({
    content: Object,
    payload: Object,
});

const emit = defineEmits(["update:payload"]);

const editable = props.payload.editable;

let happening = reactive({
    id: props.payload.id,
    resource: props.payload.resource,
    start: dayjs.utc(props.payload.start).format('YYYY-MM-DDTHH:mm:ss'),
    end: dayjs.utc(props.payload.end).format('YYYY-MM-DDTHH:mm:ss'),
    confirmer: props.payload.confirmer ?? 'BLA',
});

if (editable) {
    watchEffect(() => {
        emit("update:payload", happening);
    });
}
</script>
