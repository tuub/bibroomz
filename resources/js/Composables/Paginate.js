import { router } from "@inertiajs/core";

export function usePaginate(paginator) {
    const isValidUrl = (url) => {
        try {
            return Boolean(new URL(url));
        } catch {
            return false;
        }
    };

    const getPageUrlFromPaginator = (page) => {
        const link = paginator.links[page];

        if (link.active || !link.url) {
            return;
        }

        return link.url;
    };

    const jumpToPage = (page) => {
        const url = parseInt(page) ? getPageUrlFromPaginator(page) : page;

        if (!isValidUrl(url)) {
            return;
        }

        router.visit(url);
    };

    const updatePaginator = (newPaginator) => {
        paginator = newPaginator;
    };

    return {
        jumpToPage,
        updatePaginator,
    };
}
