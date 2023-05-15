<template>
    <div class="mt-4 space-y-3">
        <div class="grid md:grid-cols-2 md:gap-2">
            <div class="relative z-0 w-full mb-6 group">
                <HappeningFormInput
                    v-model:input="happening.start"
                    type="datetime-local"
                    name="start"
                    id="start"
                    required
                />
                <label
                    for="start"
                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6"
                    >Beginn:</label
                >
            </div>
            <div class="relative z-0 w-full mb-6 group">
                <HappeningFormInput
                    v-model:input="happening.end"
                    type="datetime-local"
                    name="end"
                    id="end"
                    required
                />
                <label
                    for="end"
                    class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6"
                    >Ende:</label
                >
            </div>
        </div>
        <div class="relative z-0 w-full mb-6 group">
            <HappeningFormInput
                v-model:input="happening.confirmer"
                type="text"
                name="confirmer"
                id="confirmer"
                required
            />
            <label
                for="confirmer"
                class="peer-focus:font-medium absolute text-sm text-gray-500 dark:text-gray-400 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:left-0 peer-focus:text-blue-600 peer-focus:dark:text-blue-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6"
                >Bestätitgung durch:</label
            >
        </div>
    </div>

    <div v-if="validationErrors">{{ validationErrors }}</div>
</template>

<script setup>
import HappeningFormInput from "./HappeningFormInput.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import { storeToRefs } from "pinia";

const props = defineProps({
    happening: Object,
});

const happeningStore = useHappeningStore();
let { validationErrors } = storeToRefs(happeningStore);
</script>
