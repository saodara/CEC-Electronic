# Bakong / KHQR Payment Integration

How this app actually accepts KHQR payments end to end, traced to the real files (`app/Services/BakongService.php`, `app/Services/KhqrGenerator.php`, `app/Console/Commands/CheckPendingBakongPayments.php`, `app/Http/Controllers/Storefront/CheckoutController.php`, `resources/views/checkout/*.blade.php`), plus the real failure modes this integration hit during development and how each was actually diagnosed — not generic KHQR/API-integration advice. Deployment/infra background lives in `DEPLOYMENT.md`; general app business logic lives in `PROJECT.md` (its Bakong section predates this rebuild and is stale — see "Superseded documentation" at the bottom).

## Architecture

```
Customer checkout (payment_method = bakong)
        │
        ▼
CheckoutController::store()
        │  BakongService::generateQrForOrder()
        │  → KhqrGenerator::individual()  (pure PHP, no API call)
        │  → orders.bakong_qr_string, orders.bakong_qr_md5 saved
        ▼
checkout/success.blade.php
        │  renders the KHQR string as a scannable QR (self-hosted qrcodejs)
        │  polls GET /checkout/payment-status/{order} every 15s
        ▼
CheckoutController::paymentStatus()  ───throttled 1 call / 15s / order───▶  BakongService::checkTransactionByMd5()
        │                                                                          │
        │                                                                          ▼
        │                                                          POST https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5
        │                                                          (shared daily budget guard, retries DNS/connection failures)
        ▼
   marks order paid → success.blade.php swaps the QR modal for a
   "Payment successful" popup on its next poll (see below)

              ┌─────────────────────────────────────────────┐
              │  bakong:check-pending (routes/console.php,   │
              │  every 5 min via `php artisan schedule:work`)│
              │  keeps checking unpaid orders even after the │
              │  customer closes the tab — same BakongService,│
              │  same shared daily budget                    │
              └─────────────────────────────────────────────┘
```

The two consumers of `BakongService::checkTransactionByMd5()` — live browser polling and the background job — share one daily request budget and one per-order throttle, so neither can starve the other or blow Bakong's real quota (see "The 100-requests/day constraint" below).

## KHQR generation is 100% local (`app/Services/KhqrGenerator.php`)

No API call is made to build the QR code itself. `KhqrGenerator::individual()` builds the EMVCo-style KHQR payload by hand, following the NBC KHQR SDK spec's tag-length-value (TLV) format: each field is `tag(2 digits) + length(2 digits) + value`, concatenated in order, ending with a CRC16/CCITT checksum (tag `63`) computed over everything before it.

