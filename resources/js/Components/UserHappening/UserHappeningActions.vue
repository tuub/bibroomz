<script setup>
import SidebarButton from "@/Components/Sidebar/SidebarButton.vue";
import { useHappeningDeleteModal, useHappeningEditModal, useHappeningVerifyModal } from "@/Composables/ModalActions";
import useModal from "@/Stores/Modal";

// ------------------------------------------------
// Props
// ------------------------------------------------
defineProps({
    happening: {
        type: Object,
        default: () => ({}),
    },
});

// ------------------------------------------------
// Variables
// ------------------------------------------------
const modal = useModal();

// ------------------------------------------------
// Modal Actions
// ------------------------------------------------
const editUserHappening = (happening) => {
    const editModal = useHappeningEditModal(happening);
    modal.open(editModal.view, editModal.content, editModal.payload, editModal.actions);
};

const verifyUserHappening = (happening) => {
    const verifyModal = useHappeningVerifyModal(happening);
    modal.open(verifyModal.view, verifyModal.content, verifyModal.payload, verifyModal.actions);
};

const deleteUserHappening = (happening) => {
    const deleteModal = useHappeningDeleteModal(happening);
    modal.open(deleteModal.view, deleteModal.content, deleteModal.payload, deleteModal.actions);
};
</script>
<template>
    <div class="flex flex-wrap space-x-1">
        <SidebarButton
            v-if="happening.can.verify"
            icon="pi pi-check"
            type="verify"
            :label="$t('user_happenings.item.form.verify')"
            @click="verifyUserHappening(happening)"
        />
        <SidebarButton
            v-if="happening.can.edit"
            icon="pi pi-pencil"
            type="edit"
            :label="$t('user_happenings.item.form.edit')"
            @click="editUserHappening(happening)"
        />
        <SidebarButton
            v-if="happening.can.delete"
            icon="pi pi-trash"
            type="delete"
            :label="$t('user_happenings.item.form.delete')"
            @click="deleteUserHappening(happening)"
        />
    </div>
</template>
