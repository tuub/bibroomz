<template>
    <button class="font-medium text-red-600 dark:text-red-500 hover:underline" @click="openModal">
        <i v-if="action === 'delete'" class="ri-delete-bin-line"></i>
        <i v-if="action === 'clone'" class="ri-file-copy-line"></i>
        <i v-if="action === 'ban'" class="ri-prohibited-line"></i>
        <i v-if="action === 'unban'" class="ri-arrow-go-back-fill"></i>
        {{ $t("admin." + model + "s.index.table.actions." + action) }}
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
        validator: (value) => ["delete", "clone", "ban", "unban"].includes(value),
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

const openModal = () => {
    modal.open(
        ConfirmModal,
        {
            message: trans("popup.content." + props.action + "." + props.model),
        },
        {},
        actions,
    );
};

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
