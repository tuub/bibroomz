<template>
    <th scope="col" class="px-6 py-3 align-top" :class="{ 'sr-only': !isLabelVisible }">
        <div @click="sort">
            {{ label }}
            <SortDirectionIcon v-if="isSortField" :sort-direction="sortDirection" />
            <button v-if="isFilterField" @click.stop="toggleFilter">
                <i class="ri-filter-2-line" />
            </button>
        </div>

        <div v-if="isFilterVisible" class="mt-1">
            <span class="mb-1 mr-1 normal-case">Filter:</span>
            <input
                ref="filterInput"
                class="rounded p-1 outline-none"
                :value="filter"
                @input="$emit('update:filter', $event.target.value)"
                @keyup.escape="toggleFilter"
            />
        </div>
    </th>
</template>

<script setup>
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
        required: true,
    },
    sortDirection: {
        type: String,
        default: "desc",
    },
});

const emits = defineEmits(["sort", "toggle-filter", "update:filter", "update:sort-direction"]);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isFilterVisible = ref(false);
const filterInput = ref(null);

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
        nextTick(() => {
            filterInput.value.focus();
        });
    }

    emits("toggle-filter", isFilterVisible.value);
}
</script>
