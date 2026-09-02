<template>
    <div v-if="notifications.length" class="space-y-3">
        <section
            v-for="(notification, index) in notifications"
            :key="`${notification.title ?? 'system-notification'}-${index}`"
            class="border-notice-warning-border bg-notice-warning-surface text-notice-warning-text dark:bg-notice-warning-surface/40 rounded-lg border px-4 py-3"
            role="status"
        >
            <div class="flex items-start gap-3">
                <i class="pi pi-exclamation-triangle text-notice-warning-icon mt-1" aria-hidden="true"></i>
                <div class="space-y-1">
                    <p class="text-sm whitespace-pre-line">
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
