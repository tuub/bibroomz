import { afterEach, describe, expect, test, vi } from "vitest";

async function loadBaseUrl() {
    vi.resetModules();
    return import("@/baseUrl");
}

afterEach(() => {
    document.head.innerHTML = "";
    vi.unstubAllEnvs();
});

describe("baseUrl", () => {
    test("uses the api-base-url meta tag when present", async () => {
        const meta = document.createElement("meta");
        meta.setAttribute("name", "api-base-url");
        meta.setAttribute("content", "https://rooms.example.com/app/");
        document.head.appendChild(meta);

        const { baseUrl } = await loadBaseUrl();

        expect(baseUrl).toBe("https://rooms.example.com/app");
    });

    test("falls back to VITE_API_URL when no meta tag is present", async () => {
        vi.stubEnv("VITE_API_URL", "https://fallback.example.com");

        const { baseUrl } = await loadBaseUrl();

        expect(baseUrl).toBe("https://fallback.example.com");
    });

    test("falls back to an empty string when neither is set", async () => {
        vi.stubEnv("VITE_API_URL", "");

        const { baseUrl } = await loadBaseUrl();

        expect(baseUrl).toBe("");
    });

    test("prefers the meta tag over the env var", async () => {
        const meta = document.createElement("meta");
        meta.setAttribute("name", "api-base-url");
        meta.setAttribute("content", "https://meta.example.com");
        document.head.appendChild(meta);
        vi.stubEnv("VITE_API_URL", "https://env.example.com");

        const { baseUrl } = await loadBaseUrl();

        expect(baseUrl).toBe("https://meta.example.com");
    });

    test("only trims a single trailing slash", async () => {
        const meta = document.createElement("meta");
        meta.setAttribute("name", "api-base-url");
        meta.setAttribute("content", "https://rooms.example.com//");
        document.head.appendChild(meta);

        const { baseUrl } = await loadBaseUrl();

        expect(baseUrl).toBe("https://rooms.example.com/");
    });
});

describe("withBaseUrl", () => {
    test("joins a path without a leading slash", async () => {
        vi.stubEnv("VITE_API_URL", "https://rooms.example.com");
        const { withBaseUrl } = await loadBaseUrl();

        expect(withBaseUrl("happening/add")).toBe("https://rooms.example.com/happening/add");
    });

    test("does not double up the leading slash", async () => {
        vi.stubEnv("VITE_API_URL", "https://rooms.example.com");
        const { withBaseUrl } = await loadBaseUrl();

        expect(withBaseUrl("/happening/add")).toBe("https://rooms.example.com/happening/add");
    });

    test("defaults to the bare base url when no path is given", async () => {
        vi.stubEnv("VITE_API_URL", "https://rooms.example.com");
        const { withBaseUrl } = await loadBaseUrl();

        expect(withBaseUrl()).toBe("https://rooms.example.com/");
    });
});
