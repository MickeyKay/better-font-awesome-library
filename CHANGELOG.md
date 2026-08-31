# Changelog

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
