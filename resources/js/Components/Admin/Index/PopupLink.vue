<template>
    <button class="font-medium text-red-600 hover:underline dark:text-red-500" @click="openModal">
        <i :class="icons[action]"></i>
        {{ label ?? $t("admin." + model + "s.index.table.actions." + action) }}
    </button>
</template>

<script setup>
import ConfirmModal from "@/Components/Modals/ConfirmModal.vue";
import useModal from "@/Stores/Modal";

import { router } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount } from "vue";

const modal = useModal();
const route = inject("ziggyRoute");

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: null,
    },
    model: {
        type: String,
        required: true,
    },
    params: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const actions = [];

const icons = {
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
    const confirmLabel = computed(() => trans("popup.actions." + props.action));
    const cancelLabel = computed(() => trans("popup.actions.cancel"));

    const confirmAction = {
        label: confirmLabel,
        callback: () => {
            router.visit(route("admin." + props.model + "." + props.action, props.params), {
                method: "post",
                onStart: () => modal.close(),
                preserveScroll: true,
                preserveState: true,
            });
        },
    };

    const cancelAction = {
        label: cancelLabel,
        callback: () => modal.close(),
    };

    actions.push(confirmAction, cancelAction);
});
</script>
