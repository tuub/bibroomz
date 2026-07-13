import { usePaginate } from "@/Composables/Paginate";

import { beforeEach, describe, expect, test, vi } from "vitest";

const routerMock = vi.hoisted(() => ({ visit: vi.fn() }));
vi.mock("@inertiajs/core", () => ({
    router: routerMock,
}));

beforeEach(() => {
    vi.clearAllMocks();
});

describe("jumpToPage", () => {
    test("visits the link url for a numeric page", () => {
        const { jumpToPage } = usePaginate({
            links: { 2: { url: "https://rooms.example.com/?page=2", active: false } },
        });

        jumpToPage(2);

        expect(routerMock.visit).toHaveBeenCalledWith("https://rooms.example.com/?page=2");
    });

    test("does not visit when the page is already active", () => {
        const { jumpToPage } = usePaginate({
            links: { 2: { url: "https://rooms.example.com/?page=2", active: true } },
        });

        jumpToPage(2);

        expect(routerMock.visit).not.toHaveBeenCalled();
    });

    test("does not visit when the link has no url", () => {
        const { jumpToPage } = usePaginate({
            links: { 2: { active: false, url: undefined } },
        });

        jumpToPage(2);

        expect(routerMock.visit).not.toHaveBeenCalled();
    });

    test("visits a valid url passed directly as the page", () => {
        const { jumpToPage } = usePaginate({ links: {} });

        jumpToPage("https://rooms.example.com/?page=3");

        expect(routerMock.visit).toHaveBeenCalledWith("https://rooms.example.com/?page=3");
    });

    test("does not visit an invalid url string", () => {
        const { jumpToPage } = usePaginate({ links: {} });

        jumpToPage("next");

        expect(routerMock.visit).not.toHaveBeenCalled();
    });
});

describe("updatePaginator", () => {
    test("makes jumpToPage resolve pages against the newly assigned paginator", () => {
        const { jumpToPage, updatePaginator } = usePaginate({
            links: { 2: { url: "https://rooms.example.com/?page=2", active: false } },
        });

        updatePaginator({
            links: { 2: { url: "https://rooms.example.com/?page=2&updated=true", active: false } },
        });

        jumpToPage(2);

        expect(routerMock.visit).toHaveBeenCalledWith("https://rooms.example.com/?page=2&updated=true");
    });
});
