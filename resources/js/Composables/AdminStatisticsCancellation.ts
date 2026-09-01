import { formatNumber, formatPercent } from "@/Composables/AdminStatisticsFormat";

import type { ComputedRef } from "vue";
import { computed } from "vue";

type StatisticWithCancellation = {
    id: number | string;
    title: Record<string, string>;
    active: number;
    cancelled: number;
    cancellationRate: number;
};

export type CancellationStatusEntry = {
    id: number | string;
    label: string;
    active: string;
    cancelled: string;
    rate: string;
};

export function useCancellationStatus(
    entries: ComputedRef<StatisticWithCancellation[]>,
    translate: (title: Record<string, string>) => string,
): ComputedRef<CancellationStatusEntry[]> {
    return computed(() =>
        entries.value.map((entry) => ({
            id: entry.id,
            label: translate(entry.title),
            active: formatNumber(entry.active),
            cancelled: formatNumber(entry.cancelled),
            rate: formatPercent(entry.cancellationRate),
        })),
    );
}
