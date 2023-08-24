<template>
    <div>
        <a v-if="isExpandable" @click.prevent="toggleVisibility">
            <div v-if="isVisible">
                <span class="underline">{{ $t('modal.resource_info.hide') }}</span>
                <i  class="ri-arrow-up-s-line"></i>
            </div>
            <div v-else>
                <span class="underline">{{ $t('modal.resource_info.show') }}</span>
                <i  class="ri-arrow-down-s-line"></i>
            </div>
        </a>
        <div v-if="isVisible">
            <div class="font-bold">{{ $t('modal.resource_info.resource_capacity') }}</div>
            <div class="mb-2">{{ capacity }}</div>
            <div class="font-bold">{{ $t('modal.resource_info.resource_description') }}</div>
            <div class="mb-2">{{ description }}</div>
            <div class="font-bold">{{ $t('modal.resource_info.resource_location') }}</div>
            <div class="mb-2">
                <template v-if="locationUri">
                    <a class="underline" :href="locationUri" target="_blank">
                        {{ location }}
                    </a>
                </template>
                <template v-else>
                    {{ location }}
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref} from 'vue';

// ------------------------------------------------
// Props
// ------------------------------------------------
let props = defineProps({
    resource: Object,
    isExpandable: Boolean,
    isInitiallyVisible: Boolean,
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const isVisible = ref(props.isInitiallyVisible)
let capacity = props.resource.capacity ?? props.resource.extendedProps?.capacity
let description = props.resource.description ?? props.resource.extendedProps?.description
let location = props.resource.location ?? props.resource.extendedProps?.location
let locationUri = props.resource.location_uri ?? props.resource.extendedProps?.location_uri

const toggleVisibility = () => {
    isVisible.value = !isVisible.value
    console.log('isVisible now ' + isVisible.value)
}
</script>

<style>
a {
    cursor: pointer;
}
</style>
