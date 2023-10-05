<template>
    <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline" @click.prevent="openModal()">
        {{ $t("admin." + model + "s.index.table.actions.delete") }}
    </a>
</template>
<script setup>
import useModal from "@/Stores/Modal";

import { router } from "@inertiajs/vue3";
import { Modal as FlowbiteModal } from "flowbite";
import { trans } from "laravel-vue-i18n";
import { computed, inject, onBeforeMount, onMounted } from "vue";

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
        {},
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

    const deleteAction = {
        label: deleteLabel,
        callback: () => {
            console.log("Route Visit: " + route("admin." + props.model + ".delete", props.params));
            router.visit(route("admin." + props.model + ".delete", props.params), {
                method: "post",
                preserveScroll: true,
            });
        },
    };

    actions.push(deleteAction);
});

onMounted(() => {
    modal.init(
        new FlowbiteModal(document.getElementById("popup-modal"), {
            closable: true,
            placement: "center",
            backdrop: "dynamic",
            backdropClasses: "bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-40",
            onHide: () => {
                modal.cleanup();
            },
        }),
    );
});
</script>
