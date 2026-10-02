import * as fs from "node:fs";
import { createRequire } from "node:module";
import * as path from "node:path";
import process from "node:process";
// A whole-clause type import rather than inline type specifiers, because
// verbatimModuleSyntax keeps the latter as a bare "vite" import in the emit,
// and nothing this package ships should load its peer at runtime.
import type { Plugin, Rollup } from "vite";

export type LegalText = {
    file: string;
    text: string;
};

export type Notice = {
    name: string;
    version: string;
    license: string;
    repository: string | null;
    licenseTexts: LegalText[];
    noticeTexts: LegalText[];
};

export type PackageResolver = (name: string, cwd: string) => string;

export type PackageRootsOptions = {
    cwd?: string;
    includePackages?: readonly string[];
    resolvePackage?: PackageResolver;
};

export type NoticeContext = {
    bundle: Rollup.OutputBundle;
    cwd: string;
    includePackages?: readonly string[];
    notices: readonly Notice[];
    resolvePackage?: PackageResolver;
    roots: ReadonlySet<string>;
};

export type NoticeCheck = (context: NoticeContext) => null | string | void | Promise<null | string | void>;

export type NoticeRenderer = (notices: readonly Notice[]) => string | Promise<string>;

export type ThirdPartyNoticesOptions = PackageRootsOptions & {
    checks?: readonly NoticeCheck[];
    fileName?: string;
    render?: NoticeRenderer;
};

export type AssetCreditsCheckOptions = {
    assetPattern?: RegExp;
    creditsFile: string;
};

export type NpmPackageLockCheckOptions = {
    lockFile?: string;
};

/**
 * The fields this plugin reads from a package.json. Every one of them arrives
 * as whatever JSON.parse returned, so none of them can be trusted to be there
 * or to have the type the specification asks for.
 */
type Manifest = {
    license?: unknown;
    licenses?: unknown;
    name?: unknown;
    repository?: unknown;
    version?: unknown;
};

type StylesheetReference = {
    importer: string;
    root: string;
};

type UnresolvedReference = {
    importer: string;
    specifier: string;
};

type ScanState = {
    credited: Set<string>;
    cwd: string;
    referenced: Map<string, StylesheetReference>;
    resolver: PackageResolver;
    unresolved: UnresolvedReference[];
    visited: Set<string>;
};

