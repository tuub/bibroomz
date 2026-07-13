import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";

const echoConstructorMock = vi.hoisted(() => vi.fn());
vi.mock("laravel-echo", () => ({
    default: echoConstructorMock,
}));

const pusherMock = vi.hoisted(() => ({ marker: "pusher-js-default" }));
vi.mock("pusher-js", () => ({
    default: pusherMock,
}));

async function loadEcho() {
    vi.resetModules();
    await import("@/echo");
    return echoConstructorMock.mock.calls.at(-1)?.[0];
}

function setMetaBaseUrl(url: string) {
    const meta = document.createElement("meta");
    meta.setAttribute("name", "api-base-url");
    meta.setAttribute("content", url);
    document.head.appendChild(meta);
}

beforeEach(() => {
    echoConstructorMock.mockReset();
    window.Echo = undefined;
    window.Pusher = undefined;
});

afterEach(() => {
    document.head.innerHTML = "";
    vi.unstubAllEnvs();
});

describe("echo bootstrap", () => {
    test("assigns the pusher-js default export to window.Pusher", async () => {
        setMetaBaseUrl("https://rooms.example.com/");

        await loadEcho();

        expect(window.Pusher).toBe(pusherMock);
    });

    test("assigns the constructed Echo instance to window.Echo", async () => {
        setMetaBaseUrl("https://rooms.example.com/");

        await loadEcho();

        expect(window.Echo).toBe(echoConstructorMock.mock.instances[0]);
    });

    test("builds the websocket config from the api-base-url meta tag", async () => {
        setMetaBaseUrl("https://rooms.example.com/app/");

        const config = await loadEcho();

        expect(config.wsHost).toBe("rooms.example.com");
        expect(config.forceTLS).toBe(true);
        expect(config.authEndpoint).toBe("/app/broadcasting/auth");
    });

    test("falls back to VITE_API_URL when there is no meta tag", async () => {
        vi.stubEnv("VITE_API_URL", "http://rooms.example.com:8080");

        const config = await loadEcho();

        expect(config.wsHost).toBe("rooms.example.com");
        expect(config.wsPort).toBe("8080");
        expect(config.forceTLS).toBe(false);
    });

    test("prefers the meta tag over VITE_API_URL", async () => {
        setMetaBaseUrl("https://meta.example.com/");
        vi.stubEnv("VITE_API_URL", "https://env.example.com/");

        const config = await loadEcho();

        expect(config.wsHost).toBe("meta.example.com");
    });

    test("defaults broadcaster/key/transports when no reverb/pusher env vars are set", async () => {
        setMetaBaseUrl("https://rooms.example.com/");

        const config = await loadEcho();

        expect(config.broadcaster).toBe("reverb");
        expect(config.key).toBe("bibroomz");
        expect(config.enabledTransports).toEqual(["ws", "wss"]);
    });

    test("uses an empty string port when the base url has no explicit port", async () => {
        setMetaBaseUrl("https://rooms.example.com");

        const config = await loadEcho();

        expect(config.wsPort).toBe("");
        expect(config.wssPort).toBe("");
    });

    test("prefers explicit reverb env vars over computed defaults", async () => {
        setMetaBaseUrl("http://rooms.example.com/");
        vi.stubEnv("VITE_REVERB_APP_KEY", "custom-key");
        vi.stubEnv("VITE_REVERB_HOST", "reverb.example.com");
        vi.stubEnv("VITE_REVERB_PORT", "9000");
        vi.stubEnv("VITE_REVERB_SCHEME", "https");

        const config = await loadEcho();

        expect(config.key).toBe("custom-key");
        expect(config.wsHost).toBe("reverb.example.com");
        expect(config.wsPort).toBe("9000");
        expect(config.wssPort).toBe("9000");
        expect(config.forceTLS).toBe(true);
    });

    test("strips exactly one trailing slash from the pathname when building the auth endpoint", async () => {
        setMetaBaseUrl("https://rooms.example.com/app//");

        const config = await loadEcho();

        expect(config.authEndpoint).toBe("/app//broadcasting/auth");
    });
});
