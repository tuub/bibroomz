import type { ChartData, Chart as ChartJS, ChartOptions } from "chart.js";
import { trans } from "laravel-vue-i18n";
import type { ComputedRef } from "vue";
import { computed, ref, shallowRef } from "vue";

export interface PieEntry {
    label: string;
    count: number;
}

export interface PieLegendItem extends PieEntry {
    color: string;
    hidden: boolean;
}

export function slicePalette(count: number): string[] {
    return Array.from({ length: count }, (_, index) => `hsl(${(index * 137.508) % 360}, 65%, 55%)`);
}

function buildChartData(entries: PieEntry[], borderColor: string): ChartData<"pie"> {
    return {
        labels: entries.map((entry) => entry.label),
        datasets: [
            {
                label: trans("admin.statistics.index.bookings"),
                backgroundColor: slicePalette(entries.length),
                borderColor,
                borderWidth: 2,
                data: entries.map((entry) => entry.count),
            },
        ],
    };
}

// The built-in Chart.js legend is a fixed-size canvas draw: once it has more
// entries than fit the chart's height, the overflowing rows are silently
// dropped instead of scrolling. We render our own scrollable HTML legend
// instead and keep click-to-toggle parity with Chart.js's default behavior.
export const pieChartOptions: ChartOptions<"pie"> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};

export function usePieChart(entries: ComputedRef<PieEntry[]>, borderColor: ComputedRef<string>) {
    const chart = shallowRef<ChartJS<"pie"> | null>(null);
    const hiddenIndices = ref<Set<number>>(new Set());

    const chartData = computed(() => buildChartData(entries.value, borderColor.value));

    const legendItems = computed<PieLegendItem[]>(() => {
        const colors = slicePalette(entries.value.length);

        return entries.value.map((entry, index) => ({
            ...entry,
            color: colors[index]!,
            hidden: hiddenIndices.value.has(index),
        }));
    });

    function onLoaded(instance: ChartJS<"pie">) {
        chart.value = instance;
        hiddenIndices.value = new Set();
    }

    function toggle(index: number) {
        if (!chart.value) return;

        chart.value.toggleDataVisibility(index);
        chart.value.update();

        const next = new Set(hiddenIndices.value);
        if (next.has(index)) {
            next.delete(index);
        } else {
            next.add(index);
        }
        hiddenIndices.value = next;
    }

    return { chartData, legendItems, onLoaded, toggle };
}

export function usePieChartSection<T extends { title: Record<string, string>; count: number }>(
    items: ComputedRef<T[]>,
    translate: (title: Record<string, string>) => string,
    borderColor: ComputedRef<string>,
) {
    const entries = computed<PieEntry[]>(() =>
        items.value.map((item) => ({ label: translate(item.title), count: item.count })),
    );

    const singleEntryLabel = computed(() => (items.value[0] ? translate(items.value[0].title) : ""));
    const singleEntryCount = computed(() => (items.value[0] ? String(items.value[0].count) : ""));

    return { ...usePieChart(entries, borderColor), singleEntryLabel, singleEntryCount };
}
