export function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value);
}

export function formatPercent(value: number): string {
    return `${Number.isInteger(value) ? value.toFixed(0) : value.toFixed(1)}%`;
}

export function formatSignedPercent(value: number): string {
    return `${value > 0 ? "+" : ""}${formatPercent(value)}`;
}

export function formatDateRange(from: string, to: string, formatDate: (date: string) => string): string {
    return `${formatDate(from)} - ${formatDate(to)}`;
}

export function comparisonDeltaClass(value: number): string {
    if (value > 0) {
        return "text-emerald-600 dark:text-emerald-400";
    }

    if (value < 0) {
        return "text-red-600 dark:text-red-400";
    }

    return "text-app-muted";
}
