<template>
    <button class="font-medium text-feedback-danger hover:underline" @click="openModal">
        <i class="ri-user-shared-line"></i>
        {{ $t("admin.users.index.table.actions.impersonate") }}
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

const modal = useModal();
const route = inject<ZiggyRouteFn>("ziggyRoute")!;

const props = withDefaults(
    defineProps<{
        params?: Record<string, unknown>;
    }>(),
    {
        params: () => ({}),
    },
);

const actions: ModalAction[] = [];

function openModal() {
    modal.open(
        ConfirmModal,
        {
            message: trans("popup.content.impersonate.user"),
        },
        {},
        actions,
    );
}

onBeforeMount(() => {
    const confirmAction: ModalAction = {
        label: trans("popup.actions.impersonate"),
        callback: async () => {
            router.post(
                route("admin.user.impersonate", props.params),
                {},
                {
                    onStart: () => modal.close(),
                    onSuccess: () => {
                        window.location.reload();
                    },
                },
            );
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
