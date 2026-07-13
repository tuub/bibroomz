import { useAdminBreadcrumbs } from "@/Composables/AdminBreadcrumbs";

import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { defineComponent, h } from "vue";

interface BreadcrumbItem {
    label: string;
    url: string | null;
}

type BreadcrumbsResult = ReturnType<typeof useAdminBreadcrumbs>;
type BreadcrumbItems = BreadcrumbsResult["items"];

const pageProps: { value: Record<string, unknown> } = { value: {} };

vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({ props: pageProps.value }),
}));

vi.mock("laravel-vue-i18n", () => ({
    trans: (key: string) => key,
}));

vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => ({
        translate: (value?: { en?: string; de?: string } | null) => (value ? (value.en ?? value.de ?? "") : ""),
    }),
}));

const route = vi.fn((name: string, params?: Record<string, unknown>) =>
    params ? `${name}?${JSON.stringify(params)}` : name,
);

function setup(routeName: string | undefined, props: Record<string, unknown> = {}): BreadcrumbsResult {
    pageProps.value = { route: routeName, ...props };

    let result!: BreadcrumbsResult;
    const Host = defineComponent({
        setup() {
            result = useAdminBreadcrumbs();
            return () => h("div");
        },
    });

    mount(Host, {
        global: {
            provide: { ziggyRoute: route },
        },
    });

    return result;
}

function labels(items: BreadcrumbItems): string[] {
    return items.value.map((item) => item.label);
}

function firstItem(items: BreadcrumbItems): BreadcrumbItem {
    return items.value[0]!;
}

function lastItem(items: BreadcrumbItems): BreadcrumbItem {
    return items.value.at(-1)!;
}

function lastLabel(items: BreadcrumbItems): string {
    return labels(items).at(-1)!;
}

beforeEach(() => {
    route.mockClear();
});

