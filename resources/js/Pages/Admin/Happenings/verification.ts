import type { AdminResource, AdminUser } from "@/Types/Admin";

type VerificationInput = {
    resource?: AdminResource;
    user?: AdminUser;
};

export function requiresAdminHappeningVerification({ resource, user }: VerificationInput): boolean {
    if (!user || !resource) {
        return true;
    }

    if (resource.is_verification_required === false) {
        return false;
    }

    if (resource.institution_id == null || !user.permissions) {
        return true;
    }

    return !user.permissions[String(resource.institution_id)]?.includes("no_verifier");
}
