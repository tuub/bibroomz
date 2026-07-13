import { useAppStore } from "@/Stores/AppStore";

import type { Ref } from "vue";
import { reactive, ref, unref, watch, watchEffect } from "vue";

type Paginator<T> = {
    currentPage: number;
    perPage: number;
    lastPage: number;
    nextPage: number;
    prevPage: number;
    jumpToPage?: (page: number | string) => void;
    data?: T[];
};

export function useSortFilterTable<T extends Record<string, unknown>>({
    data,
    initialSortField = "",
    initialSortDirection = "asc",
    nonNumericFields = [],
}: {
    data: Ref<T[]> | T[];
    initialSortField?: string;
    initialSortDirection?: "asc" | "desc";
    nonNumericFields?: string[];
}) {
    // ------------------------------------------------
    // Variables
    // ------------------------------------------------
    const { locale } = useAppStore();

    const sortField = ref(initialSortField);
    const sortDirection = ref(initialSortDirection);

    const filters = reactive<Record<string, string>>({});
    const filteredData = ref(unref(data));

    const paginator = reactive<Paginator<T>>({
        currentPage: 1,
        perPage: 10,
        lastPage: 1,
        nextPage: 1,
        prevPage: 1,
    });

    // ------------------------------------------------
    // Methods
    // ------------------------------------------------
    paginator.jumpToPage = (page: number | string) => {
        paginator.currentPage = Number(page) > 0 ? parseInt(page as string) : 1;
    };

    const toggleFilter = (field: string) => {
        if (filters[field]) {
            delete filters[field];
        }
    };

    const isSortNumeric = (field: string) => {
        return !nonNumericFields.includes(field);
    };

    const sortFunction = (a: Record<string, unknown>, b: Record<string, unknown>) => {
        // equal items sort equally
        if (a[sortField.value] === b[sortField.value]) {
            return 0;
        }

        // nulls sort after anything else
        if (a[sortField.value] === null) {
            return 1;
        }
        if (b[sortField.value] === null) {
            return -1;
        }

        const modifier = sortDirection.value === "asc" ? 1 : -1;

        if (isSortNumeric(sortField.value)) {
            return modifier * ((a[sortField.value] as number) - (b[sortField.value] as number));
        }

        return modifier * new Intl.Collator(locale).compare(a[sortField.value] as string, b[sortField.value] as string);
    };

    const filterFunction = (obj: Record<string, unknown>) => {
        return Object.keys(filters).every((key) => {
            return (obj[key] as { toString(): string } | undefined)
                ?.toString()
                .toLowerCase()
                .includes(filters[key].toLowerCase());
        });
    };

    // ------------------------------------------------
    // Hooks
    // ------------------------------------------------
    watchEffect(() => {
        filteredData.value = filteredData.value.sort(sortFunction);
    });

    watch([data, filters], () => {
        filteredData.value = unref(data).filter(filterFunction);
    });

    watchEffect(() => {
        paginator.lastPage = paginator.perPage > 0 ? Math.ceil(filteredData.value.length / paginator.perPage) : 1;
        paginator.currentPage = paginator.currentPage > paginator.lastPage ? 1 : paginator.currentPage;

        paginator.nextPage =
            paginator.currentPage < paginator.lastPage ? paginator.currentPage + 1 : paginator.currentPage;
        paginator.prevPage = paginator.currentPage > 1 ? paginator.currentPage - 1 : paginator.currentPage;

        paginator.data =
            paginator.perPage > 0
                ? filteredData.value.slice(
                      paginator.perPage * (paginator.currentPage - 1),
                      paginator.perPage * paginator.currentPage,
                  )
                : filteredData.value;
    });

    // ------------------------------------------------
    // Return
    // ------------------------------------------------
    return {
        filters,
        paginator,
        sortField,
        sortDirection,
        toggleFilter,
    };
}
