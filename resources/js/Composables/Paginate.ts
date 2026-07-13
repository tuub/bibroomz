import { router } from "@inertiajs/core";

type PaginatorLink = {
    url?: string;
    active: boolean;
};

type Paginator = {
    links: Record<string | number, PaginatorLink>;
};

export function usePaginate(paginator: Paginator) {
    const isValidUrl = (url?: string) => {
        try {
            return Boolean(new URL(url ?? ""));
        } catch {
            return false;
        }
    };

    const getPageUrlFromPaginator = (page: number) => {
        const link = paginator.links[page];

        if (!link || link.active || !link.url) {
            return undefined;
        }

        return link.url;
    };

    const jumpToPage = (page: number | string) => {
        const url = parseInt(page as string) ? getPageUrlFromPaginator(page as number) : (page as string);

        if (!isValidUrl(url)) {
            return;
        }

        if (!url) {
            return;
        }

        router.visit(url);
    };

    const updatePaginator = (newPaginator: Paginator) => {
        paginator = newPaginator;
    };

    return {
        jumpToPage,
        updatePaginator,
    };
}
