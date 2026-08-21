<template>
    <div v-if="notifications.length" class="space-y-3">
        <section
            v-for="(notification, index) in notifications"
            :key="`${notification.title ?? 'system-notification'}-${index}`"
            class="rounded-lg border border-notice-warning-border bg-notice-warning-surface px-4 py-3 text-notice-warning-text dark:bg-notice-warning-surface/40"
            role="status"
        >
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle mt-1 text-notice-warning-icon" aria-hidden="true"></i>
                <div class="space-y-1">
                    <p class="whitespace-pre-line text-sm">
                        {{ notification.message }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import type { SystemNotification } from "@/Stores/AppStore";

withDefaults(
    defineProps<{
        notifications?: SystemNotification[];
    }>(),
    {
        notifications: () => [],
    },
);
</script>
