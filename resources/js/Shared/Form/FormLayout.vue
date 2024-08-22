<template>
    <div>
        <PageHead :title="title" page-type="admin" />
        <BodyHead :title="title" :description="description" />

        <form class="max-w mx-auto mt-8 space-y-6">
            <slot />
        </form>
    </div>
</template>

<script setup>
import BodyHead from "@/Shared/BodyHead.vue";
import PageHead from "@/Shared/PageHead.vue";

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>
