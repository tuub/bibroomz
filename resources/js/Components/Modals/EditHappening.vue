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
        <input v-model="happeningData.verifier" type="text" class="input w-full max-w-xs" name="verifier" id="verifier" />
    </form>

    {{ validationErrors }}

</template>

<script setup>
import {reactive, ref, watch, watchEffect} from "vue";
import HappeningInfo from "../HappeningInfo.vue";
import {useHappeningStore} from "../../Stores/HappeningStore";
import dayjs from "dayjs";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    content: Object,
    payload: Object
})

// ------------------------------------------------
// Stores
// ------------------------------------------------
const happeningStore = useHappeningStore()

// ------------------------------------------------
// Variables
// ------------------------------------------------
let validationErrors = ref({})
let happeningData = reactive({
    id: props.payload.id,
    resource: {
        id: props.payload.resource.id,
        title: props.payload.resource.title,
    },
    start: dayjs.utc(props.payload.start),
    end: dayjs.utc(props.payload.end),
    verifier: props.payload.verifier,
})

// ------------------------------------------------
// Emits
// ------------------------------------------------
const emit = defineEmits([
    'update:payload',
])

// ------------------------------------------------
// Watchers
// ------------------------------------------------
watch(
    () => happeningStore.validationErrors,
    () => { validationErrors.value = happeningStore.getValidationErrors },
)

watchEffect(() => {
    emit("update:payload", happeningData);
});
</script>
