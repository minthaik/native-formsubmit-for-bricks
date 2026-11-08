# Release status

## Current candidate

Version 0.2.1 prepares the independent **Native FormSubmit for Bricks** name, matching text domain/folder, original logo and public CI. Native action/settings IDs are preserved. The upload accessor is guarded so earlier text-only Bricks versions do not require its newer file API. The production private package remains 0.2.0; this candidate has not been submitted to WordPress.org or migrated into production.

Public CI is the reproducible record for PHP standards, WordPress 6.5/latest contract tests, package integrity and Plugin Check. Its small independent Bricks doubles do not replace real-theme tests. CI contacts no FormSubmit endpoint and needs no real recipient or licensed theme.

## Completed integration evidence before directory preparation

- Actual local WordPress 7.1.4, PHP 8.3.30 and licensed Bricks 2.4.4: 77 WordPress/Bricks integration checks, 34 attachment/loopback parsing checks and 9 native upload HTTP checks passed for 0.2.0.
- The live builder on Bricks 2.4.5 exposed native controls; mapped Files settings and opt-in attachment configuration were verified on an unpublished temporary draft.
- Explicitly authorized synthetic PNG (118 bytes) and PDF (697 bytes) reached the test inbox. Decoded received bytes matched both source SHA-256 hashes. No real visitor data was used. Private evidence and addresses remain outside Git.
- Temporary live drafts were disconnected and moved to recoverable Trash. No public demo form was edited or connected by this work. Existing global recipient settings were preserved.
- The 0.2.0 installable package passed PHP lint, WPCS/PHPCompatibility and Plugin Check 2.2.0 with no errors/warnings. Directory candidate checks are recorded in the latest GitHub Actions run and release notes.

## Candidate checks on November 8, 2026

- 78 integration checks and 34 attachment/loopback checks passed with real licensed Bricks, then passed again using the independent CI doubles on local WordPress. No provider calls were made.
- Twelve runtime PHP files passed syntax checks; WPCS/PHPCompatibility had no errors or warnings. Composer/npm audits reported no advisories.
- Plugin Check 2.2.0 completed with no errors/warnings against the synced candidate.
- The 15-file deterministic runtime archive passed hash, UTF-8/BOM/LF, path, metadata and source-match checks. Public tracked-file scanning excluded private QA, operations, credentials and theme files.
- Logo exports have validated 128/256/1024 square PNG dimensions and preserved transparency.

## Remaining compatibility scope

The real minimum Bricks version and a full multisite network-uninstall matrix have not been exercised. PHP/WordPress minimum contracts are covered by public CI, with the real-theme gate limited to the versions above. Low-memory concurrent 10 MB uploads are a hosting-sensitive manual check. No PHPStan/Psalm gate is configured yet; adding a focused WordPress-aware static-analysis baseline is recorded development debt.

## Release and rollback

Review `docs/WORDPRESS-ORG.md`, inspect the exact allowlisted ZIP and wait for green CI. Submission and account declarations remain for the owner. Never activate the earlier private and renamed candidate plugins together. Back up recipient settings and follow migration notes. Replace the delivery action before deactivation; uninstall clears only plugin settings and leaves Bricks documents intact. Runtime services have no schema migration, background work or submission store to roll back.
