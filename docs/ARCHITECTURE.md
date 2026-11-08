# Architecture

The entry point loads `Plugin`, registers at `after_setup_theme`, and checks Bricks' version. `Controls` extends native controls/groups. Submission services load only when `fsbr-formsubmit` runs. There is no frontend script, REST route, public AJAX handler, cron, queue or submission archive.

| Component | Responsibility |
| --- | --- |
| Admin | Settings API, capability/nonce/POST-protected test, privacy suggestion |
| Configuration | Optional site default, literal recipient and private-token override |
| Payload | Saved native text field allowlist, normalization, reserved keys, reply-to |
| Provider_Options | Bounded literal CC, blacklist and public HTTPS webhook |
| Attachments | Opt-in native Files results, canonical uploads paths, type/count/size checks |
| Multipart | Bounded byte reads, safe headers and binary encoding |
| Client | Fixed host, TLS, timeouts, response limits and explicit acceptance |
| Action | Validated Bricks adapter and native success/error results |

Persistence is one site option, `fsbr_settings` (`schema_version: 1`, `recipient`). Bricks owns per-form settings, uploads, validation and retention. Uninstall removes only plugin options, including batched multisite options; it never rewrites Bricks documents. Replace the action before deactivation: Bricks can skip unavailable custom actions without delivering mail.

The action `fsbr-formsubmit`, group `fsbrFormsubmit`, `fsbr*` setting IDs and PHP namespace remain stable across the directory rename. Exported Bricks documents may contain recipient/token/CC/webhook settings; review them before sharing.

## Data and transport

Saved supported text field definitions determine submitted keys. Passwords, hidden fields, honeypots, media IDs, transport metadata and arbitrary request keys are excluded. Rich text becomes plain text. Nested values fail; reserved labels are renamed. Recipient routing cannot come from visitor values. Selected reply-to must be an included email field with a valid nonempty address.

Attachments require Bricks 2.4.3+ and opt-in. Only `get_uploaded_files()` results for saved native Files fields are eligible. Canonical paths must stay within WordPress uploads. Unknown keys, duplicates, URLs, unsupported extensions, unreadable/empty/changed files and exceeded field limits fail. No extra copy is stored. WordPress type checks supplement native Bricks upload validation.

Text uses `https://formsubmit.co/ajax/{destination}` with JSON and strict success `true`/`"true"`. Actual provider verification found AJAX discarded files while accepting text. Files therefore use the normal multipart endpoint `https://formsubmit.co/{destination}`. A generated unique `_next` receipt URL is accepted only on exact Location match with HTTP 302/303. The redirect is never followed or rendered. Bricks owns visitor redirects.

Both use WordPress safe HTTP, TLS, zero redirects/retries and a clean site-home Referer. Raw response/error bodies are never exposed or logged. Acceptance is not activation/inbox delivery; manual retries after timeouts can duplicate delivery.

| Bound | Limit |
| --- | --- |
| Text fields / flat values per field | 100 / 100 |
| Value / encoded text payload | 10,000 / 65,536 bytes |
| Attachment bytes / files | 10,000,000 / 20 |
| Response / text timeout / files timeout | 16,384 bytes / 15 seconds / 30 seconds |
| CC / blacklist phrases | 20 / 20 |

CC/webhooks create additional destinations. Webhooks must be authored public HTTPS URLs; private/reserved IPs, credentials and fragments are rejected. The plugin passes this directive to FormSubmit, rather than calling the webhook itself.

## Validation boundaries

CI uses real WordPress and independent Bricks API doubles to verify plugin contracts. Native builder rendering and Bricks' actual upload handler need a separately licensed-theme gate. No licensed source is published. Real theme validation used Bricks 2.4.4 locally and 2.4.5 live; minimum Bricks is a compatibility target, with dependency gates tested. Full multisite network-uninstall and concurrent low-memory uploads remain manual checks.

## References

- [Bricks forms/custom actions](https://academy.bricksbuilder.io/builder/elements/general/form/#custom-form-actions)
- [Action dispatch](https://academy.bricksbuilder.io/developer/hooks/filters/filter-bricks-form-action-form_action/)
- [FormSubmit documentation](https://formsubmit.co/documentation), [AJAX](https://formsubmit.co/ajax-documentation), [archive API](https://formsubmit.co/api-documentation), [privacy/terms](https://formsubmit.co/privacy.pdf)

Provider behavior may change. No nonworking autoresponse or hosted CAPTCHA control is offered for this server-side flow.
