import { useCancellationStatus } from "@/Composables/AdminStatisticsCancellation";

import { describe, expect, test } from "vitest";
import { computed, ref } from "vue";

const translate = (title: Record<string, string>) => title.en ?? "";

describe("useCancellationStatus", () => {
    test("maps entries to formatted labels, counts and rates", () => {
        const entries = useCancellationStatus(
            computed(() => [
                { id: 1, title: { en: "Institution A" }, active: 4, cancelled: 1, cancellationRate: 20 },
                { id: "b", title: { en: "Institution B" }, active: 1234, cancelled: 0, cancellationRate: 0 },
            ]),
            translate,
        );

        expect(entries.value).toEqual([
            { id: 1, label: "Institution A", active: "4", cancelled: "1", rate: "20%" },
            {
                id: "b",
                label: "Institution B",
                active: new Intl.NumberFormat().format(1234),
                cancelled: "0",
                rate: "0%",
            },
        ]);
    });

    test("recomputes when the source entries change", () => {
        const source = ref([{ id: 1, title: { en: "Institution A" }, active: 4, cancelled: 1, cancellationRate: 20 }]);
        const entries = useCancellationStatus(
            computed(() => source.value),
            translate,
        );

        expect(entries.value[0]!.rate).toBe("20%");

        source.value = [{ id: 1, title: { en: "Institution A" }, active: 10, cancelled: 5, cancellationRate: 33.3 }];

        expect(entries.value[0]!.rate).toBe("33.3%");
    });

    test("returns an empty list for no entries", () => {
        const entries = useCancellationStatus(
            computed(() => []),
            translate,
        );

        expect(entries.value).toEqual([]);
    });
});
