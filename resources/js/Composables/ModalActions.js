import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import LoginModal from "@/Components/Modals/LoginModal.vue";
import ResourceGroupInfoModal from "@/Components/Modals/ResourceGroupInfoModal.vue";
import ResourceInfoModal from "@/Components/Modals/ResourceInfoModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import { useHappeningStore } from "@/Stores/HappeningStore";
import useModal from "@/Stores/Modal";

import { trans } from "laravel-vue-i18n";

function happeningCallback(callback) {
    const modal = useModal();
    const happeningStore = useHappeningStore();

    happeningStore.error = null;

    return async (happening) => {
        happeningStore.error = null;

        try {
            await callback(happening);
            modal.close();
        } catch (error) {
            happeningStore.error = error.response;
        }
    };
}

function callLogin({ loginCallback, happeningModalCallback }) {
    const modal = useModal();
    const authStore = useAuthStore();

    authStore.error = null;

    return async ({ username, password }) => {
        if (authStore.isProcessingLogin) {
            return;
        }

        authStore.isProcessingLogin = true;
        authStore.error = null;

        try {
            await loginCallback({ username, password });

            if (!happeningModalCallback) {
                modal.close();
            } else {
                happeningModalCallback();
            }
        } catch (error) {
            authStore.error = error.response;
        }

        authStore.isProcessingLogin = false;
    };
}

export function useHappeningModal({ happening, can = happening.can, title, description, editable = false }) {
    const modal = useModal();
    const happeningStore = useHappeningStore();

    const actions = [];

    if (can) {
        if (can.verify && editable) {
            actions.push({
                label: trans("modal.verify.action.verify"),
                callback: happeningCallback((happening) => {
                    return happeningStore.verifyHappening(happening);
                }),
            });
        }

        if (can.edit && editable) {
            actions.push({
                label: trans("modal.edit.action.update"),
                callback: happeningCallback((happening) => {
                    return happeningStore.editHappening(happening);
                }),
            });
        }

        if (can.delete) {
            actions.push({
                label: trans("modal.delete.action.delete"),
                callback: happeningCallback((happening) => {
                    return happeningStore.deleteHappening(happening.id);
                }),
            });
        }
    } else if (editable) {
        actions.push({
            label: trans("modal.create.action.create"),
            callback: happeningCallback((happening) => {
                return happeningStore.addHappening(happening);
            }),
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

export function useResourceGroupInfoModal(resourceGroup) {
    const modal = useModal();
    const appStore = useAppStore();
    const translate = appStore.translate;

    return {
        view: ResourceGroupInfoModal,
        content: {
            title: translate(resourceGroup.title),
            description: translate(resourceGroup.description),
        },
        payload: { resourceGroup },
        actions: [
            {
                label: trans("modal.resource_group_info.action.ok"),
                callback: () => {
                    modal.close();
                },
            },
        ],
    };
}

export function useResourceInfoModal(resource) {
    const modal = useModal();
    const appStore = useAppStore();
    const translate = appStore.translate;

    return {
        view: ResourceInfoModal,
        content: {
            title: trans("modal.resource_info.title", {
                resource_group: translate(appStore.resourceGroup.term_singular),
                resource_title: resource.title,
            }),
        },
        payload: { resource },
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

export function useLoginModal(happeningModalCallback) {
    const authStore = useAuthStore();
    return {
        view: LoginModal,
        content: {
            title: trans("login.header"),
            description: trans("login.description"),
        },
        payload: {
            username: "",
            password: "",
        },
        actions: [
            {
                label: trans("login.form.submit.label"),
                callback: callLogin({
                    loginCallback: ({ username, password }) => authStore.login(username, password),
                    happeningModalCallback,
                }),
            },
        ],
    };
}
