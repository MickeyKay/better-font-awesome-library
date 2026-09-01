# Releasing BFAL

BFAL versions are published from repository tags and discovered automatically by Packagist. Do not add a `version` field to `composer.json`.

## Prepare and verify

When updating the inactive Font Awesome 7 fallback, select one exact official
npm version and regenerate it with:

```console
npm ci
npm run generate:font-awesome-7-fallback -- 7.3.1
npm run verify:font-awesome-7-fallback
git diff --exit-code inc/font-awesome-7-fallback
```

Run the generation command a second time and require an empty diff to verify
byte-identical output. The generator verifies the npm registry identity and
tarball integrity before extracting its fixed allowlist. The `scripts`
directory and root Node manifests are excluded from production archives.

Set the intended version and start from a clean checkout of its candidate commit:

```console
RELEASE_VERSION=2.1.0
git status --short
composer install --no-interaction --prefer-dist
composer validate --strict
composer audit
composer check
npm run build
npm run audit:runtime-assets
git diff --exit-code
```

Confirm that `Better_Font_Awesome_Library::VERSION`, both npm manifests, both npm lockfile root records, the compatibility test, and the changelog use the same release version. `composer.json` intentionally has no version field because Composer derives the package version from the tag.

## Build the production archive

Create both verification archives only from the unpushed local tag. The tracked `.gitattributes` rules exclude tests, agent files, development configuration, development locks, build sources, and release tooling. Runtime Composer dependencies belong in `composer.json`; BFAL currently has no runtime Composer dependencies and the archive must not contain `vendor`.

```console
RELEASE_VERSION=2.1.0
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
cat "$CHECKSUM_PATH"
unzip -l "$ARTIFACT_PATH"
```

The two archives must be byte-identical. Before publication, manually confirm the archive contains exactly 18 production files under the single `better-font-awesome-library-2.1.0/` root directory. Confirm every required runtime file, the internal version surfaces, and the final SHA-256 checksum. Confirm tests, agent files, development dependencies, build sources, `node_modules`, and `vendor` are absent.

## Publish

Recent stable BFAL releases use prefix-free, lightweight numeric tags. Release candidates also use prefix-free tags. Stable `2.1.0` promotes the accepted `2.1.0-rc.2` implementation without runtime changes beyond the version constant and resulting stylesheet cache key.

Before publication, confirm `Better_Font_Awesome_Library::VERSION` is `2.1.0` and that WordPress generates `?ver=2.1.0` for both BFAL Font Awesome stylesheet handles. The stable release must retain anonymous CORS mode on the exact `bfa-font-awesome` and `bfa-font-awesome-v4-shim` handles.

Better Font Awesome PR #52 passed manual release-candidate acceptance at exact BFA commit `3351b5e4c02aaf1694bdb7638cc663f398a5c7a4`. The accepted BFA candidate ZIP SHA-256 is `41e37852f70d1ee5d00cf3260a7da45e950f89755ee184e97a1a50a270333e15`.

After the release-preparation pull request is approved and merged:

1. Confirm the intended merged release commit and that the complete hosted PHP 7.4 through 8.4 and packaging matrix passed on that exact commit.
2. Create the lightweight `2.1.0` tag locally on the intended merge commit. Do not push it.
3. Build the production archive twice from that exact unpushed local tag using the commands above.
4. Confirm byte identity, all archive contents and exclusions, the single exact root directory, every version surface, and the final SHA-256 checksum. Create the `.sha256` file from the verified ZIP.
5. Confirm the locally tagged commit is exactly the intended merge commit:

   ```console
   test "$(git rev-parse '2.1.0^{commit}')" = "$(git rev-parse origin/master)"
   ```

6. Only after every local-tag verification passes, push the tag. Pushing the tag is the publication boundary because Packagist may discover it immediately. Checks performed after this push confirm publication state but cannot prevent publication.
7. Immediately create the GitHub release and attach both the verified ZIP and its `.sha256` checksum file.
8. Include the exact SHA-256 checksum directly in the GitHub release notes as well as in the attached checksum file.
9. Confirm that Packagist discovers stable `2.1.0`, points to the exact tagged merge commit, and that Composer installs the expected distribution contents.

Do not push the tag if the tagged commit, internal version, changelog, archive contents, archive root directory, byte comparison, or checksum record disagree.

## Roll back

BFAL 2.0.3 is the rollback release. Restore it in the consuming project and redeploy the updated lockfile:

```console
composer require mickey-kay/better-font-awesome-library:2.0.3 --with-all-dependencies
```