| Tag | Field | Value in this app |
| --- | --- | --- |
| `00` | Payload Format Indicator | `01` |
| `01` | Point of Initiation | `12` (dynamic — amount is fixed, can't be edited in the paying app) |
| `29` | Merchant Account Info | nested tag `00` = `BAKONG_ACCOUNT_USERNAME` (an **individual** account alias, e.g. `sin_tino@bkrt`) |
| `52` | Merchant Category Code | `5999` (misc. retail) |
| `53` | Currency | `840` (USD) or `116` (KHR) |
| `54` | Amount | order's `grand_total`, trailing zeros stripped (see known quirk below) |
| `58` | Country | `KH` |
| `59` | Merchant Name | `BAKONG_ACCOUNT_NAME`, truncated to 25 chars |
| `60` | Merchant City | `BAKONG_MERCHANT_CITY`, truncated to 15 chars |
| `62` | Additional Data | nested tag `01` = order number (bill number), truncated to 25 chars |
| `99` | KHQR Timestamps | nested `00`/`01` = creation/expiry in epoch **milliseconds** |
| `63` | CRC | CRC16/CCITT-FALSE over everything above, uppercase hex |

`BakongService::generateQrForOrder(Order $order)` (`app/Services/BakongService.php:51-66`) is the only caller — it feeds `BAKONG_ACCOUNT_USERNAME`/`BAKONG_ACCOUNT_NAME`/`BAKONG_MERCHANT_CITY` plus the order's `grand_total` and `order_number` into `KhqrGenerator::individual()` and returns `['qr' => string, 'md5' => string]`, or `null` if `isConfigured()` is false (i.e. `BAKONG_ACCOUNT_USERNAME` is blank). The MD5 of the final QR string is what later gets looked up via the Bakong Open API — this is why the exact byte content of the QR string matters (see "Individual vs. merchant account" below).

**Known quirk, not yet fixed:** the amount field strips trailing zeros (`rtrim(rtrim($formatted, '0'), '.')`), so `$0.20` encodes as `54030.2` (3-char value `0.2`) rather than `0.20`. Every KHQR-reader app tested during this integration parsed it fine as a numeric value, so payments succeeded — but if you ever see a scanning app reject the amount outright, this is the first place to look.

## Verifying payment: `BakongService::checkTransactionByMd5()`

```php
public function checkTransactionByMd5(string $md5): ?array
```

POSTs `{"md5": "..."}` to `check_transaction_by_md5` on the configured `BAKONG_PROD_BASE_API_URL`, authenticated with `Authorization: Bearer {BAKONG_ACCESS_TOKEN}`. Returns the transaction `data` array once `responseCode === 0`, otherwise `null` — including for "not found yet" (`responseCode: 1`), auth failures, and rate-limit responses. Callers can't distinguish "definitely not paid" from "couldn't check right now" by design — every caller's fallback behavior (poll again later) is correct for both cases.

Three protective layers were added around the raw HTTP call after real incidents (each is a section below):
1. **Retry + graceful failure** on DNS/connection errors.
2. **A shared daily request budget**, independent of Bakong's own quota.
3. **A per-order 15-second throttle** at the call site (`CheckoutController::paymentStatus()`), so a fast-polling or multi-tab browser can't multiply real API calls.

## Real failure mode: DNS resolution to Bakong intermittently fails

**Symptom:** the payment-status endpoint would occasionally return the equivalent of "still unpaid" even for orders that were, in fact, paid — with no visible error to the customer.

**Actual cause, found in `storage/logs/laravel.log`:**
```
cURL error 6: Could not resolve host: api-bakong.nbc.gov.kh
Illuminate\Http\Client\ConnectionException
```
`checkTransactionByMd5()` had no error handling around the `Http::post()` call — a DNS hiccup inside the Docker network (the same class of intermittent Docker embedded-DNS failure `docker-compose.yml` already works around for the Neon DB host, see `DEPLOYMENT.md`) threw an uncaught exception straight through `CheckoutController::paymentStatus()`, surfacing as a 500 to the frontend's `fetch()`. The JS `.catch()` swallowed it as "Checking payment status…" and just retried next interval — but if the failure persisted across several polls, the customer never saw confirmation.

**Fix** (`app/Services/BakongService.php:36-43, 90-103`):
```php
return Http::timeout(15)->retry(3, 500, throw: false)->acceptJson()->withToken($this->accessToken);
```
plus a `try { ... } catch (ConnectionException $e) { Log::warning(...); return null; }` around the request. `throw: false` is deliberate and load-bearing: Laravel's `retry()` helper, when `$throw` is left at its default `true`, converts a **persistently-failing HTTP response** (e.g. a genuine `500` from Bakong, or a legitimate "not found") into a thrown `RequestException` after retries are exhausted — which would reintroduce the exact same crash for a different reason. `throw: false` keeps that case as a normal response object (handled by the existing `successful()`/`responseCode` check below it), while connection-level exceptions (DNS, timeout, refused) are still retried and still degrade to a logged warning + `null` rather than a crash. Covered by `test_check_transaction_by_md5_returns_null_instead_of_throwing_on_dns_failure` in `tests/Unit/Services/BakongServiceTest.php`.

## Real failure mode: the QR code didn't render, but everything else on the page did

**Symptom:** the payment modal showed the order number, amount, and instructions perfectly — but no visible QR image, indefinitely.

**Actual cause:** the QR container was `<canvas id="khqr-canvas">`, and the `qrcodejs` library (davidshimjs/qrcodejs, self-hosted at `public/vendor/qrcodejs/qrcode.min.js`) appends its **own** `<canvas>` (or `<img>`) as a *child* of whatever element you give it. Browsers never render DOM children nested inside a `<canvas>` tag — they're only used as fallback content for non-supporting browsers. The library was successfully generating and inserting a real QR canvas; it was just invisible.

**Fix:** the container is now a plain `<div id="khqr-canvas">` (`resources/views/checkout/success.blade.php`), which is what the library actually expects. Verified visually with a real headless Chrome run (Playwright) rendering the modal end to end, not just by inspecting the HTML — curl/HTML inspection alone would not have caught this class of bug, since the QR string and the `<div>`/`<canvas>` markup both looked completely correct in the raw response.

**Related hardening, same file:** the QR library was originally loaded from `cdnjs.cloudflare.com`. It's now vendored locally at `public/vendor/qrcodejs/qrcode.min.js` and referenced via `asset('vendor/qrcodejs/qrcode.min.js')`, so a blocked/unreachable CDN in a customer's network can never silently take down QR rendering again.

## The 100-requests/day constraint

Bakong's `check_transaction_by_md5` endpoint returns, once exhausted:
```json
{"responseCode":1,"responseMessage":"Daily request limit of 100 exceeded. Please try again tomorrow.","errorCode":17}
```
This is almost certainly a sandbox/developer-tier cap on this specific access token, not a realistic production limit — **this needs to be raised by Bakong/NBC directly before relying on this for real customers**, no amount of client-side engineering changes the hard external ceiling. That said, the app defends the budget it does have on three levels, all sharing one clock (`Cache` key `bakong:daily-checks:{date}`, TTL to end of day):

1. **Global daily budget** (`BakongService::dailyBudgetExceeded()`/`recordDailyCheck()`, default 90 via `BAKONG_DAILY_CHECK_LIMIT`) — every call from *any* source (live polling, the background job, future callers) increments one shared counter. Once at the limit, `checkTransactionByMd5()` returns `null` immediately without ever making the HTTP request, leaving a safety margin below Bakong's real 100 so the app's own usage never triggers Bakong's cutoff.
2. **Per-order throttle** (`CheckoutController::paymentStatus()`, `Cache::add("bakong-check:{$order->id}", true, 15)`) — real outbound calls for one order are capped at once per 15 seconds no matter how often the browser polls (fast retries, multiple open tabs).
3. **Client poll interval** (`success.blade.php`) — 15 seconds, capped at 40 attempts (~10 minutes) before the page stops polling on its own and tells the customer to refresh later rather than paying again.

**Real incident:** early testing (many end-to-end runs, each polling every 5 seconds before layer 3 existed) burned through the real 100/day quota within the same session real customer orders were being tested against — so *every* order, paid or not, showed "still waiting" until Bakong's quota reset, with no way to distinguish "not paid" from "can't check" from the outside. The layers above exist specifically so this can't recur from this app's own usage; it can still happen if Bakong's quota is simply too low for actual order volume, which is a business/support problem to raise with Bakong, not a code problem.

## Real failure mode: the background job starved new orders behind an old backlog

`app/Console/Commands/CheckPendingBakongPayments.php` originally selected the **oldest** unpaid orders first (`orderBy('created_at')`, 24h window). Once more than `--limit` (default 5) unpaid orders existed at once, the same oldest 5 were re-selected on every 5-minute run — a customer's brand-new order could never reach the front of the queue while any old abandoned carts remained unpaid. Fixed by switching to `orderByDesc('created_at')` with a 2-hour window: newest orders are prioritized (a customer waiting right now matters more than an old abandoned cart), and orders past 2 hours unpaid are assumed abandoned and stop consuming budget. Covered by `test_it_prioritizes_the_newest_orders_over_a_stale_backlog` in `tests/Feature/Console/CheckPendingBakongPaymentsTest.php`.

## The background auto-confirmation job

`php artisan bakong:check-pending {--limit=5}` — checks up to `--limit` unpaid Bakong orders (newest first, within the last 2 hours) against the real API and marks any confirmed ones paid. Exists so payment confirmation doesn't depend on a customer keeping their browser tab open (live polling only runs client-side for ~10 minutes) or an admin manually noticing.

Scheduled in `routes/console.php`:
```php
Schedule::command('bakong:check-pending --limit=5')->everyFiveMinutes();
```
For this to actually run, something has to invoke Laravel's scheduler continuously — `docker/entrypoint.sh` starts `php artisan schedule:work` in the background before `php-fpm`/`nginx`. **This only takes effect on a container rebuild/restart** (same rule as any other `docker/entrypoint.sh` change — see `DEPLOYMENT.md`'s "Local development" section on bind-mount limitations); an already-running container needs `docker exec -d <container> php artisan schedule:work` to pick it up without a restart.

