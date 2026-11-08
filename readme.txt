=== Native FormSubmit for Bricks ===
Contributors: minthaik
Tags: bricks, forms, contact-form, email, file-upload
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect native Bricks forms to FormSubmit using an easy setup screen and native form controls.

== Description ==

Native FormSubmit for Bricks adds a FormSubmit action to the existing Bricks Form element. Keep your form design, validation, spam protection, success message and redirect inside Bricks.

* Optional site-wide default recipient with per-form overrides.
* Native recipient, subject, reply-to, included-field and email-layout controls.
* Automatic mapping of supported fields with readable labels.
* Opt-in file and image attachments from native Bricks Files fields.
* CC recipients, blocked phrases, public HTTPS webhooks and private recipient tokens.
* Setup screen with an explicit test-message button and activation instructions.
* No frontend scripts, submission database, tracking or background requests.

Requires an active licensed Bricks theme, version 1.12.2 or later. Attachment forwarding requires Bricks 2.4.3 or later. Bricks is not included. Independently developed; not affiliated with Bricks or FormSubmit.

Supported text fields: text, email, telephone, number, URL, textarea, rich text, checkbox, select, radio and datepicker. Rich text is converted to plain text. Passwords, hidden fields, honeypots, HTML, remember-me and media-library fields are never sent as text. Configure uploaded attachments separately using native Files fields.

Recipient, subject, CC, private token and webhook settings are authored literal configuration, not visitor-controlled values or dynamic tags. A request timeout can occur after the provider received the message; manual retries may produce duplicates. See feature compatibility below for FormSubmit's AJAX limitations.

== Installation ==

1. Upload the plugin ZIP under Plugins > Add New > Upload Plugin and activate it.
2. Open Settings > Native FormSubmit for Bricks. Optionally save a default recipient email.
3. Click Send test to saved recipient. Confirm FormSubmit's activation email if requested, then send another test and verify it reaches your inbox.
4. Edit a native Bricks Form element. Add FormSubmit under Actions.
5. Open the FormSubmit group. Use the site default or enter a recipient/private token. Optionally set subject, reply-to, included text fields, layout, CC, blocked phrases and webhook.
6. Remove the Email action if FormSubmit is your only delivery service. Enable Bricks spam protection. Configure the native Bricks Success message and optional Redirect action.
7. Save and test from the frontend. The builder itself does not submit forms. Each recipient/form combination may require activation; confirm any activation email and retest.

== Files and image attachments ==

Add a native Bricks Files field and configure its allowed formats, count and size limits. For images, allow formats such as png,jpg,jpeg,webp in that Files field. Then enable Attach uploaded files in the FormSubmit group. Optionally select specific Attachment fields; leaving it empty includes all native Files fields. Media-library Image and Gallery elements are not uploaded attachments.

Attachment forwarding is disabled by default, including after upgrading existing forms. It reads only files produced by Bricks for this submission, within the WordPress uploads root. Submitted paths/URLs, media-library IDs, unknown upload keys, unsupported extensions, duplicate files, and paths outside that root cannot be forwarded. File changes and unreadable files fail safely. Bricks validates uploads first; the plugin rechecks field limits and actual byte sizes.

FormSubmit documents a combined 10 MB limit. The plugin uses a conservative total of 10,000,000 bytes across up to 20 files. Bricks field settings, WordPress/PHP and mailbox limits can be lower. Optional empty file fields are accepted. Bricks controls file storage and cleanup; this plugin stores no extra copy and does not change retention. Custom upload directories outside the WordPress uploads root are unsupported. Test again with fresh files after activation; the provider archive does not retain attachments.

== FormSubmit feature compatibility ==

