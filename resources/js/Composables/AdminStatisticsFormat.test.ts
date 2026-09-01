import {
    comparisonDeltaClass,
    formatDateRange,
    formatNumber,
    formatPercent,
    formatSignedPercent,
} from "@/Composables/AdminStatisticsFormat";

import { describe, expect, test } from "vitest";

describe("formatNumber", () => {
    test("formats using the locale thousands separator", () => {
        expect(formatNumber(1234)).toBe(new Intl.NumberFormat().format(1234));
    });
});

describe("formatPercent", () => {
    test("renders integer values without decimals", () => {
        expect(formatPercent(20)).toBe("20%");
    });

    test("renders fractional values with one decimal", () => {
        expect(formatPercent(33.33)).toBe("33.3%");
    });

    test("renders zero without decimals", () => {
        expect(formatPercent(0)).toBe("0%");
    });
});

describe("formatSignedPercent", () => {
    test("prefixes positive values with a plus sign", () => {
        expect(formatSignedPercent(66.7)).toBe("+66.7%");
    });

    test("keeps the minus sign for negative values", () => {
        expect(formatSignedPercent(-12)).toBe("-12%");
    });

    test("does not prefix zero", () => {
        expect(formatSignedPercent(0)).toBe("0%");
    });
});

describe("formatDateRange", () => {
    test("joins the formatted from and to dates", () => {
        const formatDate = (date: string) => `formatted(${date})`;

        expect(formatDateRange("2026-01-01", "2026-01-31", formatDate)).toBe(
            "formatted(2026-01-01) - formatted(2026-01-31)",
        );
    });
});

describe("comparisonDeltaClass", () => {
    test("returns the positive color class for an increase", () => {
        expect(comparisonDeltaClass(10)).toBe("text-emerald-600 dark:text-emerald-400");
    });

    test("returns the negative color class for a decrease", () => {
        expect(comparisonDeltaClass(-10)).toBe("text-red-600 dark:text-red-400");
    });

    test("returns the muted class when unchanged", () => {
        expect(comparisonDeltaClass(0)).toBe("text-app-muted");
    });
});
