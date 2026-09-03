# Changelog

## 3.0.1

- Treat an exact empty array from `release_data_provider` as a normal indication that no local candidate is available, without recording a provider validation error.
- Continue resolving the established transient or bundled fallback after that sentinel and requesting asynchronous refresh when fallback is selected.
- Preserve validation and diagnostics for every other provider result, including malformed data, channel mismatches, and `WP_Error` values.
- Correct the third-party notice to identify Font Awesome Free 7.3.1 as the active default fallback and Font Awesome Free 5.14.0 as the fallback for explicit 5.x consumers.
- Update BFAL stylesheet and script cache keys to `3.0.1` without changing editor behavior or packaged runtime assets.

## 3.0.0

- Promoted the accepted 3.0.0-rc.1 implementation to stable without production behavior changes beyond the BFAL version identity and resulting asset cache keys.
- Made Font Awesome 7 the stable default while preserving explicit `release_channel => '5.x'` behavior, the first-caller singleton contract, existing public APIs, and channel-specific asset and metadata precedence.
- Preserved the no-HTTP ordinary-request contract. Metadata transport remains limited to explicit asynchronous refresh work, and BFAL continues not to own WordPress persistence, scheduling, locking, retry, freshness, or migration policy.
- Kept the packaged Font Awesome Free 7.3.1 fallback unchanged from the accepted release candidate.
- Updated BFAL stylesheet and script cache keys from `3.0.0-rc.1` to `3.0.0`.

To install the stable release after its tag is published:

```console
composer require mickey-kay/better-font-awesome-library:3.0.0
```

To roll back to the stable Font Awesome 5 release:

```console
composer require mickey-kay/better-font-awesome-library:2.1.0 --with-all-dependencies
```

## 3.0.0-rc.1

- Prepared the BFAL 3 next-major release candidate for integration testing. BFAL now defaults to Font Awesome 7 Free when the first caller omits `release_channel`; no channel argument is required for the default.
- Added explicit `release_channel => '5.x'` selection for consumers that need the legacy Font Awesome 5 channel. The first caller owns the selected channel for the singleton lifetime, and later callers cannot change it.
- Made the packaged and verified Font Awesome Free 7.3.1 CSS, WOFF2, metadata, licensing, attribution, and provenance the immediate default baseline. Activation and ordinary frontend, admin, editor, REST, shortcode, picker, and getter paths require no metadata HTTP, cron run, migration, or delayed activation.
- Added a bounded explicit 7.x background refresh operation that follows only newer, completely validated Font Awesome 7 Free releases. BFAL does not persist refresh results or own consumer scheduling, cron, locking, retry, backoff, freshness, or migration policy, and it will not select Font Awesome 8 automatically.
- Preserved the existing public API, shortcode output, stylesheet behavior, editor compatibility, optional v4 compatibility, first-caller singleton contract, and explicit Font Awesome 5 behavior. The BFAL version change updates WordPress stylesheet and script cache keys to `3.0.0-rc.1`.
- Kept BFAL 2.1.0 as the stable rollback release. Better Font Awesome integration and browser acceptance remain pending and are required before BFAL 3.0.0 stable publication.

To install this release candidate after its tag is published:

```console
composer require mickey-kay/better-font-awesome-library:3.0.0-rc.1
```

To roll back to the stable Font Awesome 5 release:

```console
composer require mickey-kay/better-font-awesome-library:2.1.0 --with-all-dependencies
```

## 2.1.0

- Promoted the accepted 2.1.0-rc.2 implementation to stable with validated live Font Awesome Free 5.x metadata and no metadata HTTP on ordinary frontend, admin, editor, REST, or other request paths.
- Preserved BFAL's asynchronous refresh contract and WordPress-owned orchestration in the Better Font Awesome consumer, including durable last-known-good storage, retry, locking, migration, and multisite behavior. BFAL retains its checksummed bundled fallback.
- Preserved normal static Font Awesome stylesheet registration with anonymous CORS mode on BFAL's exact main and optional v4 shim handles. Block Editor, Classic Editor, hybrid `wp_editor()` screens, the TinyMCE picker, frontend rendering, and v4 compatibility remain supported.
- Kept the validated provider contract, first-caller singleton ownership, live Font Awesome CDN architecture, PHP 7.4 compatibility, and public API unchanged from rc.2.
- Recorded manual release-candidate acceptance of Better Font Awesome PR #52 at commit `3351b5e4c02aaf1694bdb7638cc663f398a5c7a4`. The accepted BFA candidate ZIP SHA-256 is `41e37852f70d1ee5d00cf3260a7da45e950f89755ee184e97a1a50a270333e15`.

To install the stable release after its tag is published:

```console
composer require mickey-kay/better-font-awesome-library:2.1.0
```

To roll back to the previous stable release:

```console
composer require mickey-kay/better-font-awesome-library:2.0.3 --with-all-dependencies
```

## 2.1.0-rc.2

- Restored BFAL's normal static Font Awesome enqueue in the parent document, including WordPress 7.1 Block Editor screens that also contain traditional `wp_editor()` instances.
- Added consistent anonymous CORS mode to the exact `bfa-font-awesome` and `bfa-font-awesome-v4-shim` stylesheet links. This keeps CDN responses usable by WordPress's isolated editor processing while preserving picker glyphs, TinyMCE styling, frontend CSS, and optional v4 compatibility.
- Superseded the rc.1 Block Editor suppression behavior. The rc.2 runtime version produces distinct `?ver=2.1.0-rc.2` stylesheet URLs so incompatible one-year rc.1 browser cache entries cannot be reused.
- Preserved the validated live Font Awesome Free 5.x metadata architecture, provider and fallback precedence, transport and integrity safeguards, first-caller singleton ownership, PHP 7.4 support, and existing public API.
- Kept WordPress's normal enqueue lifecycle and the existing Font Awesome CDN. This candidate contains no delayed JavaScript stylesheet loader, DOM polling, event replay, bundled versioned Font Awesome CSS, or CDN change.

To test the candidate after its tag is published:

```console
composer require mickey-kay/better-font-awesome-library:2.1.0-rc.2
```

To roll back to the previous stable release:

```console
composer require mickey-kay/better-font-awesome-library:2.0.3 --with-all-dependencies
```

## 2.1.0-rc.1

- Added a validated Font Awesome Free 5.x provider and release-record contract. Normal frontend, admin, editor, REST, and other request paths resolve only local provider, transient, or bundled fallback data.
- Restricted remote metadata transport to the explicit asynchronous worker operation. Requests verify TLS, reject unsafe URLs and redirects, enforce bounded time and response size, and expose sanitized failures.
- Added full release-record, asset, SRI, and fallback checksum validation before metadata is adopted.
- Preserved the `bfa-release-data` transient shape and added deterministic provider, transient, and checksummed fallback precedence. Consumers continue to own durable storage, freshness, scheduling, locking, and retries.
- Preserved PHP 7.4 support, existing public methods and filters, and the first-caller singleton ownership contract. Later initialization arguments remain ignored.

To test the candidate after its tag is published:

```console
composer require mickey-kay/better-font-awesome-library:2.1.0-rc.1
```

To roll back to the previous stable release:

```console
composer require mickey-kay/better-font-awesome-library:2.0.3 --with-all-dependencies
```
