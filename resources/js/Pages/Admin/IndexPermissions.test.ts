/* eslint-disable vue/one-component-per-file */
import ClosingsIndex from "@/Pages/Admin/Closings/Index.vue";
import HappeningsIndex from "@/Pages/Admin/Happenings/Index.vue";
import MailsIndex from "@/Pages/Admin/Mails/Index.vue";
import ResourceGroupsIndex from "@/Pages/Admin/ResourceGroups/Index.vue";
import ResourcesIndex from "@/Pages/Admin/Resources/Index.vue";
import RolesIndex from "@/Pages/Admin/Roles/Index.vue";

import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { type Component, defineComponent, h } from "vue";

const hasPermissionMock = vi.hoisted(() => vi.fn(() => true));
const appStoreMock = vi.hoisted(() => ({
    formatDate: vi.fn(() => "05.10.2026"),
    formatTime: vi.fn(() => "10:00"),
    now: vi.fn(() => "2026-10-05T10:00:00"),
    translate: vi.fn((value?: Record<string, string>) => value?.en ?? ""),
}));

vi.mock("@/Stores/AuthStore", () => ({
    useAuthStore: () => ({
        hasPermission: hasPermissionMock,
    }),
}));

vi.mock("@/Stores/AppStore", () => ({
    useAppStore: () => appStoreMock,
}));

vi.mock("laravel-vue-i18n", () => ({
    transChoice: (key: string) => key,
}));

let tableRow: Record<string, unknown> = {};

const DataTableStub = defineComponent({
    setup(_, { slots }) {
        return () => h("div", [slots.header?.(), slots.default?.()]);
    },
});

const ColumnStub = defineComponent({
    setup(_, { slots }) {
        return () => h("div", slots.body?.({ data: tableRow }));
    },
});

const CreateLinkStub = defineComponent({
    props: {
        institutionId: {
            type: [String, Number],
            default: undefined,
        },
    },
    setup(props) {
        return () =>
            h("div", {
                "data-test": "create-link",
                "data-institution-id": props.institutionId,
            });
    },
});

const LinkGroupStub = defineComponent({
    setup(_, { slots }) {
        return () => h("div", slots.default?.());
    },
});

function render(component: Component, props: Record<string, unknown>) {
    return shallowMount(component, {
        props,
        global: {
            provide: {
                ziggyRoute: vi.fn(),
            },
            mocks: {
                $t: (key: string) => key,
            },
            stubs: {
                ActionLink: true,
                BooleanField: true,
                Column: ColumnStub,
                CreateLink: CreateLinkStub,
                DataTable: DataTableStub,
                LinkGroup: LinkGroupStub,
                PopupLink: true,
                RelationLink: true,
            },
        },
    });
}

beforeEach(() => {
    vi.clearAllMocks();
    hasPermissionMock.mockReturnValue(true);
    tableRow = {};
});

describe("admin index permissions", () => {
    test("uses the global plural permissions for role actions", () => {
        tableRow = { id: "role-1", permissions: [] };

        render(RolesIndex, { roles: [tableRow] });

        expect(hasPermissionMock).toHaveBeenCalledWith("edit_roles");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_roles");
    });

    test("uses each happening's institution for scoped actions", () => {
        tableRow = {
            id: "happening-1",
            institution_id: "institution-1",
            start: "2026-10-05T11:00:00",
            end: "2026-10-05T12:00:00",
        };

        render(HappeningsIndex, { happenings: [tableRow] });

        expect(hasPermissionMock).toHaveBeenCalledWith("edit_happenings", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_happenings", "institution-1");
    });

    test("uses the mail page institution for create, edit, and delete", () => {
        tableRow = { id: "mail-1", mail_type: { key: "confirmation" } };

        const wrapper = render(MailsIndex, {
            institution: { id: "institution-1", title: { en: "Library" } },
            mails: [tableRow],
        });

        expect(wrapper.get('[data-test="create-link"]').attributes("data-institution-id")).toBe("institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("edit_mails", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_mails", "institution-1");
    });

    test("uses the parent institution for resource group actions", () => {
        tableRow = { id: "resource-group-1" };

        const wrapper = render(ResourceGroupsIndex, {
            institution: { id: "institution-1", title: { en: "Library" } },
            resource_groups: [tableRow],
        });

        expect(wrapper.get('[data-test="create-link"]').attributes("data-institution-id")).toBe("institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("edit_resource_groups", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_resource_groups", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("view_resources", "institution-1");
    });

    test("uses the resource group's institution for resource actions", () => {
        tableRow = { id: "resource-1", business_hours: [] };

        const wrapper = render(ResourcesIndex, {
            resourceGroup: {
                id: "resource-group-1",
                institution: { id: "institution-1", title: { en: "Library" } },
            },
            resources: [tableRow],
        });

        expect(wrapper.get('[data-test="create-link"]').attributes("data-institution-id")).toBe("institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("edit_resources", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("create_resources", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_resources", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("view_closings", "institution-1");
    });

    test("uses the resolved institution for closing actions", () => {
        tableRow = { id: "closing-1" };

        const wrapper = render(ClosingsIndex, {
            closable: { id: "resource-1", title: { en: "Room" } },
            closable_type: "resource",
            closings: [tableRow],
            institution: { id: "institution-1" },
        });

        expect(wrapper.get('[data-test="create-link"]').attributes("data-institution-id")).toBe("institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("edit_closings", "institution-1");
        expect(hasPermissionMock).toHaveBeenCalledWith("delete_closings", "institution-1");
    });
});
