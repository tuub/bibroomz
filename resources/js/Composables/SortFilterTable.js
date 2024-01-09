import { useAppStore } from "@/Stores/AppStore";

import { reactive, ref, unref, watch, watchEffect } from "vue";

export function useSortFilterTable({
    data,
    initialSortField = "",
    initialSortDirection = "asc",
    nonNumericFields = [],
}) {
    // ------------------------------------------------
    // Variables
    // ------------------------------------------------
    const { locale } = useAppStore();

    const sortField = ref(initialSortField);
    const sortDirection = ref(initialSortDirection);

    const filters = reactive({});
    const filteredData = ref(unref(data));

    const paginator = reactive({
        currentPage: 1,
        perPage: 10,
        lastPage: 1,
        nextPage: 1,
        prevPage: 1,
    });

    // ------------------------------------------------
    // Methods
    // ------------------------------------------------
    paginator.jumpToPage = (page) => {
        paginator.currentPage = page > 0 ? parseInt(page) : 1;
    };

    const toggleFilter = (field) => {
        if (filters[field]) {
            delete filters[field];
        }
    };

    const isSortNumeric = (field) => {
        return !nonNumericFields.includes(field);
    };

    const sortFunction = (a, b) => {
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
            return modifier * (a[sortField.value] - b[sortField.value]);
        }

        return modifier * new Intl.Collator(locale).compare(a[sortField.value], b[sortField.value]);
    };

    const filterFunction = (obj) => {
        return Object.keys(filters).every((key) => {
            return obj[key]?.toString().toLowerCase().includes(filters[key].toLowerCase());
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

        paginator.data = filteredData.value.slice(
            paginator.perPage * (paginator.currentPage - 1),
            paginator.perPage * paginator.currentPage,
        );
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
