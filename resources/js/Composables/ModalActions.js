import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import ResourceInfoModal from "@/Components/Modals/ResourceInfoModal.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";
import { trans } from "laravel-vue-i18n";

export function useCreateModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: trans("modal.create.title"),
            description: trans("modal.create.description"),
        },
        payload: { ...happening, editable: true },
        actions: [
            {
                label: trans("modal.create.action.create"),
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
            title: trans("modal.info.title"),
            description: trans("modal.info.description"),
        },
        payload: { ...happening, editable: false },
        actions: [
            {
                label: trans("modal.info.action.ok"),
                callback: () => {
                    modal.close();
                },
            },
        ],
    };
}

export function useVerifyModalFromPermissions(happening, can = happening.can) {
    const happeningStore = useHappeningStore();

    const actions = [];
    if (can.verify) {
        actions.push({
            label: trans("modal.verify.action.verify"),
            callback: (happening) => {
                return happeningStore.verifyHappening(happening);
            },
        });
    }

    if (can.edit) {
        actions.push({
            label: trans("modal.edit.action.update"),
            callback: (happening) => {
                return happeningStore.editHappening(happening);
            },
        });
    }

    if (can.delete) {
        actions.push({
            label: trans("modal.delete.action.delete"),
            callback: (happening) => {
                return happeningStore.deleteHappening(happening.id);
            },
        });
    }

    return {
        view: HappeningModal,
        content: {
            title: trans("modal.verify.title"),
            description: trans("modal.verify.description"),
        },
        payload: { ...happening, editable: can.edit },
        actions,
    };
}

export function useEditModal(happening) {
    const happeningStore = useHappeningStore();

    return {
        view: HappeningModal,
        content: {
            title: trans("modal.edit.title"),
            description: trans("modal.edit.description"),
        },
        payload: { ...happening, editable: true },
        actions: [
            {
                label: trans("modal.edit.action.update"),
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
            title: trans("modal.delete.title"),
            description: trans("modal.delete.description"),
        },
        payload: { ...happening, editable: false },
        actions: [
            {
                label: trans("modal.delete.action.delete"),
                callback: (happening) => {
                    return happeningStore.deleteHappening(happening.id);
                },
            },
        ],
    };
}

export function useEditModalFromPermissions(happening, can = happening.can) {
    const happeningStore = useHappeningStore();

    const actions = [];
    if (can.edit) {
        actions.push({
            label: trans("modal.edit.action.update"),
            callback: (happening) => {
                return happeningStore.editHappening(happening);
            },
        });
    }

    if (can.delete) {
        actions.push({
            label: trans("modal.delete.action.delete"),
            callback: (happening) => {
                return happeningStore.deleteHappening(happening.id);
            },
        });
    }

    return {
        view: HappeningModal,
        content: {
            title: trans("modal.edit_delete.title"),
            description: trans("modal.edit_delete.description"),
        },
        payload: { ...happening, editable: can.edit },
        actions,
    };
}

export function useResourceInfoModal(resourceInfo) {
    const modal = useModal();

    return {
        view: ResourceInfoModal,
        content: {
            title: trans("modal.resource_info.title", { resource_title: resourceInfo.resource.title }),
            description: trans("modal.resource_info.description"),
        },
        payload: { ...resourceInfo },
        actions: [
            {
                label: trans("modal.resource_info.action.ok"),
                callback: () => {
                    modal.close();
                },
            },
        ],
    };
}
