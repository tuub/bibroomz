<template>
    <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline" @click.prevent="openModal()">
        {{ $t("admin." + model + "s.index.table.actions.delete") }}
    </a>
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
    model: {
        type: String,
        default: null,
    },
    entity: {
        type: Object,
        default: () => ({}),
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
            message: trans("popup.content.delete." + props.model),
        },
        props.entity,
        actions,
    );
};

// ------------------------------------------------
// Lifecycle
// ------------------------------------------------
onBeforeMount(() => {
    const deleteLabel = computed(() => trans("popup.actions.delete"));
    const cancelLabel = computed(() => trans("popup.actions.cancel"));

    const deleteAction = {
        label: deleteLabel,
        callback: () => {
            router.visit(route("admin." + props.model + ".delete", props.params), {
                method: "post",
                preserveScroll: true,
                onStart: () => modal.close(),
            });
        },
    };

    const cancelAction = {
        label: cancelLabel,
        callback: () => modal.close(),
    };

    actions.push(deleteAction, cancelAction);
});
</script>
