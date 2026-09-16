<template>
    <div>
        <form class="max-w mx-auto mt-8 space-y-6">
            <slot />
        </form>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    description: string;
}>();
</script>
