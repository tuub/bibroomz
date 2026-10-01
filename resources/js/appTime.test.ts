import { appNow, appWallClock } from "@/appTime";

import { afterEach, beforeEach, describe, expect, test, vi } from "vitest";

function freezeAt(instant: string) {
    vi.setSystemTime(new Date(instant));
}

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
});

describe("appNow", () => {
    test("returns the app timezone's wall clock, labelled UTC", () => {
        freezeAt("2026-06-10T08:00:00Z");

        expect(appNow("Europe/Berlin").format("YYYY-MM-DD HH:mm")).toBe("2026-06-10 10:00");
    });

    test("follows the zone's offset out of daylight saving time", () => {
        freezeAt("2026-01-15T08:00:00Z");

        expect(appNow("Europe/Berlin").format("YYYY-MM-DD HH:mm")).toBe("2026-01-15 09:00");
    });

    test("shifts a zone behind UTC backwards", () => {
        freezeAt("2026-06-10T08:00:00Z");

        expect(appNow("America/New_York").format("YYYY-MM-DD HH:mm")).toBe("2026-06-10 04:00");
    });

    test("leaves the clock alone for UTC itself", () => {
        freezeAt("2026-06-10T08:00:00Z");

        expect(appNow("UTC").format("YYYY-MM-DD HH:mm")).toBe("2026-06-10 08:00");
    });

    test("stays in UTC mode, so it compares against zone-less API datetimes", () => {
        freezeAt("2026-06-10T08:00:00Z");

        const now = appNow("Europe/Berlin");

        expect(now.isUTC()).toBe(true);
        expect(now.toISOString()).toBe("2026-06-10T10:00:00.000Z");
    });

    test("falls back to UTC for a zone name the browser does not know", () => {
        freezeAt("2026-06-10T08:00:00Z");

        expect(appNow("Nonsense/Zone").format("YYYY-MM-DD HH:mm")).toBe("2026-06-10 08:00");
    });
});

describe("appWallClock", () => {
    test("formats a value the API can read back unchanged", () => {
        expect(appWallClock("2026-06-10T13:00:00Z")).toBe("2026-06-10 13:00:00");
    });

    test("keeps the wall clock of a UTC-mode dayjs value", () => {
        freezeAt("2026-06-10T08:00:00Z");

        expect(appWallClock(appNow("Europe/Berlin"))).toBe("2026-06-10 10:00:00");
    });

    test("reads a Date as the wall clock it stands for", () => {
        expect(appWallClock(new Date("2026-06-10T13:00:00Z"))).toBe("2026-06-10 13:00:00");
    });

    test("passes an absent datetime through rather than inventing one", () => {
        expect(appWallClock("")).toBe("");
        expect(appWallClock(null)).toBe("");
        expect(appWallClock(undefined)).toBe("");
    });
});
