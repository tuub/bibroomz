<template>
    <div class="flex">
        <div class="flex items-center h-5">
            <input
                :id="`permission-checkbox-${index}`"
                v-model="checked"
                :value="permission.id"
                :aria-describedby="`permission-checkbox-text-${index}`"
                type="checkbox"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                @change="$emit('update-checked', { permissionId: permission.id, checked })"
            />
        </div>
        <div class="ml-2 text-sm">
            <label :for="`permission-checkbox-${index}`" class="font-medium text-gray-900 dark:text-gray-300">{{
                translate(permission.name)
            }}</label>
            <p :id="`permission-checkbox-text-${index}`" class="text-xs font-normal text-gray-500 dark:text-gray-300">
                {{ translate(permission.description) }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { inject, ref } from "vue";

const props = defineProps({
    permission: {
        type: Object,
        required: true,
    },
    index: {
        type: Number,
        required: true,
    },
    checked: {
        type: Boolean,
    },
});
defineEmits(["update-checked"]);

const translate = inject("translate");
const checked = ref(props.checked);
</script>
