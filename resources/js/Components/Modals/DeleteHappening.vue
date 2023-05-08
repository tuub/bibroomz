<!-- UpdateHappening.vue -->
<template>
    <div class="text-3xl font-bold">
        {{ content.title }}
    </div>
    <div class="italic mt-4 mb-4">
        {{ content.description }}
    </div>

    <HappeningInfo :happening="payload"></HappeningInfo>

</template>

<script setup>
import {ref, watch} from "vue";
import HappeningInfo from "../HappeningInfo.vue";
import {useHappeningStore} from "../../Stores/HappeningStore";

const happeningStore = useHappeningStore()
let validationErrors = ref({})

watch(
    () => happeningStore.validationErrors,
    () => { validationErrors.value = happeningStore.getValidationErrors },
)

const props = defineProps({
    content: Object,
    payload: Object
})
</script>
