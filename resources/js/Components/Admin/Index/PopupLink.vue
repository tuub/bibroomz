<template>
    <button class="font-medium text-red-600 hover:underline dark:text-red-500" @click="openModal">
        <i :class="icons[action]"></i>
        {{ label ?? $t("admin." + model + "s.index.table.actions." + action) }}
    </button>
</template>

<script setup lang="ts">
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import type { ModalAction } from "@/Stores/Modal";
import useModal from "@/Stores/Modal";
import type { ZiggyRouteFn } from "@/ziggyRoute";

import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { inject, onBeforeMount } from "vue";

type PopupLinkAction = "delete" | "clone" | "ban" | "unban" | "remove";

const modal = useModal();
const route = inject<ZiggyRouteFn>("ziggyRoute");

const props = withDefaults(
    defineProps<{
        action: PopupLinkAction;
        label?: string | null;
        model: string;
        params?: Record<string, unknown>;
    }>(),
    {
        label: null,
        params: () => ({}),
    },
);

// ------------------------------------------------
// Variables
// ------------------------------------------------
const actions: ModalAction[] = [];

const icons: Record<PopupLinkAction, string> = {
    delete: "ri-delete-bin-line",
    clone: "ri-file-copy-line",
    ban: "ri-prohibited-line",
    unban: "ri-arrow-go-back-fill",
    remove: "ri-close-circle-line",
};

// ------------------------------------------------
// Methods
// ------------------------------------------------
function openModal() {
    modal.open(
        ConfirmModal,
        {
            message: trans("popup.content." + props.action + "." + props.model),
        },
        {},
        actions,
    );
}

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const confirmAction: ModalAction = {
        label: trans("popup.actions." + props.action),
        callback: async () => {
            router.visit(route("admin." + props.model + "." + props.action, props.params), {
                method: "post",
                onStart: () => modal.close(),
                preserveScroll: true,
                preserveState: true,
            });
        },
    };

    const cancelAction: ModalAction = {
        label: trans("popup.actions.cancel"),
        callback: async () => {
            modal.close();
        },
    };

    actions.push(confirmAction, cancelAction);
});
</script>
