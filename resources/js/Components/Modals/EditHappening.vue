<!-- UpdateHappening.vue -->
<template>
    <div class="text-3xl font-bold">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>

    <HappeningInfo :happening="happeningData"></HappeningInfo>

    <form>
        <input v-model="happeningData.start" type="text" class="input w-full max-w-xs" name="start" id="start" />
        <input v-model="happeningData.end" type="text" class="input w-full max-w-xs" name="end" id="end" />
        <input v-model="happeningData.confirmer" type="text" class="input w-full max-w-xs" name="confirmer" id="confirmer" />
    </form>

    {{ validationErrors }}

</template>

<script setup>
import {reactive, ref, watch, watchEffect} from "vue";
import HappeningInfo from "../HappeningInfo.vue";
import {useHappeningStore} from "../../Stores/HappeningStore";
import dayjs from "dayjs";

const happeningStore = useHappeningStore()
let validationErrors = ref({})

watch(
    () => happeningStore.validationErrors,
    () => { validationErrors.value = happeningStore.getValidationErrors },
)

const emit = defineEmits([
    'update:payload',
])

const props = defineProps({
    content: Object,
    payload: Object
})

console.log(props.payload.start)

let happeningData = reactive({
    id: props.payload.id,
    resource: {
        id: props.payload.resource.id,
        title: props.payload.resource.title,
    },
    start: dayjs.utc(props.payload.start),
    end: dayjs.utc(props.payload.end),
    confirmer: props.payload.confirmer,
})

watchEffect(() => {
    emit("update:payload", happeningData);
});
</script>
