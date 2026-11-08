# Contributing

Keep native Bricks controls and WordPress admin patterns. Maintain `fsbr*` contracts. Do not add visitor-controlled routing, arbitrary provider keys, frontend assets or normal-page network calls.

Run README checks and inspect the ZIP. Never commit credentials, private recipient tokens, live exports, mail evidence, licensed Bricks code, `.tools/`, `qa/` or real submissions. Use `example.test` and synthetic fixtures. Private operational notes are ignored.

Automated tests must intercept FormSubmit. Public CI blocks external WordPress HTTP and uses doubles in `tests/contract-bootstrap.php`. Never put these doubles in runtime code or represent them as native Bricks integration coverage.

Real-theme tests require your own disposable QA site, environment `local`, URL `http://127.0.0.1:8796`. Copy `tests/provider-mock.php` into its `wp-content/mu-plugins/` before upload/browser tests. Create a local `fsbqa` administrator, save its password only in `.tools/qa-password.txt`, then run `wp eval-file tests/prepare-browser.php --path=.tools/wordpress`. This creates a synthetic page and clears the default recipient only in that disposable site. Serve localhost on port 8796 and run `python tests/native-upload-http.py`. Finish with `tests/cleanup.php` and remove the mock. Never use production.

Optional Playwright harness: `npm ci`, `npx playwright install chromium`, `npm run test:browser`; `FSBR_CHROMIUM_PATH` can select an executable. It is not in public CI. Use approved browser-control tooling when your working environment restricts automation.

Keep translations in `native-formsubmit-for-bricks`, regenerate POT for string changes and update readme/setup/privacy together. Version changes/publication are deliberate release decisions. CI never submits, deploys or sends mail.
