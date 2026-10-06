/* eslint-disable vue/one-component-per-file */
import ClosingsIndex from "@/Pages/Admin/Closings/Index.vue";
import HappeningsIndex from "@/Pages/Admin/Happenings/Index.vue";
import MailsIndex from "@/Pages/Admin/Mails/Index.vue";
import ResourceGroupsIndex from "@/Pages/Admin/ResourceGroups/Index.vue";
import ResourcesIndex from "@/Pages/Admin/Resources/Index.vue";
import RolesIndex from "@/Pages/Admin/Roles/Index.vue";
import { PermissionKey } from "@/Types/PermissionKey.generated";

import { shallowMount } from "@vue/test-utils";
import { beforeEach, describe, expect, test, vi } from "vitest";
import { type Component, defineComponent, h } from "vue";

const hasGlobalPermissionMock = vi.hoisted(() => vi.fn(() => true));
const hasInstitutionPermissionMock = vi.hoisted(() => vi.fn(() => true));
const appStoreMock = vi.hoisted(() => ({
    formatDate: vi.fn(() => "05.10.2026"),
    formatTime: vi.fn(() => "10:00"),
    now: vi.fn(() => "2026-10-05T10:00:00"),
    translate: vi.fn((value?: Record<string, string>) => value?.en ?? ""),
}));

vi.mock("@/Stores/AuthStore", () => ({
    useAuthStore: () => ({
        hasGlobalPermission: hasGlobalPermissionMock,
        hasInstitutionPermission: hasInstitutionPermissionMock,
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
    setup() {
        return () => h("div", { "data-test": "create-link" });
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
    hasGlobalPermissionMock.mockReturnValue(true);
    hasInstitutionPermissionMock.mockReturnValue(true);
    tableRow = {};
});

describe("admin index permissions", () => {
    test("uses the global plural permissions for role actions", () => {
        tableRow = { id: "role-1", permissions: [] };

        render(RolesIndex, { roles: [tableRow] });

        expect(hasGlobalPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateRoles);
        expect(hasGlobalPermissionMock).toHaveBeenCalledWith(PermissionKey.EditRoles);
        expect(hasGlobalPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteRoles);
    });

    test("uses each happening's institution for scoped actions", () => {
        tableRow = {
            id: "happening-1",
            institution_id: "institution-1",
            start: "2026-10-05T11:00:00",
            end: "2026-10-05T12:00:00",
        };

        render(HappeningsIndex, { happenings: [tableRow] });

        expect(hasGlobalPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateHappenings);
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.EditHappenings, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteHappenings, "institution-1");
    });

    test("uses the mail page institution for create, edit, and delete", () => {
        tableRow = { id: "mail-1", mail_type: { key: "confirmation" } };

        render(MailsIndex, {
            institution: { id: "institution-1", title: { en: "Library" } },
            mails: [tableRow],
        });

        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateMails, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.EditMails, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteMails, "institution-1");
    });

    test("uses the parent institution for resource group actions", () => {
        tableRow = { id: "resource-group-1" };

        render(ResourceGroupsIndex, {
            institution: { id: "institution-1", title: { en: "Library" } },
            resource_groups: [tableRow],
        });

        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateResourceGroups, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.EditResourceGroups, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteResourceGroups, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.ViewResources, "institution-1");
    });

    test("uses the resource group's institution for resource actions", () => {
        tableRow = { id: "resource-1", business_hours: [] };

        render(ResourcesIndex, {
            resourceGroup: {
                id: "resource-group-1",
                institution: { id: "institution-1", title: { en: "Library" } },
            },
            resources: [tableRow],
        });

        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.EditResources, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateResources, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteResources, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.ViewClosings, "institution-1");
    });

    test("uses the resolved institution for closing actions", () => {
        tableRow = { id: "closing-1" };

        render(ClosingsIndex, {
            closable: { id: "resource-1", title: { en: "Room" } },
            closable_type: "resource",
            closings: [tableRow],
            institution: { id: "institution-1" },
        });

        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.CreateClosings, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.EditClosings, "institution-1");
        expect(hasInstitutionPermissionMock).toHaveBeenCalledWith(PermissionKey.DeleteClosings, "institution-1");
    });
});
