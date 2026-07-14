import type { RequestPayload } from "@inertiajs/core";
import { router } from "@inertiajs/vue3";

// Inertia's RequestPayload type only models object/FormData bodies; posting the
// reordered rows as a raw array is valid over the wire but needs this cast to satisfy it.
export function postReorderedRows<T extends { order?: number | string }>(url: string, items: T[]) {
    for (const [index, item] of items.entries()) {
        item.order = index + 1;
    }

    router.post(url, items as unknown as RequestPayload);
}
