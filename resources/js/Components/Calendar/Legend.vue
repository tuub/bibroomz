<script setup>
import { trans } from "laravel-vue-i18n";
import { computed } from "vue";

// ------------------------------------------------
// Variables
// ------------------------------------------------
const statuses = computed(() => [
    {
        id: "reservation",
        label: trans("legend.reservation"),
        css: "status-reservation",
        isDisplayed: true,
    },
    {
        id: "booking",
        label: trans("legend.booking"),
        css: "status-booking",
        isDisplayed: true,
    },
    {
        id: "closing",
        label: trans("legend.closing"),
        css: "status-closing",
        isDisplayed: true,
    },
    /*
    {
        id: "user-reservation",
        label: trans("legend.user-reservation"),
        css: "user-reservation",
        isDisplayed: isAuthenticated.value,
    },
    {
        id: "user-booking",
        label: trans("legend.user-booking"),
        css: "user-booking",
        isDisplayed: isAuthenticated.value,
    },
    {
        id: "user-to-verify",
        label: trans("legend.user-to-verify"),
        css: "user-to-verify",
        isDisplayed: isAuthenticated.value,
    },
     */
]);
</script>
<template>
    <div id="status-legend" class="">
        <template v-for="status in statuses">
            <Tag
                v-if="status.isDisplayed"
                :key="status.label"
                :value="status.label"
                :class="status.css"
                class="mx-0 my-0 rounded-none"
            >
                <div class="flex items-center gap-2 px-0">
                    <span class="text-xs font-normal">{{ status.label }}</span>
                </div>
            </Tag>
        </template>
    </div>
</template>
