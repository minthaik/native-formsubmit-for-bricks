<p align="center"><img src="assets/icon-256x256.png" width="128" height="128" alt="Native FormSubmit for Bricks logo"></p>

# Native FormSubmit for Bricks

[![Plugin quality](https://github.com/minthaik/native-formsubmit-for-bricks/actions/workflows/ci.yml/badge.svg)](https://github.com/minthaik/native-formsubmit-for-bricks/actions/workflows/ci.yml)

Connect the native **Bricks Form** element to **FormSubmit.co**, with a WordPress setup screen and native Bricks controls. Independent GPL plugin; neither Bricks nor FormSubmit is included or affiliated with it.

- Site default recipient and per-form email/private-token overrides.
- Subject, reply-to field, included fields and email layout.
- Opt-in image/file attachments from native **Files** fields.
- CC recipients, blocked phrases and public HTTPS webhooks.
- Native Bricks validation, spam protection, success messages and redirects.
- No frontend assets, local submission database, queues or telemetry.

**Requirements:** WordPress 6.5+, PHP 7.4+, active licensed Bricks 1.12.2+. Attachments require Bricks 2.4.3+. See [setup, privacy, service limits and troubleshooting](readme.txt).

FormSubmit's hosted CAPTCHA and autoresponse are incompatible with this server-side flow. Configure Bricks' spam protection. Provider acceptance does not prove inbox delivery. Attachments are limited to 10,000,000 combined bytes and 20 files; native field/hosting limits may be lower.

## Install

Build the installable ZIP or download a verified release/CI artifact. GitHub's source archive contains development files and is not the release package.

```sh
python scripts/package.py
```

Upload `build/native-formsubmit-for-bricks-0.2.1.zip` through **Plugins > Add New > Upload Plugin**. Open **Settings > Native FormSubmit for Bricks**, optionally save a recipient, and send the deliberate setup test. Confirm FormSubmit's activation email, then verify a fresh test reaches your inbox. Add **FormSubmit** under Actions on a native Bricks Form element and configure its FormSubmit group.

Submitted to WordPress.org on November 8, 2026 under `minthaik`: automated scan **passed**, status **Awaiting Review**. It is not yet approved. See [submission notes](docs/WORDPRESS-ORG.md), including migration from the earlier private `formsubmit-for-bricks` package.

## Development and checks

```sh
composer install
composer validate --strict
composer lint
composer audit
php tests/dependency-smoke.php none
php tests/dependency-smoke.php 1.12.2
python scripts/package.py
python scripts/check-release.py
```

GitHub Actions checks PHP 7.4, 8.3 and 8.5 syntax/coding standards, dependency advisories, the package and WordPress Plugin Check. WordPress-backed contract tests run on WordPress 6.5/PHP 7.4 and latest WordPress/PHP 8.3. They use small independently written Bricks API doubles: **contract tests, not licensed Bricks compatibility tests**. Provider calls are intercepted and external WordPress HTTP is blocked. CI needs no recipient, private theme or production credential.

For licensed-theme verification, create disposable WordPress under `.tools/wordpress`, set environment `local` and home URL `http://127.0.0.1:8796`, install your licensed Bricks, and activate only this plugin. Never run mutation tests on a customer site.

```sh
python scripts/package.py --sync-qa
wp eval-file tests/integration.php --path=.tools/wordpress
wp eval-file tests/attachments.php --path=.tools/wordpress
```

Optional multipart receiver: `php -S 127.0.0.1:8797 -t tests`; set `FSBR_TEST_CAPTURE=1` for attachment tests with real HTTP parsing and binary hashes. [Contributing](CONTRIBUTING.md) documents disposable localhost native-upload/browser checks.

## Architecture and release

[Architecture](docs/ARCHITECTURE.md) explains ownership, settings, limits and transport. [Validation status](docs/STATUS.md) distinguishes real Bricks, contract and provider checks. [Security reporting](SECURITY.md) explains private reports.

The packager uses an explicit runtime allowlist. Tests, fixtures, tooling, artwork and development documents never enter the installable ZIP. [Artwork](assets/README.md) is prepared for the separate WordPress.org SVN `assets/` directory. Runtime code and original artwork use GPL-2.0-or-later; development dependencies retain their own licenses.
