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

const date = dayjs(props.happening.start).format("DD.MM.YYYY");
const start = dayjs(props.happening.start).format("H:mm");
const end = dayjs(props.happening.end).format("H:mm");
</script>
