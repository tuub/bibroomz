import { useLogin } from "@/Composables/Login";
import type { ModalAction } from "@/Stores/Modal";

import { beforeEach, describe, expect, test, vi } from "vitest";

const authStoreMock = vi.hoisted(() => ({ logout: vi.fn() }));
vi.mock("@/Stores/AuthStore", () => ({
    useAuthStore: () => authStoreMock,
}));

const modalMock = vi.hoisted(() => ({ open: vi.fn() }));
vi.mock("@/Stores/Modal", () => ({
    default: () => modalMock,
}));

type LoginModalMock = {
    view: { name: string };
    content: { title: string };
    payload: { happeningCallback: (() => void) | undefined };
    actions: ModalAction[];
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

beforeEach(() => {
    vi.clearAllMocks();
});

describe("loginUser", () => {
    test("opens the login modal via the modal store", async () => {
        const { loginUser } = useLogin();

        await loginUser();

        expect(modalMock.open).toHaveBeenCalledWith(
            loginModalMock.view,
            loginModalMock.content,
            loginModalMock.payload,
            loginModalMock.actions,
        );
    });
});

describe("logoutUser", () => {
    test("delegates to authStore.logout and returns its result", async () => {
        authStoreMock.logout.mockResolvedValue("logged-out");
        const { logoutUser } = useLogin();

        const result = await logoutUser();

        expect(authStoreMock.logout).toHaveBeenCalled();
        expect(result).toBe("logged-out");
    });

    test("catches and logs an error from authStore.logout", async () => {
        const error = new Error("network error");
        authStoreMock.logout.mockRejectedValue(error);
        const consoleSpy = vi.spyOn(console, "log").mockImplementation(() => {});
        const { logoutUser } = useLogin();

        const result = await logoutUser();

        expect(consoleSpy).toHaveBeenCalledWith(error);
        expect(result).toBeUndefined();

        consoleSpy.mockRestore();
    });
});
