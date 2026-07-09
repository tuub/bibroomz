<template>
    <div v-if="notifications.length" class="space-y-3">
        <section
            v-for="(notification, index) in notifications"
            :key="`${notification.title ?? 'system-notification'}-${index}`"
            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950"
            role="status"
        >
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle mt-1 text-amber-600" aria-hidden="true"></i>
                <div class="space-y-1">
                    <p class="whitespace-pre-line text-sm">
                        {{ notification.message }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});
</script>
