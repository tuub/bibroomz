import { beforeEach, describe, expect, test, vi } from "vitest";

vi.mock("@/echo", () => ({}));

const axiosMock = vi.hoisted(() => ({
    defaults: {
        headers: { common: {} },
        withCredentials: false,
    },
}));
vi.mock("axios", () => ({
    default: axiosMock,
}));

async function loadBootstrap() {
    vi.resetModules();
    await import("@/bootstrap");
}

beforeEach(() => {
    window.axios = undefined as unknown as typeof window.axios;
    axiosMock.defaults.headers.common = {};
    axiosMock.defaults.withCredentials = false;
});

describe("bootstrap", () => {
    test("assigns the axios default export to window.axios", async () => {
        await loadBootstrap();

        expect(window.axios).toBe(axiosMock);
    });

    test("sets the X-Requested-With header to XMLHttpRequest", async () => {
        await loadBootstrap();

        expect(window.axios.defaults.headers.common["X-Requested-With"]).toBe("XMLHttpRequest");
    });

    test("enables withCredentials", async () => {
        await loadBootstrap();

        expect(window.axios.defaults.withCredentials).toBe(true);
    });
});
