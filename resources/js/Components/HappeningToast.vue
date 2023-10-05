<template>
    <div>
        {{ message }}
        <br />
        <span v-text="trans('toast.happening.info.date')" />: {{ date }}
        <br />
        <span v-text="trans('toast.happening.info.start')" />: {{ start }}
        <br />
        <span v-text="trans('toast.happening.info.end')" />: {{ end }}
    </div>
</template>

<script setup>
import dayjs from "dayjs";
import { trans } from "laravel-vue-i18n";

const props = defineProps({
    message: {
        type: String,
        default: "",
    },
    happening: {
        type: Object,
        default: () => ({}),
    },
});

const happeningStart = appStore.getDateTimeFromString(props.happening.start)
const happeningEnd = appStore.getDateTimeFromString(props.happening.end)

const date = appStore.formatDate(happeningStart);
const start = appStore.formatTime(happeningStart);
const end = appStore.formatTime(happeningEnd);
</script>
