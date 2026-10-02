// @vitest-environment node
import { execFileSync } from "node:child_process";
import { existsSync, mkdtempSync, readFileSync, readdirSync, realpathSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import * as path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

// scripts/pack-package.ts builds the tarball a release publishes and hands it
// here, so these see the very bytes that go to the registries. Without one
// there is nothing to look at, and an ordinary test run skips them.
const tarball = process.env.PACKAGE_TARBALL ?? "";

// Set by a pipeline a tag started, so a tag naming a version the repository
// does not can be refused before anything is published.
const tag = process.env.PACKAGE_TAG ?? "";

const packageDirectory = path.dirname(fileURLToPath(import.meta.url));

const manifest = JSON.parse(readFileSync(path.join(packageDirectory, "package.json"), "utf8")) as {
    exports: { ".": { import: string; types: string } };
    name: string;
    version: string;
};

// Run in a plain Node process, not Vitest's module runner: what matters is
// what a consumer's Node makes of the files, and a bundler's resolver is more
// forgiving.
const loader = `
const api = await import(process.env.PACK_CHECK_PACKAGE);
const plugin = api.thirdPartyNotices();

let emitted = null;

await plugin.generateBundle.call(
    {
        emitFile(file) {
            emitted = file;
            return "asset-id";
        },
        error(error) {
            throw new Error(String(error));
        },
    },
    {},
    {},
    false,
);

process.stdout.write(
    JSON.stringify({
        apply: plugin.apply,
        emitted,
        entry: import.meta.resolve(process.env.PACK_CHECK_PACKAGE),
        exports: Object.keys(api).sort(),
        name: plugin.name,
    }),
);
`;

interface Loaded {
    apply: string;
    emitted: { fileName: string; source: string; type: string } | null;
    entry: string;
    exports: string[];
    name: string;
}

/**
 * Every file of an installed package, relative to it and in a stable order.
 */
function installedFiles(root: string, prefix = ""): string[] {
    return readdirSync(path.join(root, prefix), { withFileTypes: true })
        .flatMap((entry) =>
            entry.isDirectory()
                ? installedFiles(root, path.join(prefix, entry.name))
                : [path.join(prefix, entry.name).split(path.sep).join("/")],
        )
        .sort();
}

describe.runIf(tarball !== "")("the tarball a release publishes", () => {
    let consumer = "";
    let root = "";

    beforeAll(() => {
        consumer = realpathSync(mkdtempSync(path.join(tmpdir(), "vite-plugin-third-party-notices-pack-")));
        root = path.join(consumer, "node_modules", ...manifest.name.split("/"));
        writeFileSync(
            path.join(consumer, "package.json"),
            `${JSON.stringify({ name: "pack-check", private: true, type: "module" })}\n`,
        );
        writeFileSync(path.join(consumer, "load.js"), loader);

        // --omit=peer leaves vite uninstalled: the package names it as a
        // peer dependency and must not reach for it at run time.
        execFileSync("npm", ["install", "--ignore-scripts", "--no-audit", "--no-fund", "--omit=peer", tarball], {
            cwd: consumer,
            stdio: ["ignore", "ignore", "inherit"],
        });
    }, 180_000);

    afterAll(() => {
        if (consumer !== "") {
            rmSync(consumer, { force: true, recursive: true });
        }
    });

    it.runIf(tag !== "")("carries the version its tag names", () => {
        expect(tag).toBe(`${path.basename(packageDirectory)}@${manifest.version}`);
    });

    it("ships exactly the files it promises and nothing else", () => {
        expect(installedFiles(root)).toEqual([
            "LICENSE",
            "README.md",
            "dist/index.d.ts",
            "dist/index.d.ts.map",
            "dist/index.js",
            "dist/index.js.map",
            "package.json",
            "src/index.ts",
        ]);
    });

    it("points its source map back at the source it ships", () => {
        const map = JSON.parse(readFileSync(path.join(root, "dist", "index.js.map"), "utf8")) as { sources: string[] };

        expect(map.sources).toEqual(["../src/index.ts"]);
        expect(existsSync(path.resolve(root, "dist", "../src/index.ts"))).toBe(true);
    });

    it("imports in a Node process that has no vite installed, and emits a report", () => {
        const loaded = JSON.parse(
            execFileSync(process.execPath, ["load.js"], {
                cwd: consumer,
                encoding: "utf8",
                env: { ...process.env, PACK_CHECK_PACKAGE: manifest.name },
                stdio: ["ignore", "pipe", "inherit"],
            }),
        ) as Loaded;

        expect(loaded.entry).toBe(pathToFileURL(path.join(root, manifest.exports["."].import)).href);
        expect(loaded.exports).toEqual([
            "assetCreditsCheck",
            "cssImportCheck",
            "formatLicenseReport",
            "modulePath",
            "noticesForRoots",
            "npmPackageLockCheck",
            "packageRoot",
            "packageRoots",
            "packageSpecifier",
            "readNotice",
            "thirdPartyNotices",
            "unattributedModuleCheck",
        ]);
        expect(loaded.name).toBe("third-party-notices");
        expect(loaded.apply).toBe("build");
        expect(loaded.emitted).toMatchObject({ fileName: "third-party-notices.txt", type: "asset" });
        expect(typeof loaded.emitted?.source).toBe("string");
    });
});
