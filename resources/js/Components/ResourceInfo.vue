<template>
    <div>
        <a v-if="isExpandable" @click.prevent="toggleVisibility">
            <div v-if="isVisible">
                <span class="underline">{{ $t("modal.resource_info.hide") }}</span>
                <i class="ri-arrow-up-s-line"></i>
            </div>
            <div v-else>
                <span class="underline">{{ $t("modal.resource_info.show") }}</span>
                <i class="ri-arrow-down-s-line"></i>
            </div>
        </a>
        <div v-if="isVisible">
            <div class="font-bold">{{ $t("modal.resource_info.resource_capacity") }}</div>
            <div class="mb-2">{{ resource.capacity }}</div>
            <div class="font-bold">{{ $t("modal.resource_info.resource_description") }}</div>
            <div class="mb-2">{{ resource.description }}</div>
            <div class="font-bold">{{ $t("modal.resource_info.resource_location") }}</div>
            <div class="mb-2">
                <template v-if="resource.location_uri">
                    <a class="underline" :href="resource.location_uri" target="_blank">
                        {{ resource.location }}
                    </a>
                </template>
                <template v-else>
                    {{ resource.location }}
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

// ------------------------------------------------
// Props
// ------------------------------------------------
const props = defineProps({
    resource: {
        type: Object,
        required: true,
    },
    isExpandable: Boolean,
    isInitiallyVisible: Boolean,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isVisible = ref(props.isInitiallyVisible);

const toggleVisibility = () => {
    isVisible.value = !isVisible.value;
};
</script>

<style>
a {
    cursor: pointer;
}
</style>