## Manual override (when Bakong's API can't be reached at all)

The admin order page (`resources/views/admin/orders/show.blade.php`, `AdminOrderController::update()`) has **two separate dropdowns** that are easy to conflate:
- **Status** — fulfillment state (`pending`/`processing`/`shipped`/`completed`/`cancelled`). Has no effect on payment.
- **Payment** — `unpaid`/`paid`/`refunded`/`failed`. Setting this to **Paid** is what stamps `payment_confirmed_at` and is what `checkout/success.blade.php` actually checks (`payment_status !== 'paid'`) to decide whether to keep showing the QR/waiting modal.

**Real incident:** an admin changed Status to "Completed" expecting it to mark the order paid; `payment_status` stayed `unpaid` and the customer-facing page kept showing "still waiting" indefinitely. If a customer reports this, check both fields on the order directly (`payment_status` is the one that matters), not just whether *an* update happened.

This manual path exists as a fallback for when Bakong's real API is unreachable (quota exhausted, sustained DNS failure, etc.) — it is **not** a substitute for the automatic flow above, and shouldn't be needed once Bakong's quota is production-sized and DNS is stable.

## The success popup

Originally, detecting `is_paid: true` just closed the payment modal silently. `resources/views/checkout/success.blade.php`'s `markPaid()` now rewrites the modal's head/body in place into a "Payment successful!" state (checkmark, order number, amount, a **Continue** button routing to `account.orders`) instead of just closing it, so the customer gets a visible confirmation rather than the modal quietly disappearing. Verified with a real headless-Chrome run: loaded the success page while unpaid, flipped the order to `paid` in the database mid-session, and confirmed the modal transformed automatically within one 15-second poll — no page reload needed.

