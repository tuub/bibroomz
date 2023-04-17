<!-- AddReservation.vue -->
<template>
    <div class="text-3xl font-bold">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>

    <ReservationInfo :reservation="reservationData"></ReservationInfo>

    <form>
        <input v-model="reservationData.start" type="text" class="input w-full max-w-xs" name="start" id="start" />
        <input v-model="reservationData.end" type="text" class="input w-full max-w-xs" name="end" id="end" />
        <input v-model="reservationData.confirmer" type="text" class="input w-full max-w-xs" name="confirmer" id="confirmer" />
    </form>

    {{ validationErrors }}

</template>

<script setup>
import {reactive, ref, watch} from "vue";
import ReservationInfo from "../ReservationInfo.vue";
import {useReservationStore} from "../../Stores/ReservationStore";

const reservationStore = useReservationStore()
let validationErrors = ref({})

watch(
    () => reservationStore.validationErrors,
    () => { validationErrors.value = reservationStore.getValidationErrors },
)

const emit = defineEmits([
    'update:payload',
])

const props = defineProps({
    content: Object,
    payload: Object
})



console.log(validationErrors)

let reservationData = reactive({
    resource: {
        id: props.payload.resource._resource.id,
        title: props.payload.resource._resource.title,
    },
    start: props.payload.start,
    end: props.payload.end,
    confirmer: '',
})

// When reservationData changes, it will update the reference passed via v-model
watch(reservationData, (value) => {
    emit("update:payload", value);
});
</script>
