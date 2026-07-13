import HappeningModal from "@/Components/Modals/HappeningModal.vue";
import LoginModal from "@/Components/Modals/LoginModal.vue";
import ResourceGroupInfoModal from "@/Components/Modals/ResourceGroupInfoModal.vue";
import ResourceInfoModal from "@/Components/Modals/ResourceInfoModal.vue";
import { useAppStore } from "@/Stores/AppStore";
import { useAuthStore } from "@/Stores/AuthStore";
import type { Happening } from "@/Stores/HappeningStore";
import { useHappeningStore } from "@/Stores/HappeningStore";
import type { ModalAction } from "@/Stores/Modal";
import useModal from "@/Stores/Modal";
import type { ApiError } from "@/Types/Api";

import { trans } from "laravel-vue-i18n";

type Translatable = Record<string, string>;

function getApiError(error: unknown): ApiError {
    if (typeof error === "object" && error !== null && "response" in error) {
        return (error as { response?: ApiError }).response ?? null;
    }

    return null;
}

function happeningCallback(callback: (happening: Happening) => Promise<unknown>) {
    const modal = useModal();
    const happeningStore = useHappeningStore();

    happeningStore.error = null;

    return async (happening: Happening) => {
        happeningStore.error = null;

        try {
            await callback(happening);
            modal.close();
        } catch (error) {
            happeningStore.error = getApiError(error);
        }
    };
}

function callLogin({
    loginCallback,
    happeningModalCallback,
}: {
    loginCallback: (credentials: { username: string; password: string }) => Promise<unknown>;
    happeningModalCallback?: () => void;
}) {
    const modal = useModal();
    const authStore = useAuthStore();

    authStore.error = null;

    return async ({ username, password }: { username: string; password: string }) => {
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
            authStore.error = getApiError(error);
        }

        authStore.isProcessingLogin = false;
    };
}

export function useHappeningModal({
    happening,
    can = happening.can,
    title,
    description,
    editable = false,
}: {
    happening: Happening;
    can?: Happening["can"];
    title?: string;
    description?: string;
    editable?: boolean;
}) {
    const modal = useModal();
    const happeningStore = useHappeningStore();

    const actions: ModalAction[] = [];

    if (can) {
        if (can.verify && editable) {
            actions.push({
                label: trans("modal.verify.action.verify"),
                testId: "modal-action-verify",
                callback: happeningCallback((happening) => {
                    return happeningStore.verifyHappening(happening);
                }),
            });
        }

        if (can.edit && editable) {
            actions.push({
                label: trans("modal.edit.action.update"),
                testId: "modal-action-update",
                callback: happeningCallback((happening) => {
                    return happeningStore.editHappening(happening);
                }),
            });
        }

        if (can.delete) {
            actions.push({
                label: trans("modal.delete.action.delete"),
                testId: "modal-action-delete",
                callback: happeningCallback((happening) => {
                    return happeningStore.deleteHappening(happening.id);
                }),
            });
        }
    } else if (editable) {
        actions.push({
            label: trans("modal.create.action.create"),
            testId: "modal-action-create",
            callback: happeningCallback((happening) => {
                return happeningStore.addHappening(happening);
            }),
        });
    }

    if (actions.length < 1) {
        actions.push({
            label: trans("modal.info.action.ok"),
            testId: "modal-action-ok",
            callback: async () => {
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

export function useHappeningCreateModal(happening: Happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.create.title"),
        description: trans("modal.create.description"),
        editable: true,
    });
}

export function useHappeningVerifyModal(happening: Happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.verify.title"),
        description: trans("modal.verify.description"),
        editable: true,
    });
}

export function useHappeningEditModal(happening: Happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.edit.title"),
        description: trans("modal.edit.description"),
        editable: true,
    });
}

export function useHappeningDeleteModal(happening: Happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.delete.title"),
        description: trans("modal.delete.description"),
        editable: false,
    });
}

export function useHappeningInfoModal(happening: Happening) {
    return useHappeningModal({
        happening,
        title: trans("modal.info.title"),
        description: trans("modal.info.description"),
        editable: false,
    });
}

export function useResourceGroupInfoModal(resourceGroup: { title?: Translatable; description?: Translatable }) {
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
                testId: "modal-action-resource-group-info-ok",
                callback: async () => {
                    modal.close();
                },
            },
        ],
    };
}

export function useResourceInfoModal(resource: {
    title?: string;
    description?: string;
    location?: string;
    resourceGroup?: string;
    location_uri?: string;
    capacity?: number;
}) {
    const modal = useModal();
    const appStore = useAppStore();
    const translate = appStore.translate;

    return {
        view: ResourceInfoModal,
        content: {
            title: trans("modal.resource_info.title", {
                resource_group: translate(appStore.resourceGroup?.term_singular) ?? "",
                resource_title: resource.title ?? "",
            }),
        },
        payload: { resource },
        actions: [
            {
                label: trans("modal.resource_info.action.ok"),
                testId: "modal-action-resource-info-ok",
                callback: async () => {
                    modal.close();
                },
            },
        ],
    };
}

export function useLoginModal(happeningModalCallback?: () => void) {
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
                testId: "modal-action-login",
                callback: callLogin({
                    loginCallback: ({ username, password }) => authStore.login(username, password),
                    happeningModalCallback,
                }),
            },
        ],
    };
}
