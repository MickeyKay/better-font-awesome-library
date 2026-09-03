# Third-party notices

## Font Awesome Icon Picker 3.2.0

BFAL distributes the compiled CSS and JavaScript for
`fontawesome-iconpicker` 3.2.0 under `lib/fontawesome-iconpicker/dist`.

- Copyright: Javi Aguilar
- License: MIT
- Source: https://github.com/itsjavi/fontawesome-iconpicker
- Bundled license: `lib/fontawesome-iconpicker/LICENSE`

The unminified files in the same `dist` directories are the human-readable
maintained source corresponding to the minified runtime assets.

`runtime-assets/package.json` and its lockfile pin the shipped icon picker as a
production audit target even though the root Node dependency is used only by
the build. CI verifies that the build and audit locks select the same exact
version before checking the runtime asset source for published advisories.

## Font Awesome Free 5 metadata

BFAL bundles Font Awesome Free 5.14.0 release metadata in
`inc/fallback-release-data.json`. Font Awesome Free uses the following
licenses by asset category:

- Icons: CC BY 4.0
- Fonts: SIL OFL 1.1
- Code: MIT

License details: https://fontawesome.com/license/free

For the explicit Font Awesome 5 channel, BFAL bundles metadata only. Browser CSS
and webfonts are fetched from the Font Awesome Free v5 CDN when consumers
enable those existing BFAL features.

## Font Awesome Free 7.3.1 default fallback

BFAL packages the active default cold-start and recovery baseline generated from
the exact official npm release `@fortawesome/fontawesome-free@7.3.1`. Its
reduced Free metadata, four required minified stylesheets, and four WOFF2
webfonts are under `inc/font-awesome-7-fallback`.

- Package: `@fortawesome/fontawesome-free@7.3.1`
- Source: https://registry.npmjs.org/@fortawesome/fontawesome-free/-/fontawesome-free-7.3.1.tgz
- npm integrity: `sha512-wmglKKPDIkgV3aWlZzWECCPoGIkYCulzBwxG9+w7rc5BGapZ6cPMpoPOT8k36J0Ni7PPX6c/rsoMWfS4d1MUMg==`
- Icons: CC BY 4.0
- Fonts: SIL OFL 1.1
- Code: MIT
- Bundled license: `inc/font-awesome-7-fallback/LICENSE.txt`
- Reproducible provenance: `inc/font-awesome-7-fallback/provenance.json`

This baseline is selected by the default Font Awesome 7 production runtime and
retains its established lowest-precedence behavior. The Font Awesome 5 metadata
fallback remains available to explicit 5.x consumers.