## Database schema

Added across `database/migrations/2026_06_06_000001_add_bakong_checkout_to_orders_table.php` and `..._000002_add_bakong_qr_fields_to_orders_table.php`:

| Column | Purpose |
| --- | --- |
| `bakong_session_id`, `bakong_checkout_url` | From an earlier hosted-checkout-iframe design; **unused by the current flow** (no code path sets or reads `bakong_checkout_url` today — `success.blade.php`'s `$hasCheckoutUrl` branch was removed). Left in place rather than migrated out since dropping columns is harder to reverse than leaving unused ones. |
| `bakong_qr_string` | The full KHQR payload text, rendered client-side as the scannable QR. |
| `bakong_qr_md5` | MD5 of `bakong_qr_string` — the value sent to `check_transaction_by_md5`. |
| `payment_status`, `payment_confirmed_at`, `admin_payment_seen_at` | Pre-existing generic order columns, not Bakong-specific — `payment_confirmed_at` is stamped by both the automatic flow and the admin manual-paid path. |

## Configuration reference

| Variable | Required | Default | Notes |
| --- | --- | --- | --- |
| `BAKONG_ACCOUNT_USERNAME` | Yes | — | The individual Bakong account alias (e.g. `sin_tino@bkrt`). `BakongService::isConfigured()` is `false` without this — checkout silently falls back to showing "KHQR payment is not configured" instead of a QR. |
| `BAKONG_ACCESS_TOKEN` | Yes (for verification) | — | Bearer token for the Open API. QR *generation* works without it (it's local); only `checkTransactionByMd5()` needs it. |
| `BAKONG_ACCOUNT_NAME` | No | `CEC Electronic` | Merchant name field in the QR (tag `59`), truncated to 25 chars. |
| `BAKONG_MERCHANT_CITY` | No | `Phnom Penh` | Tag `60`, truncated to 15 chars. |
| `BAKONG_PROD_BASE_API_URL` | No | `https://api-bakong.nbc.gov.kh/v1` | Base for the transaction-check API. |
| `BAKONG_DAILY_CHECK_LIMIT` | No | `90` | This app's own safety cap on outbound checks/day, below Bakong's real ~100. Set to `0` to disable (not recommended). |

`.env.example` documents these with blank values (no secrets); `config/services.php`'s `bakong` array is the single source of truth for how each maps to config.

**Superseded documentation:** `PROJECT.md`'s payment-methods section (around line 80) and `DEPLOYMENT.md`'s environment-variables table both describe an **earlier version** of this integration — a "Bakong relay" concept (`BAKONG_RELAY_URL`, `BAKONG_ACCOUNT_ID`, QR-to-PNG conversion, a hosted web-checkout flow, a `/webhooks/bakong` route) and state that the payment option was removed from checkout entirely. None of that matches the current code (no relay, no webhook route, KHQR generated locally, checkout option present in `checkout/create.blade.php`). Treat this document as current; those two sections predate this rebuild and should be updated or removed rather than trusted.

## Testing

- `tests/Unit/Services/BakongServiceTest.php` — `isConfigured()`, QR generation/MD5 correctness, transaction-check response handling (success/not-found/error), the daily budget guard (respects the limit, `0` disables it), and the DNS-failure-returns-null-not-throws case.
- `tests/Feature/Services/CheckoutServiceTest.php` — order creation flow including the `bakong` payment method path.
- `tests/Feature/Console/CheckPendingBakongPaymentsTest.php` — the background job: confirms paid orders, leaves unconfirmed ones alone, ignores orders older than 2 hours, respects `--limit`, and prioritizes newest-first over a stale backlog.

```bash
php artisan test --filter="BakongServiceTest|CheckoutServiceTest|CheckPendingBakongPaymentsTest"
```
All of the above run against `Http::fake()` — none hit the real Bakong API, so they can't consume the real daily quota and stay deterministic regardless of Bakong's live availability.

## Where `BAKONG_ACCESS_TOKEN` comes from

Self-registered at **https://api-bakong.nbc.gov.kh/register** — NBC's public Bakong Open API developer portal. This is a self-service sign-up (no business/merchant vetting), which is almost certainly *why* the token is capped at ~100 requests/day (see above) — it's the general-developer tier, not a negotiated production integration.

The token itself is a JWT with a fixed lifetime, decodable without hitting the network:
```
{"data":{"id":"a6ca82b612304801"},"iat":1789282726,"exp":1797058726}
```
`exp - iat` = exactly **90 days**. The current token was issued 2026-09-13 and expires **2026-12-12** — it needs to be re-obtained from the same registration page before then, or every Bakong API call in this app (`check_transaction_by_md5`) starts failing auth. There's no code-level warning for this yet; worth adding a reminder (e.g. a scheduled check that logs a warning inside some window of `exp`) before this becomes a silent production outage.

## Open questions for production readiness

1. **Individual vs. merchant account.** `BAKONG_ACCOUNT_USERNAME` is a personal/individual Bakong account (`KhqrGenerator::individual()`), not a registered Bakong merchant. It has not been confirmed end-to-end (blocked by the quota exhaustion above) whether `check_transaction_by_md5` reliably finds transactions paid to an individual account the same way it's documented to for merchant KHQR — Bakong's transaction-reporting API is generally described around merchant integrations. If automatic confirmation continues to fail even once quota and DNS are both fine, this is the next thing to test, and registering a proper Bakong Business/merchant account (with `KhqrGenerator`'s not-yet-used merchant-mode fields) may be required.
2. **Quota.** The self-service portal above is the likely source of the ~100/day cap. Producing a real production integration probably means going through Bakong/NBC's business/merchant onboarding (not just this developer registration page) rather than "upgrading" the same self-service token — worth confirming directly with NBC rather than assuming either path.
3. **Token expiry.** 90 days from issue (see above) — needs a renewal process (manual re-registration today; nothing in this codebase tracks or warns about the expiry date yet).
4. **No webhook.** Everything here is polling-based (client + background job). If Bakong offers a push/webhook notification for merchant accounts, it would remove the quota pressure and the up-to-5-minute confirmation lag entirely — worth asking about if/when moving to a merchant account,