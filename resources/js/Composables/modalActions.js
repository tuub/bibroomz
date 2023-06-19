import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";

export function useCreateModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: "Create Reservation",
            description: "Create your reservation here, you won't regret it.",
        },
        payload: { ...happening, editable: true },
        actions: [
            {
                label: "Save reservation",
                callback: (happening) => {
                    return happeningStore.addHappening(happening);
                },
            },
        ],
    };
}

export function useInfoModal(happening) {
    const modal = useModal();

    return {
        view: HappeningModal,
        content: {
            title: "Show Reservation",
            description: "Info about reservation here.",
        },
        payload: { ...happening, editable: false },
        actions: [
            {
                label: "OK",
                callback: () => {
                    modal.close();
                },
            },
        ],
    };
}

export function useConfirmModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: "Confirm Happening",
            description: "Are you sure you wanna confirm this?",
        },
        payload: { ...happening, editable: false },
        actions: [
            {
                label: "Yes, confirm",
                callback: (happening) => {
                    return happeningStore.confirmHappening(happening);
                },
            },
        ],
    };
}

export function useEditModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: "Edit",
            description: "Edit your reservation here",
        },
        payload: { ...happening, editable: true },
        actions: [
            {
                label: "Update",
                callback: (happening) => {
                    return happeningStore.editHappening(happening);
                },
            },
        ],
    };
}

export function useDeleteModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: "Confirm Delete",
            description: "Are you sure you wanna delete your future?",
        },
        payload: { ...happening, editable: false },
        actions: [
            {
                label: "Yes, delete",
                callback: (happening) => {
                    return happeningStore.deleteHappening(happening.id);
                },
            },
        ],
    };
}

export function useEditDeleteModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: "Edit/Delete",
            description: "Edit/Delete your reservation here",
        },
        payload: { ...happening, editable: true },
        actions: [
            {
                label: "Update",
                callback: (happening) => {
                    return happeningStore.editHappening(happening);
                },
            },
            {
                label: "Delete",
                callback: (happening) => {
                    return happeningStore.deleteHappening(happening.id);
                },
            },
        ],
    };
}
