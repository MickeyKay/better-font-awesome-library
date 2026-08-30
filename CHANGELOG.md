# Changelog

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
