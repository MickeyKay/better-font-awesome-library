# WordPress agent skills upstream

- Source: https://github.com/WordPress/agent-skills
- Pinned commit: `d87ee6916e740c7960b6959220c0481a41b320c7`
- Import date: 2026-08-29
- License: GPL-2.0-or-later, copied in `LICENSE`

## Included skills

- `wp-project-triage`
- `wp-plugin-development`
- `wp-performance`
- `wp-phpstan`

Block, Playground, Blueprint, WP-CLI operations, and WordPress.org Plugin
Directory skills are intentionally not included because BFAL is a reusable
WordPress library and the current repository audit found no concrete need for
those workflows. No WordPress Design System or WordPress.org Plugin Directory
MCP server is configured for this repository.

## Compatibility precedence

BFAL's verified compatibility requirements override generic WordPress skill
version defaults. In particular, the imported skills currently describe
WordPress 7.0+ and PHP 7.4+ defaults. Those defaults must not be treated as
BFAL runtime requirements unless BFAL independently adopts them through a
reviewed compatibility decision.

## Update policy

Updates must be deliberate, reviewable imports pinned to a full upstream
commit. Review upstream changes, import only the skills still relevant to
BFAL, update this file's commit and import date, preserve the upstream license,
and keep the tooling update in a focused commit separate from runtime changes.
