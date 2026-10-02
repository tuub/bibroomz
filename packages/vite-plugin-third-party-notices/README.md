# Vite third-party notices plugin

This package generates a deterministic license report from the packages
that survive into a Vite/Rollup output bundle. It fails when shipped packages
have incomplete metadata or no license text. It runs only in Node during the
build and does not depend on DOM runtime APIs or DOM ambient types.

It is written in TypeScript. The published package carries the compiled
JavaScript, its declarations, and the `src` the source maps point back at, so a
consumer steps into the original source without building anything.

```js
import {
    assetCreditsCheck,
    cssImportCheck,
    npmPackageLockCheck,
    thirdPartyNotices,
    unattributedModuleCheck,
} from "@tuub/vite-plugin-third-party-notices";

export default {
    plugins: [
        thirdPartyNotices({
            fileName: "THIRD-PARTY-LICENSES.txt",
            includePackages: ["animate.css", "primeicons", "tailwindcss"],
            checks: [
                npmPackageLockCheck(),
                cssImportCheck(),
                unattributedModuleCheck(),
                assetCreditsCheck({ creditsFile: "src/credits.json" }),
            ],
        }),
    ],
};
```

`includePackages` covers preprocessors whose generated output reaches the bundle
before Rollup can record its provenance. Tailwind is the usual case: a stylesheet
reaches it through `@import` or `@plugin`, PostCSS inlines the result into the
stylesheet being transformed, and no module id under `node_modules` is ever
created. Names are resolved by walking the `node_modules` chain rather than the
package's own entry points, so an `exports` map that keeps `package.json` private
does not hide the package. A package installed below another package, and any
layout npm does not use, needs an explicit `resolvePackage`.

`render` replaces the default report format. `checks` can enforce project policy
from the final bundle, package roots, parsed notices, and the discovery options the
build was given. `cwd` and `resolvePackage` support non-standard repository and
package-manager layouts. The report orders packages and license files by code unit,
so the same bundle yields the same bytes on every machine.

`npmPackageLockCheck` rejects bundled packages that npm records as dev-only or
cannot identify. `cssImportCheck` reads the stylesheets the bundle ships, follows
their `@import`, `@plugin`, `@config`, and `@reference` directives (never `@source`,
which only names files to scan for class names), and reports both packages nothing
credits and `includePackages` names no stylesheet reaches. `unattributedModuleCheck`
reports bundled code from outside both `node_modules` and the project, such as a
linked dependency, which package root scanning cannot see. `assetCreditsCheck` keeps
a JSON array of `{ "package": "..." }` credits aligned with packages that emit fonts,
icons, images, or similar assets.

A package with unusable metadata fails the build, and every such package is reported
in one pass rather than one per build.

## Installing

Every release goes out three ways, all carrying the same tarball built from the
same tag: a GitHub release asset, GitHub Packages, and the GitLab package registry
of the project this package lives in.

The release asset is the only one of the three that needs no token, because both
npm registries authenticate every read -- GitHub Packages even for a public
package. It is installed by URL:

```sh
npm install --save-dev https://github.com/tuub/bibroomz/releases/download/vite-plugin-third-party-notices@1.2.3/tuub-vite-plugin-third-party-notices-1.2.3.tgz
```

The lock file records that URL together with the tarball's hash, so installs stay
reproducible, but the version is part of the URL: there are no version ranges and
no update notices from `npm outdated` or a dependency bot.

For those, install from a registry instead. Point the `@tuub` scope at whichever
one you can reach in the consuming project's `.npmrc`:

```ini
# GitHub Packages
@tuub:registry=https://npm.pkg.github.com
```

```ini
# GitLab package registry, where <project id> is the numeric id shown on the
# project's overview page.
@tuub:registry=https://git.tu-berlin.de/api/v4/projects/<project id>/packages/npm/
```

GitHub Packages asks for a token even for a public package: add a personal access
token with the `read:packages` scope as
`//npm.pkg.github.com/:_authToken=<token>`. GitLab needs one only when the project
is not public, as `//git.tu-berlin.de/api/v4/projects/<project id>/packages/npm/:_authToken=<token>`.
Keep tokens in `~/.npmrc` rather than in the project. Then:

```sh
npm install --save-dev @tuub/vite-plugin-third-party-notices
```

A checkout builds the same tarball, which is the quickest way to try an unreleased
change in another project (`npm install ./tuub-vite-plugin-third-party-notices-<version>.tgz`):

```sh
npm pack --workspace @tuub/vite-plugin-third-party-notices
```

Packing compiles `src` into `dist` on the way, through `prepack`. To compile
without packing, run `npm run build --workspace @tuub/vite-plugin-third-party-notices`.
To build the tarball a release publishes, run the checks it runs, and print its
path:

```sh
node scripts/pack-package.ts packages/vite-plugin-third-party-notices
```

## Releasing

Raise `version` in `package.json` on a branch and merge it, then:

```sh
scripts/release-package.sh packages/vite-plugin-third-party-notices
```

The script tags `origin/main` with `vite-plugin-third-party-notices@<version>`, read
from that same commit, and pushes the tag to both remotes. It tags what GitLab
merged rather than your checkout, whose merge commit a squash or a rebase may have
replaced -- a tag on that one would pin the release to a commit that never lands on
`main`, and the publish would still succeed. Pass `--dry-run` to run every check and
print the tag without creating it.

The tag names the package because the repository around it is not published and
carries a version of its own that has nothing to do with this one.

The tag starts `npm:publish` in `.gitlab-ci.yml` and the `publish-plugin` workflow
in `.github/workflows/`. Each runs the package's tests, then `scripts/pack-package.ts`,
which packs the tarball and runs `pack.test.ts` against it: the tag against
`package.json`, then an install and an import in a project of its own with no vite
present, because every other test reads `src` and nothing else would notice an emit
a consumer cannot load. Those need a tarball, so an ordinary `npm run test` skips
them. Each pipeline then publishes that tarball to its own registry, so the two stay
in step without either having to reach the other. The GitHub workflow also attaches
it to a release of the same name.

Neither registry lets a published version be replaced, so a bad release needs a new
version rather than a retag. A release that only half happened is a different case:
re-running the GitHub workflow from the tag skips a version that is already on
GitHub Packages and attaches the tarball to the release it is missing from.
