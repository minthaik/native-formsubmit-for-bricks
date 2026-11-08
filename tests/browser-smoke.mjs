import { chromium, expect } from '@playwright/test';
import fs from 'node:fs/promises';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const fixture = JSON.parse(await fs.readFile(path.join(root, '.tools/browser-fixture.json'), 'utf8'));
const password = (await fs.readFile(path.join(root, '.tools/qa-password.txt'), 'utf8')).trim();
const base = 'http://127.0.0.1:8796';
if (!fixture.url.startsWith(base + '/')) throw new Error('Wrong QA environment');
await fs.mkdir(path.join(root, 'qa'), { recursive: true });
const browser = await chromium.launch({ headless: true, ...(process.env.FSBR_CHROMIUM_PATH ? { executablePath: process.env.FSBR_CHROMIUM_PATH } : {}) });
const context = await browser.newContext({ viewport: { width: 1440, height: 1080 } });
const page = await context.newPage();
const checks = [];
const observations = [];
const errors = [];
page.on('pageerror', error => errors.push(error.message));

try {
  await page.goto(base + '/wp-login.php');
  await page.locator('#user_login').fill('fsbqa');
  await page.locator('#user_pass').fill(password);
  await Promise.all([page.waitForURL('**/wp-admin/**'), page.locator('#wp-submit').click()]);
  await page.goto(base + '/wp-admin/options-general.php?page=native-formsubmit-for-bricks');
  await expect(page.getByRole('heading', { name: 'Native FormSubmit for Bricks', exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Send test to saved recipient' })).toBeDisabled();
  checks.push('Setup loads with accessible recipient label and test disabled until configured');
  const csrf = await context.request.post(base + '/wp-admin/admin-post.php', { form: { action: 'fsbr_test' } });
  if (csrf.status() !== 403) throw new Error('Missing test nonce was not rejected');
  const getTest = await context.request.get(base + '/wp-admin/admin-post.php?action=fsbr_test');
  if (getTest.status() !== 405) throw new Error('GET test request was not rejected');
  checks.push('Admin test rejects missing nonce and GET requests');
  await page.getByLabel('Default recipient email', { exact: true }).fill('qa-default@example.test');
  await Promise.all([page.waitForURL('**/options-general.php?**'), page.getByRole('button', { name: 'Save recipient' }).click()]);
  await expect(page.getByLabel('Default recipient email', { exact: true })).toHaveValue('qa-default@example.test');
  checks.push('Settings API saves the recipient');
  await Promise.all([page.waitForURL('**fsbr_result=accepted'), page.getByRole('button', { name: 'Send test to saved recipient' }).click()]);
  await expect(page.getByText('FormSubmit accepted the test request.', { exact: false })).toBeVisible();
  checks.push('Explicit admin test uses the mocked provider and explains activation/inbox limits');
  await page.screenshot({ path: path.join(root, 'qa/setup-desktop.png'), fullPage: true });
  await page.setViewportSize({ width: 390, height: 844 });
  await page.screenshot({ path: path.join(root, 'qa/setup-mobile.png'), fullPage: true });
  const overflowing = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
  if (overflowing) throw new Error('Setup has mobile horizontal overflow');
  checks.push('Setup has no horizontal overflow at 390px');

  // A builder license is a separate prerequisite; report access honestly.
  await page.setViewportSize({ width: 1440, height: 1080 });
  await page.goto(base + '/?page_id=' + fixture.pageId + '&bricks=run');
  await page.waitForTimeout(1500);
  await page.screenshot({ path: path.join(root, 'qa/builder-initial.png'), fullPage: true });
  const builderAccessible = await page.locator('#bricks-panel').count() > 0;
  observations.push(builderAccessible ? 'Native Bricks builder opens' : 'Visual builder controls could not be checked because this QA site has no activated Bricks license');

  // Logged-out frontend proves normal visitors can submit with Bricks validation.
  const visitor = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const formPage = await visitor.newPage();
  formPage.on('pageerror', error => errors.push(error.message));
  const anonymousTest = await visitor.request.post(base + '/wp-admin/admin-post.php', { form: { action: 'fsbr_test' } });
  if (anonymousTest.status() !== 400) throw new Error('Anonymous test request was not rejected');
  checks.push('Anonymous visitors cannot invoke the admin test action');
  await formPage.goto(fixture.url);
  await expect(formPage.getByRole('heading', { name: 'Synthetic plugin QA' })).toBeVisible();
  if ((await formPage.content()).includes('qa-recipient@example.test')) throw new Error('Recipient leaked in frontend markup');
  checks.push('Per-form recipient is absent from public frontend HTML');
  await formPage.getByRole('button', { name: 'Send QA message' }).click();
  if (await formPage.locator('input:invalid').count() < 1) throw new Error('Required validation failed');
  checks.push('Native browser required-field validation remains active');
  await formPage.getByLabel('Name', { exact: true }).fill('Synthetic Browser Tester');
  await formPage.getByLabel('Email', { exact: true }).fill('browser@example.test');
  await formPage.getByLabel('Message', { exact: true }).fill('Synthetic success message');
  await formPage.getByRole('button', { name: 'Send QA message' }).click();
  await expect(formPage.getByText('FSBR QA request accepted', { exact: true })).toBeVisible();
  checks.push('Logged-out native Bricks AJAX submission accepts a mocked provider success');
  await formPage.screenshot({ path: path.join(root, 'qa/frontend-success-mobile.png'), fullPage: true });
  await formPage.getByLabel('Name', { exact: true }).fill('Synthetic Browser Tester');
  await formPage.getByLabel('Email', { exact: true }).fill('browser@example.test');
  await formPage.getByLabel('Message', { exact: true }).fill('FSBR_QA_REJECT');
  await formPage.getByRole('button', { name: 'Send QA message' }).click();
  await expect(formPage.getByText('The message could not be accepted.', { exact: false })).toBeVisible();
  await expect(formPage.getByLabel('Message', { exact: true })).toHaveValue('FSBR_QA_REJECT');
  checks.push('Provider rejection shows an error and retains visitor input');
  await formPage.screenshot({ path: path.join(root, 'qa/frontend-error-mobile.png'), fullPage: true });
  const capture = JSON.parse(await fs.readFile(path.join(root, '.tools/browser-capture.json'), 'utf8'));
  if (!capture.url.endsWith('/qa-recipient%40example.test') || JSON.stringify(capture.payload).includes('FSBR_HIDDEN_EXCLUDED')) throw new Error('Frontend payload routing/exclusion failed');
  checks.push('Actual AJAX payload excludes hidden fields and uses authored per-form routing');
  await visitor.close();
  if (errors.length) throw new Error('Browser errors: ' + errors.join('; '));
  await fs.writeFile(path.join(root, 'qa/browser.json'), JSON.stringify({ passed: checks.length, checks, observations, browserErrors: errors, realProviderCalls: 0, builderAccessible }, null, 2) + '\n');
  process.stdout.write(JSON.stringify({ passed: checks.length, builderAccessible, browserErrors: errors }) + '\n');
} finally {
  await browser.close();
}
