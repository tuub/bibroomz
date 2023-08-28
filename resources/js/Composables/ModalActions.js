import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import ResourceInfoModal from "@/Components/Modals/ResourceInfoModal.vue";
import { useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";
import { trans } from "laravel-vue-i18n";

export function useHappeningModal({ happening, can = happening.can, title, description, editable = false }) {
    const modal = useModal();
    const happeningStore = useHappeningStore();

    const actions = [];

    if (can) {
        if (can.verify && editable) {
            actions.push({
                label: trans("modal.verify.action.verify"),
                callback: (happening) => {
                    return happeningStore.verifyHappening(happening);
                },
            });
        }

        if (can.edit && editable) {
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
    } else if (editable) {
        actions.push({
            label: trans("modal.create.action.create"),
            callback: (happening) => {
                return happeningStore.addHappening(happening);
            },
        });
    }

    if (actions.length < 1) {
        actions.push({
            label: trans("modal.info.action.ok"),
            callback: () => {
                modal.close();
            },
        });
    }

    return {
        view: HappeningModal,
        content: {
            title,
            description,
        },
        payload: { ...happening, editable },
        actions,
    };
}

export function useHappeningCreateModal(happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.create.title"),
        description: trans("modal.create.description"),
        editable: true,
    });
}

export function useHappeningVerifyModal(happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.verify.title"),
        description: trans("modal.verify.description"),
        editable: true,
    });
}

export function useHappeningEditModal(happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.edit.title"),
        description: trans("modal.edit.description"),
        editable: true,
    });
}

export function useHappeningDeleteModal(happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.delete.title"),
        description: trans("modal.delete.description"),
        editable: false,
    });
}

export function useHappeningInfoModal(happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.info.title"),
        description: trans("modal.info.description"),
        editable: false,
    });
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
