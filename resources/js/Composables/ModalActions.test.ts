import {
    useHappeningCreateModal,
    useHappeningDeleteModal,
    useHappeningEditModal,
    useHappeningInfoModal,
    useHappeningModal,
    useHappeningVerifyModal,
    useLoginModal,
    useResourceGroupInfoModal,
    useResourceInfoModal,
} from "@/Composables/ModalActions";
import type { ResourceGroup } from "@/Stores/AppStore";
import type { ApiError } from "@/Types/Api";

import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/Components/Modals/HappeningModal.vue", () => ({ default: { name: "HappeningModal" } }));
vi.mock("@/Components/Modals/LoginModal.vue", () => ({ default: { name: "LoginModal" } }));
vi.mock("@/Components/Modals/ResourceGroupInfoModal.vue", () => ({ default: { name: "ResourceGroupInfoModal" } }));
vi.mock("@/Components/Modals/ResourceInfoModal.vue", () => ({ default: { name: "ResourceInfoModal" } }));

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string, params?: Record<string, string>) => (params ? `${key}:${JSON.stringify(params)}` : key),
}));

const modalMock = vi.hoisted(() => ({ close: vi.fn() }));
vi.mock("@/Stores/Modal", () => ({
    default: () => modalMock,
}));

type HappeningStoreMock = {
    error: ApiError;
    verifyHappening: ReturnType<typeof vi.fn>;
    editHappening: ReturnType<typeof vi.fn>;
    deleteHappening: ReturnType<typeof vi.fn>;
    addHappening: ReturnType<typeof vi.fn>;
};

const happeningStoreMock = vi.hoisted(
    (): HappeningStoreMock => ({
        error: null,
        verifyHappening: vi.fn(),
        editHappening: vi.fn(),
        deleteHappening: vi.fn(),
        addHappening: vi.fn(),
    }),
);
vi.mock("@/Stores/HappeningStore", () => ({
    useHappeningStore: () => happeningStoreMock,
}));

type AuthStoreMock = {
    error: ApiError;
    isProcessingLogin: boolean;
    login: ReturnType<typeof vi.fn>;
};

const authStoreMock = vi.hoisted(
    (): AuthStoreMock => ({
        error: null,
        isProcessingLogin: false,
        login: vi.fn(),
    }),
);
vi.mock("@/Stores/AuthStore", () => ({
    useAuthStore: () => authStoreMock,
}));

type AppStoreMock = {
    translate: (value?: Record<string, string>) => string;
    resourceGroup: ResourceGroup | null;
};

const appStoreMock = vi.hoisted(
    (): AppStoreMock => ({
        translate: vi.fn((value?: Record<string, string>) => value?.en ?? ""),
        resourceGroup: null,
    }),
);
vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

beforeEach(() => {
    vi.clearAllMocks();
    happeningStoreMock.error = null;
    authStoreMock.error = null;
    authStoreMock.isProcessingLogin = false;
    appStoreMock.resourceGroup = null;
});

describe("useHappeningModal", () => {
    test("pushes a verify action when can.verify and editable", async () => {
        happeningStoreMock.verifyHappening.mockResolvedValue({});
        const happening = { id: 1, can: { verify: true } };

        const { actions } = useHappeningModal({ happening, editable: true });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-verify"]);

        await actions[0]!.callback(happening);

        expect(happeningStoreMock.verifyHappening).toHaveBeenCalledWith(happening);
        expect(modalMock.close).toHaveBeenCalled();
    });

    test("pushes an edit action when can.edit and editable", async () => {
        happeningStoreMock.editHappening.mockResolvedValue({});
        const happening = { id: 1, can: { edit: true } };

        const { actions } = useHappeningModal({ happening, editable: true });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-update"]);

        await actions[0]!.callback(happening);

        expect(happeningStoreMock.editHappening).toHaveBeenCalledWith(happening);
    });

    test("does not push verify/edit actions when not editable", () => {
        const happening = { id: 1, can: { verify: true, edit: true } };

        const { actions } = useHappeningModal({ happening, editable: false });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-ok"]);
    });

    test("pushes a delete action when can.delete, regardless of editable", async () => {
        happeningStoreMock.deleteHappening.mockResolvedValue({});
        const happening = { id: 7, can: { delete: true } };

        const { actions } = useHappeningModal({ happening, editable: false });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-delete"]);

        await actions[0]!.callback(happening);

        expect(happeningStoreMock.deleteHappening).toHaveBeenCalledWith(7);
    });

    test("falls back to ok when delete is allowed but the happening has no id", () => {
        const happening = { can: { delete: true } };

        const { actions } = useHappeningModal({ happening, editable: false });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-ok"]);
        expect(happeningStoreMock.deleteHappening).not.toHaveBeenCalled();
    });

    test("pushes a create action when there is no can and editable is true", async () => {
        happeningStoreMock.addHappening.mockResolvedValue({});
        const happening = { id: 1 };

        const { actions } = useHappeningModal({ happening, editable: true });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-create"]);

        await actions[0]!.callback(happening);

        expect(happeningStoreMock.addHappening).toHaveBeenCalledWith(happening);
    });

    test("falls back to a single ok action that just closes the modal", async () => {
        const happening = { id: 1 };

        const { actions } = useHappeningModal({ happening, editable: false });

        expect(actions.map((a) => a.testId)).toEqual(["modal-action-ok"]);

        await actions[0]!.callback(happening);

        expect(modalMock.close).toHaveBeenCalled();
        expect(happeningStoreMock.addHappening).not.toHaveBeenCalled();
    });

    test("records the error response and does not close the modal on failure", async () => {
        happeningStoreMock.verifyHappening.mockRejectedValue({ response: { status: 422 } });
        const happening = { id: 1, can: { verify: true } };

        const { actions } = useHappeningModal({ happening, editable: true });
        await actions[0]!.callback(happening);

        expect(happeningStoreMock.error).toEqual({ status: 422 });
        expect(modalMock.close).not.toHaveBeenCalled();
    });

    test("builds content and payload from the given title/description/editable", () => {
        const happening = { id: 1, resource: { id: 5 } };

        const { content, payload } = useHappeningModal({
            happening,
            title: "Create",
            description: "New happening",
            editable: true,
        });

        expect(content).toEqual({ title: "Create", description: "New happening" });
        expect(payload).toEqual({ id: 1, resource: { id: 5 }, editable: true });
    });
});

