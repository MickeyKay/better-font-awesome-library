# Releasing BFAL

BFAL versions are published from repository tags and discovered automatically by Packagist. Do not add a `version` field to `composer.json`.

Changing the default Font Awesome major is a public compatibility change. Publish it only through an appropriately versioned, separately reviewed BFAL release. Never publish a default-major change as a compatible update to the preceding BFAL release line.

Exact candidate commits, archive counts, checksums, and consumer acceptance evidence belong in the release-preparation pull request and release record. Keep this procedure parameterized so it remains valid for later releases.

## Update the bundled Font Awesome fallback

When updating the Font Awesome 7 fallback, select one exact approved official npm version:

```console
FONT_AWESOME_VERSION="<approved exact 7.x version>"
npm ci
npm run generate:font-awesome-7-fallback -- "$FONT_AWESOME_VERSION"
npm run verify:font-awesome-7-fallback
git diff --exit-code inc/font-awesome-7-fallback
```

Run the generation command a second time and require an empty diff to verify byte-identical output. The generator verifies npm registry identity and tarball integrity before extracting its fixed allowlist. The `scripts` directory and root Node manifests are excluded from production archives.

## Prepare and verify

Set the intended BFAL version and start from a clean checkout of its candidate commit:

```console
RELEASE_VERSION="<version>"
git status --short
composer install --no-interaction --prefer-dist
composer validate --strict
composer audit
composer check
npm run build
npm run audit:runtime-assets
npm run verify:font-awesome-7-fallback
git diff --exit-code
```

Confirm that `Better_Font_Awesome_Library::VERSION`, both npm manifests, both npm lockfile root records, the compatibility test, and the changelog use the intended release version. `composer.json` intentionally has no version field because Composer derives the package version from the tag.

## Build the production archive

Record the reviewed production file count in the release-preparation evidence, then create two verification archives only from the unpushed local tag. The tracked `.gitattributes` rules exclude tests, agent files, development configuration, development locks, build sources, and release tooling. Runtime Composer dependencies belong in `composer.json`. If BFAL has no runtime Composer dependencies, the archive must not contain `vendor`.

```console
RELEASE_VERSION="<version>"
EXPECTED_FILE_COUNT="<reviewed production file count>"
ARTIFACT_PATH="better-font-awesome-library-${RELEASE_VERSION}.zip"
VERIFICATION_ARTIFACT_PATH="better-font-awesome-library-${RELEASE_VERSION}-verification.zip"
CHECKSUM_PATH="better-font-awesome-library-${RELEASE_VERSION}.sha256"
git archive \
  --format=zip \
  --prefix="better-font-awesome-library-${RELEASE_VERSION}/" \
  --output="$ARTIFACT_PATH" \
  "$RELEASE_VERSION"
git archive \
  --format=zip \
  --prefix="better-font-awesome-library-${RELEASE_VERSION}/" \
  --output="$VERIFICATION_ARTIFACT_PATH" \
  "$RELEASE_VERSION"
cmp "$ARTIFACT_PATH" "$VERIFICATION_ARTIFACT_PATH"
shasum -a 256 "$ARTIFACT_PATH" > "$CHECKSUM_PATH"
ARCHIVE_FILE_COUNT="$(unzip -Z1 "$ARTIFACT_PATH" | awk '! /\/$/ { count++ } END { print count + 0 }')"
test "$ARCHIVE_FILE_COUNT" = "$EXPECTED_FILE_COUNT"
cat "$CHECKSUM_PATH"
unzip -l "$ARTIFACT_PATH"
```

The two archives must be byte-identical. Before publication, manually confirm the reviewed file count, the single exact versioned root directory, every required runtime file, all version surfaces, and the final SHA-256 checksum. Confirm tests, agent files, development dependencies, build sources, `node_modules`, and any excluded `vendor` directory are absent.

## Publish

Recent BFAL releases use prefix-free, lightweight numeric tags. Release candidates also use prefix-free tags.

After the release-preparation pull request is approved and merged:

1. Confirm the intended merged release commit and that the complete hosted compatibility and packaging matrix passed on that exact commit.
2. Create the lightweight release tag locally on the intended merge commit. Do not push it.
3. Build the production archive twice from that exact unpushed local tag using the commands above.
4. Confirm byte identity, all archive contents and exclusions, the single exact root directory, every version surface, and the final SHA-256 checksum.
5. Confirm the locally tagged commit is exactly the intended merge commit.
6. Only after every local-tag verification passes, push the tag. Pushing the tag is the publication boundary because Packagist may discover it immediately.
7. Create the GitHub release and attach both the verified ZIP and its `.sha256` checksum file.
8. Include the exact SHA-256 checksum in the GitHub release notes and attached checksum file.
9. Confirm Packagist discovers the intended version, points to the exact tagged commit, and installs the expected distribution contents.

Do not push the tag if the tagged commit, internal version, changelog, archive contents, archive root directory, byte comparison, or checksum record disagree.

## Roll back

Restore the last known-good BFAL version in the consuming project and redeploy the updated lockfile:

```console
composer require mickey-kay/better-font-awesome-library:"<last known-good version>" --with-all-dependencies
```
