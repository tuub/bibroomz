// Build the tarball a release publishes, check it, and print its path;
// everything else goes to standard error, so a caller can capture it. Both
// pipelines run this and then publish what it prints.
//
// The checks are the package's own pack.test.ts, which is handed the tarball
// through PACKAGE_TARBALL and only looks at it. A pipeline that sets
// PACKAGE_TAG also gets the tag checked against package.json.
//
//     node scripts/pack-package.ts <package directory>
import { execFileSync } from "node:child_process";
import { mkdirSync, readFileSync, rmSync } from "node:fs";
import * as path from "node:path";
import { fileURLToPath } from "node:url";
import { startVitest } from "vitest/node";

const repository = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");

const destination = path.join(repository, "build", "pack");

const directory = process.argv[2] ?? "";

if (directory === "") {
    process.stderr.write("Usage: node scripts/pack-package.ts <package directory>\n");
    process.exit(1);
}

const manifest = JSON.parse(readFileSync(path.join(repository, directory, "package.json"), "utf8")) as {
    name: string;
    version: string;
};

// Emptied first, so no tarball from an earlier version is left to publish.
rmSync(destination, { force: true, recursive: true });
mkdirSync(destination, { recursive: true });

process.stderr.write(`Packing ${manifest.name}@${manifest.version}.\n`);

// Packing runs prepack, which compiles the package. npm keeps standard output
// for the JSON and sends the lifecycle output to standard error.
const packed = execFileSync(
    "npm",
    ["pack", "--workspace", manifest.name, "--pack-destination", destination, "--json"],
    {
        cwd: repository,
        encoding: "utf8",
        stdio: ["ignore", "pipe", "inherit"],
    },
);

const filename = (JSON.parse(packed) as { filename: string }[])[0]?.filename;

if (filename === undefined) {
    process.stderr.write("npm pack reported no tarball.\n");
    process.exit(1);
}

const tarball = path.join(destination, filename);

process.env.PACKAGE_TARBALL = tarball;

// Vitest's report would go to standard output, which here carries the tarball
// path alone, so it is pointed at standard error with everything else. A
// failing test leaves process.exitCode set, which is the status to exit with.
const vitest = await startVitest("test", [`${directory}/pack.test.ts`], { root: repository, watch: false }, undefined, {
    stdout: process.stderr,
});

await vitest.close();

// A filter that matches nothing is no error to vitest, so a test file that
// moved would otherwise pass for a checked tarball.
if (vitest.state.getTestModules().length === 0) {
    process.stderr.write(`No test ran for ${directory}.\n`);
    process.exit(1);
}

if (process.exitCode !== undefined && process.exitCode !== 0) {
    process.exit(process.exitCode);
}

process.stdout.write(`${tarball}\n`);

// Vitest leaves its workers behind, which would keep the process running.
process.exit(0);
