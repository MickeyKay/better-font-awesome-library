# Releasing BFAL

BFAL versions are published from repository tags and discovered automatically by Packagist. Do not add a `version` field to `composer.json`.

## Prepare and verify

Set the intended version and start from a clean checkout of its candidate commit:

```console
RELEASE_VERSION=2.1.0-rc.1
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

Create the archive only from the intended tag. The tracked `.gitattributes` rules exclude tests, agent files, development configuration, development locks, build sources, and release tooling. Runtime Composer dependencies belong in `composer.json`; BFAL currently has no runtime Composer dependencies and the archive must not contain `vendor`.

```console
RELEASE_VERSION=2.1.0-rc.1
ARTIFACT_PATH="better-font-awesome-library-${RELEASE_VERSION}.zip"
git archive \
  --format=zip \
  --prefix="better-font-awesome-library-${RELEASE_VERSION}/" \
  --output="$ARTIFACT_PATH" \
  "$RELEASE_VERSION"
shasum -a 256 "$ARTIFACT_PATH"
unzip -l "$ARTIFACT_PATH"
```

Running the same command twice from clean checkouts of the same tag must produce identical SHA-256 checksums. Manually inspect the archive before publication.

## Publish

Recent stable BFAL releases use prefix-free, lightweight numeric tags. Release candidates also use a prefix-free tag, with `2.1.0-rc.1` as the first 2.1.0 candidate.

After the release-preparation pull request is approved and merged:

1. Re-run the complete hosted PHP 7.4 through 8.4 matrix on the exact merge commit.
2. Create and push the lightweight `2.1.0-rc.1` tag on that verified commit.
3. Rebuild and inspect the archive from the tag, then record its SHA-256 checksum.
4. Create a prerelease GitHub release and attach the verified archive.
5. Confirm that Packagist discovers `2.1.0-rc.1` and that Composer installs the expected tag and dist contents.

Do not publish if the tag, internal version, changelog, archive root directory, or checksum record disagree.

## Roll back

BFAL 2.0.3 is the rollback release. Restore it in the consuming project and redeploy the updated lockfile:

```console
composer require mickey-kay/better-font-awesome-library:2.0.3 --with-all-dependencies
```
