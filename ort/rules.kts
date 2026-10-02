/**
 * What every dependency has to be licensed under, decided by what this project
 * does with it rather than by which lock file it came from:
 *
 *   conveyed -- what the frontend build compiles into a bundle every visitor
 *     downloads, together with this project's own sources, as one work offered
 *     under GPL-3.0-or-later. A dependency in here has to be combinable with
 *     that license.
 *
 *   operated -- everything else, which reaches nobody: it runs on a server
 *     here, or on a machine somebody develops on, and is never handed out.
 *     GPL compatibility is not the question there, only whether the license
 *     restricts running the thing at all.
 *
 * The Composer tree is operated whole. The npm tree splits on the scope a
 * package hangs off: what `dependencies` reaches is conveyed, what only
 * `devDependencies` reaches is tooling that runs here and stops here. Both
 * halves are judged, the dev half against the longer list.
 *
 * That split describes the dependency graph, not the bundle, so the build
 * checks the other end: the notices plugin in vite.config.ts fails `vite build`
 * when Rollup attributes output to a package the lock file marks dev-only. Its
 * includePackages option covers build transforms that put package source into
 * the output before Rollup can observe it; those packages live in
 * `dependencies` and are judged conveyed here.
 *
 * Two things the per-merge-request job does not establish. It stops at the
 * evaluator, so there is no detected-license data and a package that declares
 * MIT while shipping something else passes; the scheduled ort:scan job is what
 * closes that. And the npm analyzer installs the tree before walking it, so an
 * os/cpu-restricted package npm skips on this platform is never seen at all.
 */

/** Concluded first, declared otherwise, detected alongside once the scanner runs. */
val licenseView = LicenseView.CONCLUDED_OR_DECLARED_AND_DETECTED

val distributable = licenseClassifications.licensesByCategory["distributable"].orEmpty()

val operableOnly = licenseClassifications.licensesByCategory["operable-only"].orEmpty()

val operable = distributable + operableOnly

/**
 * Every package the npm `dependencies` scope reaches, however deep. ORT folds
 * optional and peer dependencies in, so this is wider than package.json's own
 * `dependencies` and errs the safe way: vite is a devDependency here and lands
 * in the set anyway, as a peer of laravel-vite-plugin.
 */
val conveyed = ortResult.getProjects(omitExcluded = true)
    .filter { it.id.type == "NPM" }
    .flatMapTo(mutableSetOf()) { ortResult.dependencyNavigator.scopeDependencies(it, "dependencies") }

fun PackageRule.isConveyed() =
    object : RuleMatcher {
        override val description = "isConveyed(${pkg.metadata.id.toCoordinates()})"

        override fun matches() = pkg.metadata.id in conveyed
    }

fun PackageRule.hasEffectiveLicense() =
    object : RuleMatcher {
        override val description = "hasEffectiveLicense(${pkg.metadata.id.toCoordinates()})"

        override fun matches() = getEffectiveLicense(licenseView) != null
    }

/**
 * Whether the package can be taken under licenses that are all in [allowed].
 *
 * A declaration is an expression, and its two operators mean opposite things:
 * `MIT OR GPL-2.0-only` may be taken under MIT, `MIT AND GPL-2.0-only` imposes
 * both. Judging each license on its own would fail a package dual-licensed for
 * exactly this reason.
 */
fun PackageRule.isLicensedUnder(allowed: Set<SpdxSingleLicenseExpression>) =
    object : RuleMatcher {
        override val description = "isLicensedUnder(${getEffectiveLicense(licenseView)})"

        override fun matches() =
            getEffectiveLicense(licenseView)?.validChoices().orEmpty().any { choice ->
                choice.decompose().all { it in allowed }
            }
    }

/**
 * A license in neither list is one nobody here has ruled on, which is why the
 * advice is to read the package rather than to add the license.
 */
fun howToFix() = """
    Open the package and read what it actually grants. If the license belongs
    in this project's policy, add it to ort/license-classifications.yml with a
    line saying why it is combinable. Otherwise replace the dependency, or, for
    an npm package only the build uses, move it into devDependencies so that
    it stops being something this project hands out.
""".trimIndent()

fun RuleSet.conveyedLicenseRule() = packageRule("CONVEYED_LICENSE") {
    require {
        +isConveyed()
        +hasEffectiveLicense()
        -isLicensedUnder(distributable)
    }

    error(
        "${pkg.metadata.id.toCoordinates()} ships to users inside the frontend bundle, which they receive " +
            "under GPL-3.0-or-later, and ${getEffectiveLicense(licenseView)} does not allow that.",
        howToFix(),
    )
}

fun RuleSet.operatedLicenseRule() = packageRule("OPERATED_LICENSE") {
    require {
        -isConveyed()
        +hasEffectiveLicense()
        -isLicensedUnder(operable)
    }

    error(
        "${pkg.metadata.id.toCoordinates()} never leaves this project's own machines, so GPL compatibility " +
            "is not the question, but ${getEffectiveLicense(licenseView)} does not allow even that.",
        howToFix(),
    )
}

/**
 * A package that declares nothing matches neither rule above, both needing an
 * expression to judge. An error of its own rather than a gap that passes
 * quietly.
 */
fun RuleSet.undeclaredLicenseRule() = packageRule("UNDECLARED_LICENSE") {
    require {
        -hasEffectiveLicense()
    }

    error(
        "${pkg.metadata.id.toCoordinates()} declares no license the analyzer could read, so neither rule " +
            "above has anything to judge it by. What it does declare is ${pkg.metadata.declaredLicenses}.",
        "Read the package's own LICENSE file and record what it says in .ort.yml under curations.packages, as " +
            "a concluded_license with a comment naming the file it was read from. The rules above judge that " +
            "concluded license like any other, so the package still has to be under an allowed one. Where the " +
            "declaration is there but written in a form ORT does not recognise, a declared_license_mapping in " +
            "the same file is the smaller fix.",
    )
}

/**
 * The conveyed set is a lookup by scope name. A name that stops matching would
 * fail nothing: it would move the whole npm tree into the operated rule and the
 * job would go green having stopped asking the question it exists to ask.
 */
fun RuleSet.conveyedScopeRule() = ortResultRule("CONVEYED_SCOPE") {
    if (ortResult.getProjects(omitExcluded = true).any { it.id.type == "NPM" } && conveyed.isEmpty()) {
        error(
            "The npm `dependencies` scope resolved to no packages at all, so every npm package would be " +
                "judged as something this project only runs. Nothing this project ships would be checked.",
            "Look at the scope names in ort-results/analyzer-result.yml under projects[].scopeNames and use " +
                "the one npm's production dependencies now hang off in ort/rules.kts.",
        )
    }
}

val ruleSet = ruleSet(ortResult, licenseInfoResolver, resolutionProvider) {
    conveyedScopeRule()
    conveyedLicenseRule()
    operatedLicenseRule()
    undeclaredLicenseRule()
}

ruleViolations += ruleSet.violations
