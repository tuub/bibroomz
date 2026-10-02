// @vitest-environment node
import {
    assetCreditsCheck,
    cssImportCheck,
    formatLicenseReport,
    modulePath,
    noticesForRoots,
    npmPackageLockCheck,
    packageRoot,
    packageRoots,
    packageSpecifier,
    readNotice,
    thirdPartyNotices,
    unattributedModuleCheck,
} from "./src/index.js";

import { mkdir, mkdtemp, rm, symlink, writeFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import * as path from "node:path";
import { type Plugin, type Rollup } from "vite";
import { afterEach, describe, expect, it } from "vitest";

const temporaryDirectories: string[] = [];

afterEach(async () => {
    await Promise.all(
        temporaryDirectories.splice(0).map((directory) => rm(directory, { force: true, recursive: true })),
    );
});

async function fixturePackage(
    manifest: Record<string, unknown>,
    files: Record<string, string> = {},
    directoryName = typeof manifest.name === "string" ? manifest.name : "fixture",
): Promise<string> {
    const fixture = await mkdtemp(path.join(tmpdir(), "vite-plugin-third-party-notices-"));
    const root = path.join(fixture, "node_modules", ...directoryName.split("/"));
    temporaryDirectories.push(fixture);
    await mkdir(root, { recursive: true });
    await writeFile(path.join(root, "package.json"), `${JSON.stringify(manifest)}\n`);

    for (const [file, contents] of Object.entries(files)) {
        const target = path.join(root, file);
        await mkdir(path.dirname(target), { recursive: true });
        await writeFile(target, contents);
    }

    return root;
}

async function fixtureProject(files: Record<string, string> = {}): Promise<string> {
    const project = await mkdtemp(path.join(tmpdir(), "vite-plugin-third-party-notices-"));
    temporaryDirectories.push(project);
    await writeFile(path.join(project, "package.json"), `${JSON.stringify({ name: "project" })}\n`);

    for (const [file, contents] of Object.entries(files)) {
        const target = path.join(project, file);
        await mkdir(path.dirname(target), { recursive: true });
        await writeFile(target, contents);
    }

    return project;
}

function chunkOf(modules: Record<string, number>): Rollup.OutputBundle {
    return {
        "app.js": {
            type: "chunk",
            modules: Object.fromEntries(
                Object.entries(modules).map(([id, renderedLength]) => [id, { renderedLength }]),
            ),
        },
    } as unknown as Rollup.OutputBundle;
}

interface EmittedFile {
    fileName?: string;
    source?: unknown;
    type: string;
}

/**
 * Run the plugin's generateBundle hook against a context that collects emitted
 * files and turns this.error into a rejection, the way Rollup does.
 */
async function generateBundle(plugin: Plugin, bundle: Rollup.OutputBundle, emitted: EmittedFile[]): Promise<void> {
    const hook = plugin.generateBundle;

    if (typeof hook !== "function") {
        throw new TypeError("Expected the plugin to expose a generateBundle hook");
    }

    const context = {
        emitFile(file: EmittedFile): string {
            emitted.push(file);
            return "asset-id";
        },
        error(error: Error | string): never {
            throw typeof error === "string" ? new Error(error) : error;
        },
    } as unknown as Rollup.PluginContext;

    await hook.call(context, {} as Rollup.NormalizedOutputOptions, bundle, false);
}

describe("third-party notice package discovery", () => {
    it("runs in Node without DOM globals", () => {
        expect("document" in globalThis).toBe(false);
        expect("window" in globalThis).toBe(false);
    });

    it("uses rendered modules and retains zero-length stylesheets", () => {
        const bundle = {
            "app.js": {
                type: "chunk",
                modules: {
                    "/workspace/node_modules/rendered/index.js": { renderedLength: 10 },
                    "/workspace/node_modules/shaken-out/index.js": { renderedLength: 0 },
                    "/workspace/node_modules/styles/index.css?used": { renderedLength: 0 },
                },
            },
        } as unknown as Rollup.OutputBundle;

        const roots = packageRoots(bundle);

        expect(roots).toContain("/workspace/node_modules/rendered");
        expect(roots).toContain("/workspace/node_modules/styles");
        expect(roots).not.toContain("/workspace/node_modules/shaken-out");
    });

    it("skips emitted assets and module ids that name no package", () => {
        const bundle = {
            "app.css": { type: "asset", fileName: "app.css" },
            "app.js": {
                type: "chunk",
                modules: {
                    "/workspace/node_modules/kept/index.js": { renderedLength: 10 },
                    "/workspace/src/app.js": { renderedLength: 10 },
                },
            },
        } as unknown as Rollup.OutputBundle;

        expect(packageRoots(bundle)).toEqual(new Set(["/workspace/node_modules/kept"]));
        expect(packageRoot("/workspace/node_modules/")).toBeNull();
    });

    it("resolves configured pre-Rollup packages from the consumer's working directory", () => {
        const resolvePackage = (name: string, cwd: string): string => `${cwd}/resolved/${name}`;

        const roots = packageRoots(
            {},
            {
                cwd: "/consumer",
                includePackages: ["generated-css"],
                resolvePackage,
            },
        );

        expect(roots).toEqual(new Set(["/consumer/resolved/generated-css"]));
    });

    it("normalizes Windows paths, virtual prefixes and nested packages", () => {
        expect(packageRoot("\0C:\\repo\\node_modules\\@scope\\package\\index.js?commonjs-proxy")).toBe(
            "C:/repo/node_modules/@scope/package",
        );
        expect(packageRoot("/repo/node_modules/outer/node_modules/inner/index.js")).toBe(
            "/repo/node_modules/outer/node_modules/inner",
        );
        expect(packageRoot("/repo/resources/js/app.ts")).toBeNull();
    });
});

describe("third-party notice metadata", () => {
    it.each([
        {
            manifest: { license: "MIT", version: "1.0.0" },
            error: "Incomplete package metadata",
        },
        {
            manifest: { name: "missing-identifier", version: "1.0.0" },
            error: "Missing license identifier for missing-identifier@1.0.0",
        },
        {
            manifest: { license: "MIT", name: "missing-text", version: "1.0.0" },
            error: "Missing license text for missing-text@1.0.0",
        },
    ])("fails closed for incomplete package metadata", async ({ error, manifest }) => {
        const root = await fixturePackage(manifest);

        expect(() => readNotice(root)).toThrow(error);
    });

    it("reads all license and notice files and reports the repository", async () => {
        const root = await fixturePackage(
            {
                licenses: [{ type: "MIT" }, "Apache-2.0"],
                name: "complete-package",
                repository: { url: "https://example.org/complete-package" },
                version: "2.0.0",
            },
            {
                COPYING: "Copying terms\n",
                "LICENSE.md": "License terms\n",
                NOTICE: "Required notice\n",
                "NOTICE.extra": "Another notice\n",
            },
        );

        const notice = readNotice(root);

        expect(notice).toMatchObject({
            license: "MIT OR Apache-2.0",
            name: "complete-package",
            repository: "https://example.org/complete-package",
            version: "2.0.0",
        });
        expect(notice.licenseTexts).toEqual([
            { file: "COPYING", text: "Copying terms" },
            { file: "LICENSE.md", text: "License terms" },
        ]);
        expect(notice.noticeTexts).toEqual([
            { file: "NOTICE", text: "Required notice" },
            { file: "NOTICE.extra", text: "Another notice" },
        ]);
        expect(formatLicenseReport([notice])).toContain(
            "Repository: https://example.org/complete-package\nLicense Text (COPYING):\n===\n\nCopying terms",
        );
        expect(formatLicenseReport([notice])).toContain("Notice Text (NOTICE):\n===\n\nRequired notice");
    });

    it("reads the pre-npm5 licenses and repository shorthands", async () => {
        const root = await fixturePackage(
            {
                licenses: "MIT",
                name: "legacy-package",
                repository: "https://example.org/legacy-package",
                version: "1.0.0",
            },
            { LICENSE: "Legacy terms\n" },
        );

        expect(readNotice(root)).toMatchObject({
            license: "MIT",
            repository: "https://example.org/legacy-package",
        });
    });

    it("reads a safely referenced license file and does not report it twice", async () => {
        const root = await fixturePackage(
            {
                license: "SEE LICENSE IN legal/LICENSE.txt",
                name: "referenced-license",
                version: "1.0.0",
            },
            {
                LICENSE: "Additional terms\n",
                "legal/LICENSE.txt": "Referenced terms\n",
            },
        );

        expect(readNotice(root).licenseTexts).toEqual([
            { file: "legal/LICENSE.txt", text: "Referenced terms" },
            { file: "LICENSE", text: "Additional terms" },
        ]);

        const directRoot = await fixturePackage(
            {
                license: "SEE LICENSE IN LICENSE",
                name: "direct-reference",
                version: "1.0.0",
            },
            { LICENSE: "Direct terms\n" },
        );

        expect(readNotice(directRoot).licenseTexts).toEqual([{ file: "LICENSE", text: "Direct terms" }]);
    });

    it("rejects referenced licenses outside the package directory", async () => {
        const root = await fixturePackage({
            license: "SEE LICENSE IN ../outside-license",
            name: "unsafe-package",
            version: "1.0.0",
        });

        expect(() => readNotice(root)).toThrow("License file escapes package directory for unsafe-package");
    });

    it("rejects a referenced symlink that escapes the package directory", async () => {
        const root = await fixturePackage({
            license: "SEE LICENSE IN LICENSE",
            name: "symlink-package",
            version: "1.0.0",
        });
        const outside = path.join(root, "..", "outside-license");
        await writeFile(outside, "Outside terms\n");
        await symlink(outside, path.join(root, "LICENSE"));

        expect(() => readNotice(root)).toThrow("License file escapes package directory for symlink-package");
    });

    it("deduplicates package versions and sorts by name and version", async () => {
        const zulu = await fixturePackage(
            { license: "MIT", name: "zulu", version: "1.0.0" },
            { LICENSE: "Zulu terms" },
        );
        const alphaV2 = await fixturePackage(
            { license: "MIT", name: "alpha", version: "2.0.0" },
            { LICENSE: "Alpha 2 terms" },
        );
        const alphaV1 = await fixturePackage(
            { license: "MIT", name: "alpha", version: "1.0.0" },
            { LICENSE: "Alpha 1 terms" },
        );
        const duplicateAlphaV1 = await fixturePackage(
            { license: "MIT", name: "alpha", version: "1.0.0" },
            { LICENSE: "Duplicate alpha terms" },
        );

        const notices = noticesForRoots([zulu, alphaV2, alphaV1, duplicateAlphaV1]);

        expect(notices.map(({ name, version }) => `${name}@${version}`)).toEqual([
            "alpha@1.0.0",
            "alpha@2.0.0",
            "zulu@1.0.0",
        ]);
        expect(notices[0]?.licenseTexts[0]?.text).toBe("Duplicate alpha terms");
    });

    it("configures the emitted filename, renderer and project checks", async () => {
        const root = await fixturePackage(
            { license: "MIT", name: "configured-package", version: "1.0.0" },
            { LICENSE: "Configured terms" },
        );
        const checkedRoots: string[] = [];
        const plugin = thirdPartyNotices({
            fileName: "licenses.txt",
            includePackages: ["configured-package"],
            resolvePackage: () => root,
            render: (notices) => `Packages: ${notices.map(({ name }) => name).join(", ")}\n`,
            checks: [({ roots }) => void checkedRoots.push(...roots)],
        });
        const emitted: EmittedFile[] = [];

        await generateBundle(plugin, {}, emitted);

        expect(checkedRoots).toEqual([root]);
        expect(emitted).toEqual([
            {
                fileName: "licenses.txt",
                source: "Packages: configured-package\n",
                type: "asset",
            },
        ]);
        expect(formatLicenseReport([readNotice(root)])).toContain(
            "Name: configured-package\nVersion: 1.0.0\nLicense: MIT",
        );
    });

    it("fails the build on the first check that reports, and emits nothing", async () => {
        const later: string[] = [];
        const plugin = thirdPartyNotices({
            checks: [() => "primary-package ships without a license.", () => void later.push("ran")],
        });
        const emitted: EmittedFile[] = [];

        await expect(generateBundle(plugin, {}, emitted)).rejects.toThrow("primary-package ships without a license.");

        // A report that leaves out what a check objected to is worse than no
        // report, so neither the remaining checks nor the emit may run.
        expect(later).toEqual([]);
        expect(emitted).toEqual([]);
    });

    it("refuses a filename or a credits filename that names no file", () => {
        expect(() => thirdPartyNotices({ fileName: "   " })).toThrow(
            "The third-party notices filename must not be empty.",
        );
        expect(() => assetCreditsCheck({ creditsFile: "   " })).toThrow(
            "The asset credits filename must not be empty.",
        );
    });
});

describe("third-party notice project checks", () => {
    it("rejects bundled packages that package-lock marks as dev-only or does not know", async () => {
        const root = await fixturePackage(
            { license: "MIT", name: "dev-package", version: "1.0.0" },
            { LICENSE: "Terms" },
        );
        const cwd = path.resolve(root, "../..");
        const lockFile = path.join(cwd, "package-lock.json");
        const check = npmPackageLockCheck();
        const context = {
            bundle: {},
            cwd,
            notices: [readNotice(root)],
            roots: new Set([root]),
        };
        await writeFile(lockFile, `${JSON.stringify({ packages: { "node_modules/dev-package": { dev: true } } })}\n`);

        expect(await check(context)).toContain("dev-package (node_modules/dev-package) is a devDependency");

        await writeFile(lockFile, `${JSON.stringify({ packages: { "node_modules/dev-package": {} } })}\n`);
        expect(await check(context)).toBeNull();

        context.roots.add(path.join(cwd, "node_modules", "unknown-package"));
        expect(await check(context)).toContain("node_modules/unknown-package is in the bundle");
    });

    it("keeps asset credits aligned with packages that ship recognisable files", async () => {
        const root = await fixturePackage({ license: "MIT", name: "icons", version: "1.0.0" }, { LICENSE: "Terms" });
        const cwd = path.resolve(root, "../..");
        const creditsFile = path.join(cwd, "credits.json");
        const bundle = {
            // The chunks of the same bundle emit no assets of their own.
            "app.js": { type: "chunk", modules: {} },
            "icons.woff2": {
                type: "asset",
                originalFileNames: [path.join(root, "fonts", "icons.woff2")],
            },
            // An asset the build synthesised, one the project ships itself,
            // and one that is not the kind of file a package is credited for.
            "inline.css": { type: "asset" },
            "logo.png": { type: "asset", originalFileNames: [path.join(cwd, "resources", "logo.png")] },
            "theme.css": { type: "asset", originalFileNames: [path.join(root, "theme.css")] },
        } as unknown as Rollup.OutputBundle;
        const check = assetCreditsCheck({ creditsFile: "credits.json" });
        const context = {
            bundle,
            cwd,
            notices: [readNotice(root)],
            roots: new Set([root]),
        };
        await writeFile(creditsFile, `${JSON.stringify([{ package: "icons" }])}\n`);

        expect(await check(context)).toBeNull();

        await writeFile(creditsFile, `${JSON.stringify([{ package: "old-icons" }])}\n`);
        const error = await check(context);
        expect(error).toContain("icons ships a file of its own into the bundle and nothing credits it");
        expect(error).toContain("old-icons is credited and ships nothing");

        // Anything the credits file holds that is not a { package } entry
        // credits nothing, rather than crediting something unintended.
        await writeFile(creditsFile, `${JSON.stringify({ package: "icons" })}\n`);
        expect(await check(context)).toContain("icons ships a file of its own into the bundle");

        await writeFile(creditsFile, `${JSON.stringify([{ package: "icons" }, "icons", { name: "icons" }])}\n`);
        expect(await check(context)).toBeNull();
    });
});

describe("third-party notice package resolution", () => {
    it("resolves packages that keep package.json out of their exports map", async () => {
        const project = await fixtureProject({
            "node_modules/hidden/package.json": `${JSON.stringify({
                exports: { ".": "./index.js" },
                name: "hidden",
                version: "1.0.0",
            })}\n`,
            "node_modules/@scope/hidden/package.json": `${JSON.stringify({
                exports: { ".": "./index.js" },
                name: "@scope/hidden",
                version: "1.0.0",
            })}\n`,
        });

        const roots = packageRoots({}, { cwd: project, includePackages: ["hidden", "@scope/hidden"] });

        expect(roots).toEqual(
            new Set([
                path.join(project, "node_modules", "hidden"),
                path.join(project, "node_modules", "@scope", "hidden"),
            ]),
        );
    });

    it("names the package it cannot find", async () => {
        const project = await fixtureProject();

        expect(() => packageRoots({}, { cwd: project, includePackages: ["@scope/absent"] })).toThrow(
            `Cannot find the package @scope/absent below ${project}`,
        );
    });

    it("reads package names out of module specifiers", () => {
        expect(packageSpecifier("tailwindcss/theme.css")).toBe("tailwindcss");
        expect(packageSpecifier("@tailwindcss/typography")).toBe("@tailwindcss/typography");
        expect(packageSpecifier("@/alias.css")).toBeNull();
        expect(packageSpecifier("@scope")).toBeNull();
        expect(packageSpecifier("./sibling.css")).toBeNull();
        expect(packageSpecifier("")).toBeNull();
    });

    it("points module ids at the files behind them", () => {
        expect(modulePath("\0/repo/node_modules/package/index.js?used")).toBe("/repo/node_modules/package/index.js");
        expect(modulePath("virtual:module")).toBeNull();
        expect(modulePath("\0vite/preload-helper")).toBeNull();
    });
});

describe("third-party notice stylesheet checks", () => {
    async function stylesheetProject(main: string): Promise<string> {
        return await fixtureProject({
            "node_modules/css-package/package.json": `${JSON.stringify({ name: "css-package", version: "1.0.0" })}\n`,
            "node_modules/css-package/theme.css": '@import "css-package/nested.css";\n',
            "node_modules/css-package/nested.css": ".nested {}\n",
            "node_modules/@scope/plugin-package/package.json": `${JSON.stringify({
                exports: { ".": "./index.js" },
                name: "@scope/plugin-package",
                version: "1.0.0",
            })}\n`,
            "resources/main.css": main,
            "resources/extra.css": '@import "css-package/theme.css";\n',
        });
    }

    function contextFor(project: string, includePackages: string[], roots: string[]) {
        return {
            bundle: chunkOf({
                [path.join(project, "resources", "app.js")]: 10,
                [path.join(project, "resources", "main.css")]: 0,
            }),
            cwd: project,
            includePackages,
            notices: [],
            roots: new Set(roots),
        };
    }

    const main = [
        '@import "css-package/theme.css" layer(base);',
        '@import url("https://fonts.example.org/face.css");',
        '@import "./extra.css";',
        '@import "~css-package/nested.css";',
        '@import "@/alias.css";',
        '@plugin "@scope/plugin-package";',
        '@source "../node_modules/scanned-package/**/*.vue";',
        "",
    ].join("\n");

    it("accepts a stylesheet whose packages are all credited", async () => {
        const project = await stylesheetProject(main);
        const roots = [
            path.join(project, "node_modules", "css-package"),
            path.join(project, "node_modules", "@scope", "plugin-package"),
        ];

        expect(await cssImportCheck()(contextFor(project, ["css-package", "@scope/plugin-package"], roots))).toBeNull();
    });

    it("reports packages a stylesheet reaches that nothing credits", async () => {
        const project = await stylesheetProject(main);

        const error = await cssImportCheck()(contextFor(project, [], []));

        expect(error).toContain(
            `css-package is referenced by ${path.join("resources", "main.css")} and nothing credits it.`,
        );
        expect(error).toContain("@scope/plugin-package is referenced by");
        expect(error).not.toContain("scanned-package");
        expect(error).not.toContain("fonts.example.org");
        expect(error).not.toContain("alias.css");
    });

    it("follows relative imports and subpaths into further stylesheets", async () => {
        const project = await fixtureProject({
            "node_modules/css-package/package.json": `${JSON.stringify({ name: "css-package", version: "1.0.0" })}\n`,
            "node_modules/css-package/theme.css": '@import "deep-package/deep.css";\n',
            "node_modules/deep-package/package.json": `${JSON.stringify({ name: "deep-package", version: "1.0.0" })}\n`,
            "node_modules/deep-package/deep.css": ".deep {}\n",
            "resources/main.css": '@import "./extra.css";\n',
            "resources/extra.css": '@import "css-package/theme.css";\n',
        });

        const error = await cssImportCheck()(contextFor(project, [], []));

        expect(error).toContain(`css-package is referenced by ${path.join("resources", "extra.css")}`);
        expect(error).toContain(
            `deep-package is referenced by ${path.join("node_modules", "css-package", "theme.css")}`,
        );
    });

    it("reports a declared package no stylesheet reaches, and a specifier it cannot resolve", async () => {
        const project = await stylesheetProject(`${main}@import "absent-package/absent.css";\n`);
        const roots = [
            path.join(project, "node_modules", "css-package"),
            path.join(project, "node_modules", "@scope", "plugin-package"),
        ];

        const error = await cssImportCheck()(
            contextFor(project, ["css-package", "@scope/plugin-package", "stale-package"], roots),
        );

        expect(error).toContain("stale-package is named in includePackages and no bundled stylesheet references it.");
        expect(error).toContain(
            `absent-package/absent.css is referenced by ${path.join("resources", "main.css")} and does not resolve to a package.`,
        );
    });

    it("ignores references that no readable stylesheet backs", async () => {
        const project = await fixtureProject({
            "node_modules/css-package/package.json": `${JSON.stringify({ name: "css-package", version: "1.0.0" })}\n`,
            "resources/directory.css/placeholder.txt": "",
            "resources/main.css": [
                // None of these resolves to a file to follow: one is absent,
                // one is a directory, and one names a subpath that is not a
                // stylesheet at all.
                '@import "./absent.css";',
                '@import "./directory.css";',
                '@import "css-package/theme";',
                "",
            ].join("\n"),
        });
        const context = {
            bundle: chunkOf({
                [path.join(project, "resources", "main.css")]: 0,
                // A stylesheet the bundle records but the build no longer has.
                [path.join(project, "resources", "absent.css")]: 0,
            }),
            cwd: project,
            includePackages: [],
            notices: [],
            // A credited root that is gone stays itself rather than failing the
            // comparison the credited set is for.
            roots: new Set([
                path.join(project, "node_modules", "css-package"),
                path.join(project, "node_modules", "absent-package"),
            ]),
        };

        expect(await cssImportCheck()(context)).toBeNull();
    });

    it("reports bundled code that lives outside the project and node_modules", async () => {
        const project = await fixtureProject();
        const linked = await fixtureProject({ "src/index.js": "export default 1;\n" });
        const bundle = chunkOf({
            [path.join(linked, "src", "index.js")]: 10,
            [path.join(project, "resources", "app.ts")]: 10,
            [path.join(project, "node_modules", "package", "index.js")]: 10,
            "\0vite/preload-helper": 10,
        });
        const context = { bundle, cwd: project, notices: [], roots: new Set<string>() };

        const error = await unattributedModuleCheck()(context);

        expect(error).toContain(`${linked} is in the bundle and no package root covers it.`);
        expect(error).not.toContain("app.ts");
        expect(error).not.toContain("preload-helper");
        expect(await unattributedModuleCheck()({ ...context, bundle: chunkOf({}) })).toBeNull();
    });

    it("names the file itself when no directory above it is a package", async () => {
        const project = await fixtureProject();
        // Directly at the filesystem root, so the walk upwards ends without
        // ever finding a package.json.
        const orphan = "/vite-plugin-third-party-notices-orphan.js";

        const error = await unattributedModuleCheck()({
            bundle: chunkOf({ [orphan]: 10 }),
            cwd: project,
            notices: [],
            roots: new Set<string>(),
        });

        expect(error).toContain(`${orphan} is in the bundle and no package root covers it.`);
    });
});

describe("third-party notice failure reporting", () => {
    it("reports every unreadable package at once", async () => {
        const missingText = await fixturePackage({ license: "MIT", name: "missing-text", version: "1.0.0" });
        const missingIdentifier = await fixturePackage(
            { name: "missing-identifier", version: "1.0.0" },
            {
                LICENSE: "Terms\n",
            },
        );

        let failure: Error | null = null;

        try {
            noticesForRoots([missingText, missingIdentifier]);
        } catch (error) {
            failure = error as Error;
        }

        expect(failure?.message).toContain("Missing license text for missing-text@1.0.0");
        expect(failure?.message).toContain("Missing license identifier for missing-identifier@1.0.0");
        expect(failure?.cause).toBeInstanceOf(AggregateError);
        expect((failure?.cause as AggregateError).errors).toHaveLength(2);
    });

    it("names the package whose metadata it cannot read", async () => {
        const root = await fixturePackage({ license: "MIT", name: "broken", version: "1.0.0" }, { LICENSE: "Terms" });
        await writeFile(path.join(root, "package.json"), "{\n");

        expect(() => readNotice(root)).toThrow(`Cannot read package metadata from ${root}`);
    });

    it("rejects a referenced license file that is missing or empty", async () => {
        const missing = await fixturePackage(
            { license: "SEE LICENSE IN terms.md", name: "missing-reference", version: "1.0.0" },
            { LICENSE: "Terms that the manifest does not point at" },
        );
        const empty = await fixturePackage(
            { license: "SEE LICENSE IN terms.md", name: "empty-reference", version: "1.0.0" },
            { "terms.md": "  \n" },
        );

        expect(() => readNotice(missing)).toThrow("Missing license text for missing-reference@1.0.0");
        expect(() => readNotice(empty)).toThrow("Missing license text for empty-reference@1.0.0");
    });

    it("orders packages by code unit instead of by machine collation", async () => {
        // Collation rules sort "_" before "-"; code units do the opposite, and
        // only code units give the same report on every machine.
        const underscore = await fixturePackage(
            { license: "MIT", name: "package_two", version: "1.0.0" },
            { LICENSE: "Terms" },
        );
        const hyphen = await fixturePackage(
            { license: "MIT", name: "package-one", version: "1.0.0" },
            { LICENSE: "Terms" },
        );

        expect(noticesForRoots([underscore, hyphen]).map(({ name }) => name)).toEqual(["package-one", "package_two"]);
    });
});