const STYLE_FILE = /\.(css|less|sass|scss|styl)(\?|$)/i;
const ASSET_FILE = /\.(avif|eot|gif|jpe?g|otf|png|svg|ttf|webp|woff2?)$/i;
const CSS_REFERENCE = /@(?:import|plugin|config|reference)\s+(?:url\(\s*)?["']([^"']*)["']/g;
const URL_SCHEME = /^[a-z][a-z\d+.-]*:/i;

export function thirdPartyNotices({
    checks = [],
    cwd = process.cwd(),
    fileName = "third-party-notices.txt",
    includePackages = [],
    render = formatLicenseReport,
    resolvePackage,
}: ThirdPartyNoticesOptions = {}): Plugin {
    if (fileName.trim() === "") {
        throw new Error("The third-party notices filename must not be empty.");
    }

    return {
        name: "third-party-notices",
        apply: "build",
        async generateBundle(_options, bundle) {
            const resolver = resolvePackage ?? defaultPackageResolver;
            const roots = packageRoots(bundle, { cwd, includePackages, resolvePackage: resolver });
            const notices = noticesForRoots(roots);
            const context: NoticeContext = {
                bundle,
                cwd,
                includePackages,
                notices,
                resolvePackage: resolver,
                roots,
            };

            for (const check of checks) {
                const error = await check(context);

                if (typeof error === "string") {
                    this.error(error);
                }
            }

            this.emitFile({
                type: "asset",
                fileName,
                source: await render(notices),
            });
        },
    };
}

/**
 * Return the package directories represented in a completed Rollup bundle.
 * Packages whose output is hidden by a pre-Rollup transform can be named in
 * includePackages.
 */
export function packageRoots(
    bundle: Rollup.OutputBundle,
    { cwd = process.cwd(), includePackages = [], resolvePackage }: PackageRootsOptions = {},
): Set<string> {
    const resolver = resolvePackage ?? defaultPackageResolver;
    const roots = new Set(includePackages.map((name) => resolver(name, cwd)));

    for (const id of shippedModules(bundle)) {
        const root = packageRoot(id);

        if (root !== null) {
            roots.add(root);
        }
    }

    return roots;
}

/**
 * Yield the id of every module a chunk still carries. Tree-shaken modules are
 * left out, except for stylesheets, whose contribution Rollup does not weigh.
 */
function* shippedModules(bundle: Rollup.OutputBundle): Generator<string> {
    for (const chunk of Object.values(bundle)) {
        if (chunk.type !== "chunk") {
            continue;
        }

        for (const [id, module] of Object.entries(chunk.modules)) {
            if (module.renderedLength === 0 && !STYLE_FILE.test(id)) {
                continue;
            }

            yield id;
        }
    }
}

function defaultPackageResolver(name: string, cwd: string): string {
    const require = createRequire(path.join(path.resolve(cwd), "package.json"));

    // Resolved from the node_modules chain rather than from the package's own
    // entry points, because an exports map may well keep package.json private.
    for (const directory of require.resolve.paths(name) ?? []) {
        const candidate = path.join(directory, ...name.split("/"));

        if (fs.existsSync(path.join(candidate, "package.json"))) {
            return candidate;
        }
    }

    throw new Error(
        `Cannot find the package ${name} below ${path.resolve(cwd)}. A package installed under ` +
            `another package, or any layout npm does not use, needs an explicit resolvePackage.`,
    );
}

/**
 * Return the package directory encoded in a Vite/Rollup module id.
 */
export function packageRoot(id: string): string | null {
    const modules = "node_modules/";
    const [file = ""] = id.replaceAll("\\", "/").split("?");
    const withoutPrefix = file.slice(file.lastIndexOf("\0") + 1);
    const marker = withoutPrefix.lastIndexOf(modules);

    if (marker === -1) {
        return null;
    }

    const segments = withoutPrefix.slice(marker + modules.length).split("/");
    const name = segments[0]?.startsWith("@") ? segments.slice(0, 2).join("/") : segments[0];

    if (!name) {
        return null;
    }

    return withoutPrefix.slice(0, marker + modules.length) + name;
}

/**
 * Reject packages that npm records as dev-only, or that are absent from its
 * path-keyed package-lock graph.
 */
export function npmPackageLockCheck({ lockFile = "package-lock.json" }: NpmPackageLockCheckOptions = {}): NoticeCheck {
    return ({ cwd, roots }) => {
        const parsed: unknown = JSON.parse(fs.readFileSync(path.resolve(cwd, lockFile), "utf8"));
        const packages = record(record(parsed).packages);
        const shipped: string[] = [];
        const unknown: string[] = [];

        for (const root of roots) {
            const key = path.relative(cwd, root).split(path.sep).join("/");
            const entry = packages[key];

            if (entry === undefined) {
                unknown.push(key);
            } else if (record(entry).dev === true) {
                shipped.push(`${packageName(root)} (${key})`);
            }
        }

        if (shipped.length === 0 && unknown.length === 0) {
            return null;
        }

        return [
            ...shipped.map((name) => `${name} is a devDependency and the build put its code into its output.`),
            ...unknown.map((key) => `${key} is in the bundle and ${lockFile} has no entry for it.`),
            "",
            "Move the package into dependencies in package.json so dependency policy treats it",
            "as something this project distributes, or stop the build from pulling it in. A",
            `missing entry in ${lockFile} means this check cannot distinguish the two cases.`,
        ].join("\n");
    };
}

/**
 * Keep a JSON list of credited package names aligned with packages that ship
 * recognisable asset files into the final bundle.
 */
export function assetCreditsCheck({ assetPattern = ASSET_FILE, creditsFile }: AssetCreditsCheckOptions): NoticeCheck {
    if (typeof creditsFile !== "string" || creditsFile.trim() === "") {
        throw new Error("The asset credits filename must not be empty.");
    }

    return ({ bundle, cwd }) => {
        const shipped = assetPackages(bundle, assetPattern);
        const credited = creditedPackages(path.resolve(cwd, creditsFile));
        const missing = [...shipped].filter((name) => !credited.has(name));
        const stale = [...credited].filter((name) => !shipped.has(name));

        if (missing.length === 0 && stale.length === 0) {
            return null;
        }

        return [
            ...missing.map((name) => `${name} ships a file of its own into the bundle and nothing credits it.`),
            ...stale.map((name) => `${name} is credited and ships nothing.`),
            "",
            `Update ${creditsFile} so it names exactly the packages whose assets the bundle serves.`,
        ].join("\n");
    };
}

function assetPackages(bundle: Rollup.OutputBundle, assetPattern: RegExp): Set<string> {
    const names = new Set<string>();

    for (const output of Object.values(bundle)) {
        if (output.type !== "asset") {
            continue;
        }

        for (const source of output.originalFileNames ?? []) {
            assetPattern.lastIndex = 0;
            const root = assetPattern.test(source) ? packageRoot(source) : null;
            assetPattern.lastIndex = 0;

            if (root !== null) {
                names.add(packageName(root));
            }
        }
    }

    return names;
}

function creditedPackages(file: string): Set<string> {
    const parsed: unknown = JSON.parse(fs.readFileSync(file, "utf8"));
    const entries: unknown[] = Array.isArray(parsed) ? parsed : [];
    const names = new Set<string>();

    for (const entry of entries) {
        const name = record(entry).package;

        if (typeof name === "string") {
            names.add(name);
        }
    }

    return names;
}

function packageName(root: string): string {
    const modules = "node_modules/";
    const normalized = root.replaceAll("\\", "/");

    return normalized.slice(normalized.lastIndexOf(modules) + modules.length);
}

/**
 * Keep includePackages aligned with the packages that bundled stylesheets pull
 * in through directives Rollup never resolves, such as @import and @plugin.
 */
export function cssImportCheck(): NoticeCheck {
    return ({ bundle, cwd, includePackages = [], resolvePackage, roots }) => {
        const state: ScanState = {
            credited: new Set([...roots].map(realPath)),
            cwd,
            referenced: new Map(),
            resolver: resolvePackage ?? defaultPackageResolver,
            unresolved: [],
            visited: new Set(),
        };

        for (const id of shippedModules(bundle)) {
            if (STYLE_FILE.test(id)) {
                scanStylesheet(modulePath(id), state);
            }
        }

        const missing = [...state.referenced]
            .filter(([, reference]) => !state.credited.has(realPath(reference.root)))
            .map(
                ([name, reference]) =>
                    `${name} is referenced by ${relative(cwd, reference.importer)} and nothing credits it.`,
            );
        const unresolved = state.unresolved.map(
            ({ importer, specifier }) =>
                `${specifier} is referenced by ${relative(cwd, importer)} and does not resolve to a package.`,
        );
        const stale = [...includePackages]
            .filter((name) => !state.referenced.has(name))
            .map((name) => `${name} is named in includePackages and no bundled stylesheet references it.`);

        if (missing.length === 0 && unresolved.length === 0 && stale.length === 0) {
            return null;
        }

        return [
            ...missing,
            ...unresolved,
            ...stale,
            "",
            "Stylesheet directives are resolved before Rollup can record where their output came",
            "from, so the packages behind them reach the bundle unseen. Name each one in",
            "includePackages, and drop the names no stylesheet reaches any more.",
        ].join("\n");
    };
}

function scanStylesheet(file: string | null, state: ScanState): void {
    if (file === null || state.visited.has(file)) {
        return;
    }

    state.visited.add(file);

    let source: string;

    try {
        source = fs.readFileSync(file, "utf8");
    } catch {
        return;
    }

    for (const [, specifier = ""] of source.matchAll(CSS_REFERENCE)) {
        // A leading tilde is the historical way to spell a bare specifier.
        const target = specifier.startsWith("~") ? specifier.slice(1) : specifier;

        if (target === "" || URL_SCHEME.test(target) || target.startsWith("/")) {
            continue;
        }

        if (target.startsWith(".")) {
            scanStylesheet(stylesheetAt(path.resolve(path.dirname(file), target)), state);
            continue;
        }

        const name = packageSpecifier(target);

        if (name === null) {
            continue;
        }

        let root: string;

        try {
            root = state.resolver(name, state.cwd);
        } catch {
            state.unresolved.push({ importer: file, specifier: target });
            continue;
        }

        if (!state.referenced.has(name)) {
            state.referenced.set(name, { importer: file, root });
        }

        const subpath = target.slice(name.length).replace(/^\//, "");

        if (subpath !== "") {
            scanStylesheet(stylesheetAt(path.join(root, subpath)), state);
        }
    }
}

/**
 * Return the package name a bare module specifier starts with.
 */
export function packageSpecifier(specifier: string): string | null {
    const [first, second] = specifier.split("/");

    if (!first || first === "." || first === "..") {
        return null;
    }

    // A scope on its own names no package, and neither does Vite's "@/" alias.
    if (first.startsWith("@")) {
        return first.length > 1 && second ? `${first}/${second}` : null;
    }

    return first;
}

function stylesheetAt(file: string): string | null {
    if (!STYLE_FILE.test(file)) {
        return null;
    }

    try {
        return fs.statSync(file).isFile() ? file : null;
    } catch {
        return null;
    }
}

/**
 * Report bundled code that no package root accounts for, such as a linked
 * dependency resolving outside both node_modules and the project.
 */
export function unattributedModuleCheck(): NoticeCheck {
    return ({ bundle, cwd }) => {
        const root = path.resolve(cwd);
        const foreign = new Set<string>();

        for (const id of shippedModules(bundle)) {
            const file = packageRoot(id) === null ? modulePath(id) : null;

            if (file !== null && outside(root, file)) {
                foreign.add(nearestPackageDirectory(file));
            }
        }

        if (foreign.size === 0) {
            return null;
        }

        return [
            ...[...foreign]
                .sort(compare)
                .map((directory) => `${directory} is in the bundle and no package root covers it.`),
            "",
            "Code from outside node_modules and outside the project reaches the bundle with no",
            "package metadata to report. Name its package in includePackages, teach the build a",
            "resolvePackage that finds it, or move it into the project so it ships under the",
            "project's own license.",
        ].join("\n");
    };
}

function nearestPackageDirectory(file: string): string {
    let directory = path.dirname(file);

    for (;;) {
        if (fs.existsSync(path.join(directory, "package.json"))) {
            return directory;
        }

        const parent = path.dirname(directory);

        if (parent === directory) {
            return file;
        }

        directory = parent;
    }
}

/**
 * Return the file a Vite/Rollup module id points at, or null for a module that
 * no file backs, such as a virtual one.
 */
export function modulePath(id: string): string | null {
    const [file = ""] = id.replaceAll("\\", "/").split("?");
    const withoutPrefix = file.slice(file.lastIndexOf("\0") + 1);

    return path.isAbsolute(withoutPrefix) ? withoutPrefix : null;
}

function realPath(file: string): string {
    try {
        return fs.realpathSync(file);
    } catch {
        return file;
    }
}

function relative(cwd: string, file: string): string {
    const inside = path.relative(path.resolve(cwd), file);

    return inside === "" || outside(path.resolve(cwd), file) ? file : inside;
}

export function noticesForRoots(roots: Iterable<string>): Notice[] {
    const notices: Notice[] = [];
    const failures: Error[] = [];

    for (const root of roots) {
        try {
            notices.push(readNotice(root));
        } catch (error) {
            failures.push(error instanceof Error ? error : new Error(String(error)));
        }
    }

    if (failures.length > 0) {
        throw new Error(failures.map((failure) => failure.message).join("\n"), {
            cause: new AggregateError(failures, "Cannot read every bundled package"),
        });
    }

    const unique = new Map(notices.map((notice) => [`${notice.name}@${notice.version}`, notice]));

    return [...unique.values()].sort((first, second) =>
        compare(`${first.name}@${first.version}`, `${second.name}@${second.version}`),
    );
}

/**
 * Order strings by code unit, so a report does not change with the collation
 * rules of the machine that builds it.
 */
function compare(first: string, second: string): number {
    return first < second ? -1 : first > second ? 1 : 0;
}

export function readNotice(root: string): Notice {
    let manifest: Manifest;

    try {
        manifest = record(JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8")));
    } catch (error) {
        throw new Error(`Cannot read package metadata from ${root}`, { cause: error });
    }

    if (
        typeof manifest.name !== "string" ||
        manifest.name === "" ||
        typeof manifest.version !== "string" ||
        manifest.version === ""
    ) {
        throw new Error(`Incomplete package metadata in ${root}`);
    }

    const license = declaredLicense(manifest, manifest.name, manifest.version);
    let licenseTexts = legalTexts(root, /^(licen[cs]e|copying)(?:$|[._-])/i);

    if (license.startsWith("SEE LICENSE IN ")) {
        const referenced = referencedLicense(
            root,
            license.slice("SEE LICENSE IN ".length),
            manifest.name,
            manifest.version,
        );
        const referencedPath = path.resolve(root, referenced.file);

        licenseTexts = [referenced, ...licenseTexts.filter((text) => path.resolve(root, text.file) !== referencedPath)];
    }

    if (licenseTexts.length === 0) {
        throw new Error(`Missing license text for ${manifest.name}@${manifest.version}`);
    }

    return {
        name: manifest.name,
        version: manifest.version,
        license,
        repository: repositoryUrl(manifest.repository),
        licenseTexts,
        noticeTexts: legalTexts(root, /^notice(?:$|[._-])/i),
    };
}

function declaredLicense(manifest: Manifest, name: string, version: string): string {
    const named = (value: unknown): string | null => {
        if (typeof value === "string") {
            return value;
        }

        const type = record(value).type;

        return typeof type === "string" ? type : null;
    };

    const single = named(manifest.license);

    if (single !== null) {
        return single;
    }

    if (typeof manifest.licenses === "string") {
        return manifest.licenses;
    }

    const list = Array.isArray(manifest.licenses)
        ? (manifest.licenses as unknown[]).map(named).filter((license) => license !== null)
        : [];

    if (list.length > 0) {
        return list.join(" OR ");
    }

    throw new Error(`Missing license identifier for ${name}@${version}`);
}

function repositoryUrl(repository: unknown): string | null {
    if (typeof repository === "string") {
        return repository;
    }

    const url = record(repository).url;

    return typeof url === "string" ? url : null;
}

function referencedLicense(root: string, file: string, name: string, version: string): LegalText {
    const packagePath = fs.realpathSync(root);
    const licensePath = path.resolve(packagePath, file);
    const relativePath = path.relative(packagePath, licensePath);

    if (outside(packagePath, licensePath)) {
        throw new Error(`License file escapes package directory for ${name}`);
    }

    let realLicensePath: string;

    try {
        realLicensePath = fs.realpathSync(licensePath);
    } catch (error) {
        throw new Error(`Missing license text for ${name}@${version}`, { cause: error });
    }

    if (outside(packagePath, realLicensePath)) {
        throw new Error(`License file escapes package directory for ${name}`);
    }

    const text = fs.readFileSync(realLicensePath, "utf8").trim();

    if (text === "") {
        throw new Error(`Missing license text for ${name}@${version}`);
    }

    return { file: relativePath.split(path.sep).join("/"), text };
}

function outside(root: string, target: string): boolean {
    const relative = path.relative(root, target);

    return relative === ".." || relative.startsWith(`..${path.sep}`) || path.isAbsolute(relative);
}

function legalTexts(root: string, pattern: RegExp): LegalText[] {
    let entries: fs.Dirent[];

    try {
        entries = fs.readdirSync(root, { withFileTypes: true });
    } catch (error) {
        throw new Error(`Cannot read package directory ${root}`, { cause: error });
    }

    return entries
        .filter((entry) => entry.isFile() && pattern.test(entry.name))
        .sort((first, second) => compare(first.name, second.name))
        .map((entry) => ({ file: entry.name, text: fs.readFileSync(path.join(root, entry.name), "utf8").trim() }))
        .filter(({ text }) => text !== "");
}

export function formatLicenseReport(notices: readonly Notice[]): string {
    return `${notices
        .map(({ license, licenseTexts, name, noticeTexts, repository, version }) =>
            [
                `Name: ${name}`,
                `Version: ${version}`,
                `License: ${license}`,
                ...(repository === null ? [] : [`Repository: ${repository}`]),
                ...licenseTexts.flatMap(({ file, text }) => [`License Text (${file}):`, "===", "", text]),
                ...noticeTexts.flatMap(({ file, text }) => [`Notice Text (${file}):`, "===", "", text]),
            ].join("\n"),
        )
        .join("\n\n---\n\n")}\n`;
}

/**
 * Narrow a value parsed from JSON to something whose fields can be read, so
 * that reading one is a type error away from assuming anything about it.
 */
function record(value: unknown): Record<string, unknown> {
    return typeof value === "object" && value !== null ? (value as Record<string, unknown>) : {};
}
