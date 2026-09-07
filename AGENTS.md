# BFAL Agent Guidance

## Architecture and compatibility

- Better Font Awesome Library is a reusable OSS library. Better Font Awesome is one consumer, not a privileged owner in every installation.
- In the default `automatic` delivery mode, preserve the live-ish, always-up-to-date architecture for validated Font Awesome Free metadata. The supported channels are Font Awesome Free 7.x (default) and explicit 5.x. Pro support is out of scope until the Free architecture is stable.
- In `automatic` mode, bundled release and icon metadata is only a cold-start and failure fallback. Validated provider or last-known-good transient data takes precedence.
- The optional `bundled-local` delivery mode intentionally pins the active catalog, version, CSS, compatibility styles, and fonts to the same packaged FA7 Free release. Bypass providers and transients without mutation, request no background refresh, and never substitute remote assets. Updates arrive through BFAL or embedding-plugin updates. Explicit FA5 with `bundled-local` must fail closed without a channel switch. See README.md's Local asset delivery section for the public contract.
- Ordinary frontend, admin, editor, REST, and other request paths must not perform metadata HTTP. Remote metadata transport belongs only in an explicit asynchronous worker path, and remote responses must be fully validated before adoption.
- When BFA owns the BFAL instance, BFA owns WordPress-specific durable storage, freshness policy, WP-Cron scheduling, locking, retries, migration, and authenticated refresh controls.
- The first `Better_Font_Awesome_Library::get_instance()` caller intentionally owns all initialization arguments. Later arguments are ignored. Hook priority is the supported ownership mechanism, and an earlier consumer can intentionally prevent BFA from owning that instance.
- BFA is not guaranteed to override earlier BFAL ownership. Do not reclassify this first-caller behavior as a defect without concrete user-facing interoperability evidence and explicit repository owner approval.
- Do not add late registration, mutation, reset, claim, ownership-transfer, filter, or alternate hook mechanisms for singleton ownership. Any new public ownership API requires a demonstrated need, explicit justification, and repository owner approval.
- Preserve existing public behavior unless an intentional compatibility change is approved. BFAL's verified compatibility requirements override generic defaults in vendored skills.
