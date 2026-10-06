# Permission development

`app/Enums/PermissionKey.php` is the source of truth for stored permission keys. Add or rename a key there first,
then regenerate the committed TypeScript registry:

```bash
composer run permissions:generate
```

`composer run permissions:check` and the `permission-registry` CI job fail when the generated registry is stale.
A production rename also needs a data migration that updates the existing `permissions` row without losing role
assignments; the seeder intentionally does not delete or rename records.

## Frontend checks

Use `PermissionKey` constants and choose the lookup mode explicitly:

```ts
authStore.hasGlobalPermission(PermissionKey.EditRoles)
authStore.hasInstitutionPermission(PermissionKey.EditResources, institutionId)
```

Global lookup means the user has the permission in at least one institution assignment. Institution lookup checks
only the requested institution and never falls back to a global scan. Stored permission keys are not intrinsically
global or scoped: aggregate pages may use global lookup while row actions use institution lookup for the same key.

Permission checks belong in the page that decides whether to render an action. Shared links such as `CreateLink`
only render and navigate; they must not infer permission names from model names.

## Backend checks

Frontend checks are presentation only. Every controller action or Form Request must authorize through a policy or
Gate. For existing records, resolve the institution through the authoritative model relationship and pass that
institution to authorization. Do not authorize an existing record against a separately submitted institution ID.

HTTP feature tests for a scoped endpoint should cover this matrix:

- guest: redirected by authentication middleware;
- unrelated permission: forbidden;
- matching permission in another institution: forbidden;
- weaker permission, such as view for a write action: forbidden;
- matching permission in the target institution: allowed.

Use `assertScopedGetAuthorizationMatrix()` from `Tests\Concerns\InteractsWithPermissions` for GET endpoints.
Aggregate endpoints can use `assertGlobalGetAuthorizationMatrix()`.

## Review checklist

For every new admin route, verify:

- its stored `PermissionKey` and Laravel policy ability are not confused;
- its controller or Form Request performs backend authorization;
- the institution comes from an authoritative relationship;
- frontend visibility uses the correct explicit lookup mode;
- negative HTTP authorization cases are tested;
- the generated TypeScript registry is current.
