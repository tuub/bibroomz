<template>
    <div class="space-x-5">
        <span class="space-x-1">
            <button :disabled="currentPage == 1" @click="$emit('page-changed', 1)">
                <i class="ri-skip-left-line"></i>
            </button>

            <button :disabled="!prevPage || currentPage == prevPage" @click="$emit('page-changed', prevPage)">
                <i class="ri-arrow-left-s-line"></i>
            </button>

            <span>
                <input
                    inputmode="numeric"
                    class="bg-transparent outline-none text-center"
                    :style="{ width: inputWidth }"
                    :value="currentPage"
                    :placeholder="currentPage"
                    @change="$emit('page-changed', $event.target.value)"
                />
                <span v-if="lastPage > 1"> / {{ lastPage }} </span>
            </span>

            <button :disabled="!nextPage || currentPage == nextPage" @click="$emit('page-changed', nextPage)">
                <i class="ri-arrow-right-s-line"></i>
            </button>

            <button :disabled="currentPage == lastPage" @click="$emit('page-changed', lastPage)">
                <i class="ri-skip-right-line"></i>
            </button>
        </span>

        <span class="space-x-2">
            Per Page:
            <button
                v-for="newPerPageValue in [10, 20, 30]"
                :key="newPerPageValue"
                :disabled="perPage === newPerPageValue"
                :class="{ 'font-semibold': perPage === newPerPageValue, 'font-normal': perPage !== newPerPageValue }"
                @click.prevent="$emit('update:per-page', newPerPageValue)"
            >
                {{ newPerPageValue }}
            </button>
        </span>

        <span>
            <button
                :disabled="perPage === -1"
                :class="{ 'font-semibold': perPage === -1, 'font-normal': perPage !== -1 }"
                @click.prevent="$emit('update:per-page', -1)"
            >
                Show All
            </button>
        </span>
    </div>
</template>
<script setup>
const props = defineProps({
    currentPage: {
        type: Number,
        required: true,
    },
    lastPage: {
        type: Number,
        required: true,
    },
    nextPage: {
        type: String,
        default: null,
    },
    perPage: {
        type: Number,
        required: true,
    },
    prevPage: {
        type: String,
        default: null,
    },
});

defineEmits(["update:per-page", "page-changed"]);

const inputWidth = props.lastPage.toString().length + "em";
</script>
