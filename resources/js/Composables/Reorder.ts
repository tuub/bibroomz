import { router } from "@inertiajs/vue3";

export function postReorderedRows<T extends { order?: number | string }>(url: string, items: T[]) {
    for (const [index, item] of items.entries()) {
        item.order = index + 1;
    }

    router.post(url, { rows: items }, { preserveScroll: true });
}