* Subject, reply-to and Table/Basic/Box email layout: native controls.
* CC recipients: up to 20 comma-separated literal email addresses, deduplicated.
* Blacklist: up to 20 phrases separated by commas or new lines. Passed to FormSubmit, which may silently discard matches.
* Webhook: an authored public HTTPS URL passed to FormSubmit. Private/local addresses, URL credentials and fragments are rejected. The plugin does not directly invoke your endpoint. Disclose the additional data destination.
* Invisible email: optional private recipient token from your FormSubmit activation email. It replaces the recipient address for that form. Remove it before sharing exports.
* Files/images: opt-in multipart attachments through native Files fields.
* Thank-you redirect: use Bricks' native Redirect action. The provider's _next is used internally only to confirm attachment acceptance; Bricks owns the visitor redirect.
* Honeypot/CAPTCHA: Bricks' native honeypot, reCAPTCHA, hCaptcha and Turnstile run before this action. The hosted FormSubmit CAPTCHA page is not part of AJAX submission.
* Autoresponse: FormSubmit explicitly does not support autoresponses for AJAX submissions or with its CAPTCHA disabled. No nonworking autoresponse toggle is offered. Bricks' separate confirmation email can be used when WordPress mail is configured.
* Archive API: a separate provider service, not a local inbox. FormSubmit documents 30-day retention, five archive API requests per day, and no retained uploaded files. Use its API documentation for key/submission access. This plugin does not fetch or store archive records/API keys.
* Unlimited forms/submissions: provided by FormSubmit's service. The plugin adds no submission quota.

== External service and privacy ==

This plugin uses FormSubmit.co to process and email submissions. A configured form sends its recipient/private token, included values, enabled attachment bytes, reply-to, subject, layout and any CC/blacklist/webhook options over HTTPS. Text-only requests use JSON at https://formsubmit.co/ajax/{destination}; attachments use the documented multipart endpoint https://formsubmit.co/{destination}. Attachment acceptance requires an exact unique provider redirect receipt, which is never followed. The site home URL is sent as the HTTP Referer for provider attribution, never raw visitor query strings or preview nonces. No request occurs merely by activation or page viewing.

The setup test button sends a sample message, the saved default recipient, and the site home URL when an administrator deliberately presses it. It does not send real visitor submissions.

The plugin does not explicitly forward visitor IP addresses, user agents, cookies, nonces, passwords or hidden fields. FormSubmit receives ordinary server connection metadata, including the hosting server's IP. Optional CC recipients and webhook services receive submission data too. Site owners control what they collect and must update privacy notices. FormSubmit states that text submissions are retained for 30 days; its archive does not retain uploaded files. Your host, Bricks upload/storage settings and email providers may retain data independently. This plugin stores no submission records or attachment copies.

FormSubmit documentation: https://formsubmit.co/documentation
FormSubmit AJAX documentation: https://formsubmit.co/ajax-documentation
FormSubmit archive API: https://formsubmit.co/api-documentation
FormSubmit privacy policy and terms: https://formsubmit.co/privacy.pdf

== Frequently Asked Questions ==

= Do I need SMTP or an API key? =
The FormSubmit action uses HTTPS, not WordPress mail. FormSubmit does not require an API key for this integration. Your host must allow outgoing HTTPS to formsubmit.co.

= Does success mean the message reached my inbox? =
No. Success means FormSubmit returned an explicit acceptance response. First-time activation, spam filtering and downstream email delivery still need manual verification. The plugin never claims to verify an inbox.

= Why did no message arrive? =
Check the recipient, activation email, spam folder, Bricks spam-protection configuration and your host's outbound HTTPS policy. Send a setup test. If a test works but the form does not, check the FormSubmit action and included-field/reply-to settings. A selected reply-to must be an included email field with a valid nonempty value. Leave reply-to unselected to use the first included email field with a value.

= Can I keep the native Bricks Email action? =
Yes, but both actions will run and may send duplicate notifications. Actions are sequential; a failed action stops later actions. Put FormSubmit first if its acceptance is required before later actions run. This plugin does not remove existing actions automatically.

= What happens if I deactivate or remove the plugin? =
First remove or replace the FormSubmit action on connected forms. Bricks may skip unavailable custom actions and show success without delivery. Deactivation retains setup settings; uninstall removes plugin-owned site settings and leaves Bricks documents intact. Exported forms may contain recipients, private tokens, CC and webhook settings; remove them before distribution.

== Changelog ==

= 0.2.1 =
* Directory preparation: neutral-prefix name, matching text domain and reproducible public checks.

= 0.2.0 =
* Opt-in native Files attachments with path/count/size validation and multipart transport.
* CC, blacklist, public HTTPS webhook and private recipient token controls.
* Clean site-home Referer header and expanded service/privacy documentation.

= 0.1.0 =
* Initial standalone version with native Bricks controls, setup and test-message workflow.

== Upgrade Notice ==

= 0.2.1 =
Directory candidate with a new folder/name and preserved native action/settings IDs. Deactivate the earlier private package before installing. Back up its site default recipient; do not activate both packages together.
