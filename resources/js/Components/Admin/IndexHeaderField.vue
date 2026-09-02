<template>
    <th scope="col" class="px-6 py-3 align-top" :class="{ 'sr-only': !isLabelVisible }">
        <div @click="sort">
            <span v-if="$slots.default">
                <slot />
            </span>
            <span v-else>
                {{ label }}
            </span>
            <SortDirectionIcon v-if="isSortField" :sort-direction="sortDirection" />
            <button v-if="isFilterField" @click.stop="toggleFilter">
                <i class="ri-filter-2-line" />
            </button>
        </div>

        <div v-if="isFilterVisible" class="mt-1">
            <span class="mr-1 mb-1 normal-case">Filter:</span>
            <input
                ref="filterInput"
                class="rounded-sm p-1 outline-hidden"
                :value="filter"
                @input="$emit('update:filter', ($event.target as HTMLInputElement).value)"
                @keyup.escape="toggleFilter"
            />
        </div>
    </th>
</template>

<script setup lang="ts">
// ------------------------------------------------
// Imports
// ------------------------------------------------
import SortDirectionIcon from "@/Components/Admin/SortDirectionIcon.vue";

import { nextTick, ref } from "vue";

// ------------------------------------------------
// Props and Emits
// ------------------------------------------------
const props = defineProps({
    filter: {
        type: String,
        default: "",
    },
    isFilterField: {
        type: Boolean,
        default: false,
    },
    isLabelVisible: {
        type: Boolean,
        default: true,
    },
    isSortField: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: "",
    },
    sortDirection: {
        type: String,
        default: "desc",
    },
});

const emits = defineEmits<{
    (event: "sort"): void;
    (event: "toggle-filter", isVisible: boolean): void;
    (event: "update:filter", value: string): void;
    (event: "update:sort-direction", direction: "asc" | "desc"): void;
}>();

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isFilterVisible = ref(false);
const filterInput = ref<HTMLInputElement | null>(null);

// ------------------------------------------------
// Methods
// ------------------------------------------------
function sort() {
    if (props.isSortField) {
        emits("update:sort-direction", props.sortDirection === "asc" ? "desc" : "asc");
    } else {
        emits("sort");
    }
}

function toggleFilter() {
    isFilterVisible.value = !isFilterVisible.value;

    if (isFilterVisible.value) {
        void nextTick(() => {
            filterInput.value?.focus();
        });
    }

    emits("toggle-filter", isFilterVisible.value);
}
</script>
