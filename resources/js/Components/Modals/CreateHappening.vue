<!-- CreateHappening.vue -->
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
import {onMounted, onUpdated, reactive, ref, watch} from "vue";
import HappeningInfo from "../HappeningInfo.vue";
import {useHappeningStore} from "../../Stores/HappeningStore";

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

let happeningData = reactive({
    resource: {
        id: props.payload.resource._resource.id,
        title: props.payload.resource._resource.title,
    },
    start: props.payload.start,
    end: props.payload.end,
    confirmer: 'BLA',
})

// Perform first emit to enable prefilling of confirmer
emit("update:payload", happeningData);

// When reservationData changes, it will update the reference passed via v-model
watch(happeningData, (value) => {
    console.log('Emitting back:');
    console.log(value);
    emit("update:payload", value);
});
</script>