describe("useAdminBreadcrumbs", () => {
    test("returns no crumbs for a non-admin route", () => {
        const { items } = setup("start");
        expect(items.value).toEqual([]);
    });

    test("returns no crumbs when route prop is missing", () => {
        pageProps.value = {};
        const { items } = setup(undefined);
        expect(items.value).toEqual([]);
    });

    test("always starts with the dashboard crumb", () => {
        const { items } = setup("admin.institution.index");
        expect(firstItem(items)).toEqual({ label: "admin.breadcrumbs.dashboard", url: "admin.dashboard" });
    });

    test("the last crumb never has a url", () => {
        const { items } = setup("admin.institution.index");
        expect(lastItem(items).url).toBeNull();
    });

    test("home points at the start route", () => {
        const { home } = setup("admin.institution.index");
        expect(home.value).toEqual({ icon: "pi pi-home", url: "start" });
    });

    describe("happenings", () => {
        test("index", () => {
            const { items } = setup("admin.happening.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.happenings"]);
        });

        test("create", () => {
            const { items } = setup("admin.happening.create");
            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.happenings",
                "admin.breadcrumbs.create",
            ]);
        });

        test("edit uses the happening label", () => {
            const { items } = setup("admin.happening.edit", { happening: { label: { en: "Open Day" } } });
            expect(lastLabel(items)).toBe("Open Day");
        });

        test("edit falls back to the generic edit label", () => {
            const { items } = setup("admin.happening.edit", { happening: { label: null } });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.edit");
        });
    });

    describe("institutions", () => {
        test("index", () => {
            const { items } = setup("admin.institution.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.institutions"]);
        });

        test("create", () => {
            const { items } = setup("admin.institution.create");
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit uses the institution title", () => {
            const { items } = setup("admin.institution.edit", { institution: { title: { en: "TU Berlin" } } });
            expect(lastLabel(items)).toBe("TU Berlin");
        });
    });

    describe("resource groups", () => {
        test("index without an institution", () => {
            const { items } = setup("admin.resource_group.index", {});
            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "admin.breadcrumbs.resource_groups",
            ]);
            expect(lastItem(items).url).toBeNull();
        });

        test("index with an institution builds the full chain", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.resource_group.index", { institution });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "admin.breadcrumbs.resource_groups",
            ]);
            expect(route).toHaveBeenCalledWith("admin.resource_group.index", { institution_id: 1 });
        });

        test("resolves the institution from resource_group.institution when institution prop is absent", () => {
            const institution = { id: 2, title: { en: "HU Berlin" } };
            const { items } = setup("admin.resource_group.index", { resource_group: { institution } });
            expect(labels(items)).toContain("HU Berlin");
        });

        test("edit uses the resource group title", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.resource_group.edit", {
                institution,
                resource_group: { title: { en: "Study Rooms" } },
            });
            expect(lastLabel(items)).toBe("Study Rooms");
        });
    });

    describe("resources", () => {
        test("index without a resource group", () => {
            const { items } = setup("admin.resource.index", {});
            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "admin.breadcrumbs.resources",
            ]);
            expect(lastItem(items).url).toBeNull();
        });

        test("index with a resource group builds the full chain", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const resourceGroup = { id: 5, title: { en: "Study Rooms" }, institution };
            const { items } = setup("admin.resource.index", { resourceGroup });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "Study Rooms",
                "admin.breadcrumbs.resources",
            ]);
            expect(route).toHaveBeenCalledWith("admin.resource.index", { resource_group_id: 5 });
        });

        test("edit uses the resource title", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const resourceGroup = { id: 5, title: { en: "Study Rooms" }, institution };
            const { items } = setup("admin.resource.edit", {
                resourceGroup,
                resource: { title: { en: "Room 101" } },
            });
            expect(lastLabel(items)).toBe("Room 101");
        });
    });

    describe("closings", () => {
        test("for an institution", () => {
            const closable = { id: 3, title: { en: "TU Berlin" } };
            const { items } = setup("admin.closing.index", { closable, closable_type: "institution" });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "admin.breadcrumbs.closings",
            ]);
            expect(route).toHaveBeenCalledWith("admin.institution.edit", { id: 3 });
        });

        test("for a resource builds the full chain", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const resourceGroup = { id: 5, title: { en: "Study Rooms" } };
            const closable = { id: 7, title: { en: "Room 101" } };
            const { items } = setup("admin.closing.index", {
                closable,
                closable_type: "resource",
                institution,
                resource_group: resourceGroup,
            });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "Study Rooms",
                "admin.breadcrumbs.resources",
                "Room 101",
                "admin.breadcrumbs.closings",
            ]);
        });

        test("without a closable falls back to a bare closings crumb", () => {
            const { items } = setup("admin.closing.index", {});
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.closings"]);
            expect(lastItem(items).url).toBeNull();
        });

        test("create appends the create crumb", () => {
            const closable = { id: 3, title: { en: "TU Berlin" } };
            const { items } = setup("admin.closing.create", { closable, closable_type: "institution" });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit appends the edit crumb", () => {
            const closable = { id: 3, title: { en: "TU Berlin" } };
            const { items } = setup("admin.closing.edit", { closable, closable_type: "institution" });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.edit");
        });
    });

    describe("settings", () => {
        test("for an institution", () => {
            const settingable = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.setting.index", {
                settingable,
                settingable_type: "institution",
            });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "admin.breadcrumbs.settings",
            ]);
        });

        test("for a resource group builds the full chain", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const settingable = { id: 5, title: { en: "Study Rooms" } };
            const { items } = setup("admin.setting.index", {
                settingable,
                settingable_type: "resource_group",
                institution,
            });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "Study Rooms",
                "admin.breadcrumbs.settings",
            ]);
        });

        test("without a settingable falls back to a bare settings crumb", () => {
            const { items } = setup("admin.setting.index", {});
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.settings"]);
            expect(lastItem(items).url).toBeNull();
        });

        test("edit appends the setting key label", () => {
            const settingable = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.setting.edit", {
                settingable,
                settingable_type: "institution",
                setting: { key: "timezone" },
            });
            expect(lastLabel(items)).toBe("admin.settings.keys.timezone.label");
        });
    });

    describe("app settings", () => {
        test("index", () => {
            const { items } = setup("admin.app_setting.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.app_settings"]);
        });

        test("edit", () => {
            const { items } = setup("admin.app_setting.edit");
            expect(lastLabel(items)).toBe("admin.breadcrumbs.edit");
        });
    });

    describe("mails", () => {
        test("index without an institution", () => {
            const { items } = setup("admin.mail.index", {});
            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "admin.breadcrumbs.mails",
            ]);
            expect(lastItem(items).url).toBeNull();
        });

        test("index with an institution builds the full chain", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.mail.index", { institution });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.institutions",
                "TU Berlin",
                "admin.breadcrumbs.mails",
            ]);
            expect(route).toHaveBeenCalledWith("admin.mail.index", { institution_id: 1 });
        });

        test("create appends the create crumb", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.mail.create", { institution });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit uses the mail type label", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.mail.edit", {
                institution,
                mail: { mail_type: { key: "booking_confirmation" } },
            });
            expect(lastLabel(items)).toBe("admin.mails.mail_types.booking_confirmation");
        });

        test("edit falls back to the generic edit label without a mail type", () => {
            const institution = { id: 1, title: { en: "TU Berlin" } };
            const { items } = setup("admin.mail.edit", { institution, mail: {} });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.edit");
        });
    });

    describe("user groups", () => {
        test("index", () => {
            const { items } = setup("admin.user_group.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.user_groups"]);
        });

        test("create", () => {
            const { items } = setup("admin.user_group.create");
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit uses the user group title", () => {
            const { items } = setup("admin.user_group.edit", { user_group: { title: { en: "Librarians" } } });
            expect(lastLabel(items)).toBe("Librarians");
        });

        test("import with a user group links back to its users page", () => {
            const userGroup = { id: 9, title: { en: "Librarians" } };
            const { items } = setup("admin.user_group.import", { user_group: userGroup });

            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.user_groups",
                "Librarians",
                "admin.breadcrumbs.import",
            ]);
            expect(route).toHaveBeenCalledWith("admin.user_group.users", { id: 9 });
        });

        test("import without a user group", () => {
            const { items } = setup("admin.user_group.import", {});
            expect(labels(items)).toEqual([
                "admin.breadcrumbs.dashboard",
                "admin.breadcrumbs.user_groups",
                "admin.breadcrumbs.import",
            ]);
        });

        test("users page shows the group title", () => {
            const { items } = setup("admin.user_group.users", { user_group: { title: { en: "Librarians" } } });
            expect(lastLabel(items)).toBe("Librarians");
        });
    });

    describe("users", () => {
        test("index", () => {
            const { items } = setup("admin.user.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.users"]);
        });

        test("create", () => {
            const { items } = setup("admin.user.create");
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit uses the user's name", () => {
            const { items } = setup("admin.user.edit", { user: { name: "Jane Doe" } });
            expect(lastLabel(items)).toBe("Jane Doe");
        });

        test("edit falls back to the generic edit label without a name", () => {
            const { items } = setup("admin.user.edit", { user: {} });
            expect(lastLabel(items)).toBe("admin.breadcrumbs.edit");
        });
    });

    describe("roles", () => {
        test("index", () => {
            const { items } = setup("admin.role.index");
            expect(labels(items)).toEqual(["admin.breadcrumbs.dashboard", "admin.breadcrumbs.roles"]);
        });

        test("create", () => {
            const { items } = setup("admin.role.create");
            expect(lastLabel(items)).toBe("admin.breadcrumbs.create");
        });

        test("edit uses the role name", () => {
            const { items } = setup("admin.role.edit", { role: { name: { en: "Administrator" } } });
            expect(lastLabel(items)).toBe("Administrator");
        });
    });
});
