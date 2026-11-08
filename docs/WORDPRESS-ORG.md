# WordPress.org submission and review

## Submission record — November 8, 2026

Submitted successfully using **minthaik**. WordPress.org displayed **Results of Automated Plugin Scanning: Pass** and **Awaiting Review**. Initial assigned slug: `native-formsubmit-for-bricks`. Submitted file: `native-formsubmit-for-bricks-0.2.1.zip`, version **0.2.1**, SHA-256 `c8aff14ce0a8590106843ec5785afff77192935d42960b42ed1ed2848b9a0845`. This is the unchanged canonical CI-built artifact. WordPress.org reported that it sent a confirmation email. No manual approval or SVN access has been granted yet.

Do not create a duplicate submission. Await the review email and respond in the same ticket; use the existing submission's updated-plugin upload if revisions are requested.

## Candidate

- Name: **Native FormSubmit for Bricks**.
- Proposed slug/text domain/folder: `native-formsubmit-for-bricks`.
- Version: **0.2.1**.
- Contributor/submitting account: **minthaik**, confirmed in signed-in WordPress.org toolbar.
- Source: https://github.com/minthaik/native-formsubmit-for-bricks
- Upload: `build/native-formsubmit-for-bricks-0.2.1.zip`; SHA-256 in `build/manifest.json`.
- GPL-2.0-or-later. No theme or runtime dependency bundled.

The former private package began with FormSubmit. The candidate has a neutral prefix because [guideline 17](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#17-plugins-must-respect-trademarks-copyrights-and-project-names) restricts slugs starting with another project's name. The directory team decides naming, availability and acceptance; this is not a reservation or approval.

## Prepared review notes

> Native FormSubmit for Bricks adds an explicitly selected custom action to the existing Bricks Form element. This independent integration requires separately licensed Bricks, with no theme code bundled. Bricks 1.12.2+ is required; opt-in Files attachments require 2.4.3+. FormSubmit.co is the external mail service. The readme documents data, endpoints, activation, privacy/terms and feature limitations. Only configured submissions and deliberate capability/nonce-protected administrator tests contact the service. Activation/page viewing triggers no network request. No local submissions, telemetry, frontend libraries, update service or API key storage are added. All automated provider calls are mocked.

If review access is requested, supply it privately to the review team. Never publish production credentials or licensed themes.

## Review and publication steps

1. Await the review email and keep the same ticket for all corrections. Passing automated checks does not guarantee approval.
2. For requested changes, run appropriate checks, inspect the exact ZIP, and use the existing submission's updated-plugin upload. Do not submit a second copy.
3. After approval, use the assigned WordPress.org SVN repository: runtime files in `trunk/`, version in `tags/0.2.1/`, artwork separately in top-level `assets/`. Match the stable tag and actual approved version. Do not upload the GitHub checkout or licensed theme.

No automated SVN/directory deployment or stored SVN credentials are configured before approval.

## Earlier private installation

Private 0.2.0 uses folder `formsubmit-for-bricks` and the same namespace. **Never activate both packages together.** Back up the site/default recipient, deactivate the earlier plugin, and remove its files without invoking uninstall before installing the candidate. Saved `fsbr_settings` and native action/control IDs are unchanged. WordPress's Delete button invokes uninstall and clears the default recipient; restore/re-save it if you take that route. Verify connected forms before resuming traffic. Repository preparation does not migrate production.

## Artwork

`assets/icon-128x128.png` and `assets/icon-256x256.png` are directory icons. `assets/logo-1024.png` is the reusable original. Later upload to SVN `assets/`, outside the runtime ZIP. Optional screenshots/banners are not needed for the initial package. Screenshots must use synthetic settings and exclude private data.

## References

- [Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
- [Submission](https://wordpress.org/plugins/developers/add/)
- [Readme format](https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/)
- [Plugin assets](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/)