describe("happening modal wrappers", () => {
    test("useHappeningCreateModal is editable with the create title", () => {
        const { content, actions } = useHappeningCreateModal({ id: 1 });

        expect(content.title).toBe("modal.create.title");
        expect(actions.map((a) => a.testId)).toEqual(["modal-action-create"]);
    });

    test("useHappeningVerifyModal is editable with the verify title", () => {
        const { content, actions } = useHappeningVerifyModal({ id: 1, can: { verify: true } });

        expect(content.title).toBe("modal.verify.title");
        expect(actions.map((a) => a.testId)).toEqual(["modal-action-verify"]);
    });

    test("useHappeningEditModal is editable with the edit title", () => {
        const { content, actions } = useHappeningEditModal({ id: 1, can: { edit: true } });

        expect(content.title).toBe("modal.edit.title");
        expect(actions.map((a) => a.testId)).toEqual(["modal-action-update"]);
    });

    test("useHappeningDeleteModal is not editable but still allows delete", () => {
        const { content, actions } = useHappeningDeleteModal({ id: 1, can: { delete: true } });

        expect(content.title).toBe("modal.delete.title");
        expect(actions.map((a) => a.testId)).toEqual(["modal-action-delete"]);
    });

    test("useHappeningInfoModal is not editable and falls back to ok", () => {
        const { content, actions } = useHappeningInfoModal({ id: 1 });

        expect(content.title).toBe("modal.info.title");
        expect(actions.map((a) => a.testId)).toEqual(["modal-action-ok"]);
    });
});

describe("useResourceGroupInfoModal", () => {
    test("translates the title/description and closes on ok", async () => {
        const resourceGroup = { title: { en: "Study Rooms" }, description: { en: "Rooms for studying" } };

        const { content, payload, actions } = useResourceGroupInfoModal(resourceGroup);

        expect(content).toEqual({ title: "Study Rooms", description: "Rooms for studying" });
        expect(payload).toEqual({ resourceGroup });

        await actions[0]!.callback();

        expect(modalMock.close).toHaveBeenCalled();
    });
});

describe("useResourceInfoModal", () => {
    test("builds a translated title from the resource group term and resource title", () => {
        appStoreMock.resourceGroup = { term_singular: { en: "Room" } };

        const { content, payload } = useResourceInfoModal({ title: "Room 101" });

        expect(content.title).toBe('modal.resource_info.title:{"resource_group":"Room","resource_title":"Room 101"}');
        expect(payload).toEqual({ resource: { title: "Room 101" } });
    });

    test("falls back to empty strings without a resource group or title", () => {
        const { content } = useResourceInfoModal({});

        expect(content.title).toBe('modal.resource_info.title:{"resource_group":"","resource_title":""}');
    });
});

describe("useLoginModal", () => {
    test("does nothing while a login is already processing", async () => {
        authStoreMock.isProcessingLogin = true;
        const { actions } = useLoginModal();

        await actions[0]!.callback({ username: "alice", password: "secret" });

        expect(authStoreMock.login).not.toHaveBeenCalled();
    });

    test("logs in and closes the modal when there is no happening callback", async () => {
        authStoreMock.login.mockResolvedValue({});
        const { actions } = useLoginModal();

        await actions[0]!.callback({ username: "alice", password: "secret" });

        expect(authStoreMock.login).toHaveBeenCalledWith("alice", "secret");
        expect(modalMock.close).toHaveBeenCalled();
        expect(authStoreMock.isProcessingLogin).toBe(false);
    });

    test("invokes the happening callback instead of closing when one is given", async () => {
        authStoreMock.login.mockResolvedValue({});
        const happeningModalCallback = vi.fn();
        const { actions } = useLoginModal(happeningModalCallback);

        await actions[0]!.callback({ username: "alice", password: "secret" });

        expect(happeningModalCallback).toHaveBeenCalled();
        expect(modalMock.close).not.toHaveBeenCalled();
    });

    test("records the error response and resets isProcessingLogin on failure", async () => {
        authStoreMock.login.mockRejectedValue({ response: { status: 401 } });
        const { actions } = useLoginModal();

        await actions[0]!.callback({ username: "alice", password: "wrong" });

        expect(authStoreMock.error).toEqual({ status: 401 });
        expect(authStoreMock.isProcessingLogin).toBe(false);
        expect(modalMock.close).not.toHaveBeenCalled();
    });
});
