# Changelog

## 0.2.1 — November 8, 2026

- Directory candidate renamed Native FormSubmit for Bricks, with matching slug/text domain.
- Existing native action/settings IDs preserved; see migration notes for the earlier private package.
- Public CI, deterministic allowlist packaging, submission notes and original logo assets.

## 0.2.0 — November 8, 2026

- Opt-in attachment forwarding from native Bricks Files fields, including images and multiple files, with explicit field selection and bounded local-file validation.
- Documented normal multipart provider endpoint with strict redirect-receipt confirmation; text-only forms retain the JSON endpoint.
- CC recipients, blocked phrases, public HTTPS webhook and private recipient token controls.
- Site-home Referer header for provider attribution; no preview query/nonce forwarding.
- Real PNG/PDF delivery verified byte-for-byte, plus upload security and transport tests. Public demo forms remain disconnected.

## 0.1.0 — November 8, 2026

- Initial standalone WordPress plugin with native Bricks FormSubmit action and controls.
- Optional site recipient, per-form override, subject, mapped reply-to, included fields and email layout.
- WordPress setup screen with an administrator-requested test and activation instructions.
- Server-side delivery with sensitive-field exclusions, bounded payloads, fixed HTTPS destination and safe native errors.
- Installable ZIP, translation template, automated checks and setup/privacy documentation.
