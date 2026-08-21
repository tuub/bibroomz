import { requiresAdminHappeningVerification } from "@/Pages/Admin/Happenings/verification";
import type { AdminResource, AdminUser } from "@/Types/Admin";

import { describe, expect, test } from "vitest";

const user = (permissions: AdminUser["permissions"] = {}): AdminUser => ({
    id: 10,
    name: "Ada",
    permissions,
});

const resource = (overrides: Partial<AdminResource> = {}): AdminResource => ({
    id: 20,
    institution_id: 1,
    is_verification_required: true,
    ...overrides,
});

describe("requiresAdminHappeningVerification", () => {
    test("keeps verification required until both user and resource are selected", () => {
        expect(requiresAdminHappeningVerification({ user: user() })).toBe(true);
        expect(requiresAdminHappeningVerification({ resource: resource() })).toBe(true);
    });

    test("does not require verifier controls when the resource skips verification", () => {
        expect(
            requiresAdminHappeningVerification({
                user: user(),
                resource: resource({ institution_id: undefined, is_verification_required: false }),
            }),
        ).toBe(false);
    });

    test("does not require verifier controls for users with no_verifier at the resource institution", () => {
        expect(
            requiresAdminHappeningVerification({
                user: user({ "1": ["no_verifier"] }),
                resource: resource(),
            }),
        ).toBe(false);
    });

    test("requires verifier controls when verification is enabled and no matching permission exists", () => {
        expect(
            requiresAdminHappeningVerification({
                user: user({ "2": ["no_verifier"] }),
                resource: resource(),
            }),
        ).toBe(true);
    });

    test("requires verifier controls when verification is enabled but institution context is missing", () => {
        expect(
            requiresAdminHappeningVerification({
                user: user({ "1": ["no_verifier"] }),
                resource: resource({ institution_id: undefined }),
            }),
        ).toBe(true);
    });
});
