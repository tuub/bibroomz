<template>
    <div class="space-y-2">
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

        <div v-if="isVisible" class="space-y-2">
            <div>
                <span class="font-bold">{{ $t("modal.resource_info.resource_title") }}</span>
                <div v-if="resource.location_uri">
                    <a class="tu-red hover:underline" :href="resource.location_uri" target="_blank">
                        {{ resource.resourceGroup }}
                        {{ resource.title }}
                        <i class="ri-map-pin-2-line"></i>
                    </a>
                </div>
                <div v-else>
                    {{ resource.title }}
                </div>
            </div>
            <div>
                <span class="font-bold">{{ $t("modal.resource_info.resource_location") }}</span>
                <div>{{ resource.location }}</div>
            </div>
            <div>
                <span class="font-bold">{{ $t("modal.resource_info.resource_capacity") }}</span>
                <div>{{ resource.capacity }}</div>
            </div>
            <div>
                <span class="font-bold">{{ $t("modal.resource_info.resource_description") }}</span>
                <div>{{ resource.description }}</div>
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

.tu-red {
    color: #c40d20;
}
</style>
