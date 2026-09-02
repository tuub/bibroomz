import { useLogin } from "@/Composables/Login";
import { useAuthStore } from "@/Stores/AuthStore";
import useModal from "@/Stores/Modal";

import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, test, vi } from "vitest";
import type { Mock } from "vitest";

type LoginModalMock = {
    view: { name: string };
    content: { title: string };
    payload: { happeningCallback: (() => void) | undefined };
    actions: { label: string; callback: ReturnType<typeof vi.fn> }[];
};

const loginModalMock = vi.hoisted((): LoginModalMock => ({
    view: { name: "LoginModal" },
    content: { title: "Login" },
    payload: { happeningCallback: undefined },
    actions: [{ label: "Cancel", callback: vi.fn() }],
}));
vi.mock("@/Composables/ModalActions", () => ({
    useLoginModal: vi.fn(() => loginModalMock),
}));

let modal: ReturnType<typeof useModal>;
let authStore: ReturnType<typeof useAuthStore>;
let openSpy: Mock<typeof modal.open>;
let logoutSpy: Mock<typeof authStore.logout>;

beforeEach(() => {
    setActivePinia(createPinia());
    modal = useModal();
    authStore = useAuthStore();
    openSpy = vi.spyOn(modal, "open");
    logoutSpy = vi.spyOn(authStore, "logout");
    vi.clearAllMocks();
});

describe("loginUser", () => {
    test("opens the login modal via the modal store", async () => {
        const { loginUser } = useLogin();

        await loginUser();

        expect(openSpy).toHaveBeenCalledWith(
            loginModalMock.view,
            loginModalMock.content,
            loginModalMock.payload,
            loginModalMock.actions,
        );
    });
});

describe("logoutUser", () => {
    test("delegates to authStore.logout and returns its result", async () => {
        logoutSpy.mockResolvedValue("logged-out" as never);
        const { logoutUser } = useLogin();

        const result = await logoutUser();

        expect(logoutSpy).toHaveBeenCalled();
        expect(result).toBe("logged-out");
    });

    test("catches and logs an error from authStore.logout", async () => {
        const error = new Error("network error");
        logoutSpy.mockRejectedValue(error);
        const consoleSpy = vi.spyOn(console, "log").mockImplementation(() => {});
        const { logoutUser } = useLogin();

        const result = await logoutUser();

        expect(consoleSpy).toHaveBeenCalledWith(error);
        expect(result).toBeUndefined();

        consoleSpy.mockRestore();
    });
});
