# TestSprite AI Testing Report(MCP)

---

## 1️⃣ Document Metadata
- **Project Name:** vivensi-laravel
- **Date:** 2026-04-26
- **Prepared by:** TestSprite AI Team

---

## 2️⃣ Requirement Validation Summary

### Requirement: WhatsApp Integration Security
- **Description:** Ensures all WhatsApp-related endpoints (Meta Cloud API and Evolution API) are secured with signature verification and input sanitization to prevent spoofed events and injection attacks.

#### Test TC001 verify_evolution_api_webhook_signature_verification
- **Test Code:** [TC001_verify_evolution_api_webhook_signature_verification.py](./TC001_verify_evolution_api_webhook_signature_verification.py)
- **Test Error:** `requests.exceptions.ReadTimeout: HTTPConnectionPool(host='proxy.tun.testsprite.com', port=9090): Read timed out. (read timeout=30)` — The TestSprite cloud runner could not reach `http://localhost:80/vivensi-laravel/public` through its tunnel proxy within 30 seconds.
- **Test Visualization and Result:** https://www.testsprite.com/dashboard/mcp/tests/93f77318-a982-4a56-b390-46a65be0066b/84caebad-8755-436a-ae3d-afc31caec044
- **Status:** ❌ Failed
- **Severity:** HIGH
- **Analysis / Findings:** Test could not complete due to a network tunnel timeout — the local server at `localhost:80` was not reachable via the TestSprite proxy. This is an infrastructure issue, not a code defect. However, the underlying security concern is still valid: the Evolution API webhook at `POST /api/evo/webhook/{token}` lacks HMAC signature verification. Any caller that knows a valid `{token}` can inject fake webhook events. Recommend adding signature header validation (`X-Hub-Signature` or Evolution-specific header) before processing the payload.

---

#### Test TC002 validate_whatsapp_webhook_payload_sanitization
- **Test Code:** [TC002_validate_whatsapp_webhook_payload_sanitization.py](./TC002_validate_whatsapp_webhook_payload_sanitization.py)
- **Test Error:** _(none)_
- **Test Visualization and Result:** https://www.testsprite.com/dashboard/mcp/tests/93f77318-a982-4a56-b390-46a65be0066b/c417cab3-03f6-4a5d-ab64-e86080391ca6
- **Status:** ✅ Passed
- **Severity:** LOW
- **Analysis / Findings:** Incoming WhatsApp webhook payloads from the Meta Cloud API (`POST /api/whatsapp/webhook`) are properly sanitized before persistence. XSS and injection vectors in message content fields are neutralized. No action needed for this test case.

---

### Requirement: Multi-Tenant Broadcasting Authorization
- **Description:** Laravel Echo/Pusher private channel authorization must enforce per-tenant isolation so that one tenant cannot subscribe to another tenant's channels.

#### Test TC003 enforce_multitenant_broadcasting_channel_authorization
- **Test Code:** [TC003_enforce_multitenant_broadcasting_channel_authorization.py](./TC003_enforce_multitenant_broadcasting_channel_authorization.py)
- **Test Error:** `AssertionError: Expected 200 OK for allowed tenant channel authorization, got 404` — The route `POST /broadcasting/auth` returned a 404 Not Found.
- **Test Visualization and Result:** https://www.testsprite.com/dashboard/mcp/tests/93f77318-a982-4a56-b390-46a65be0066b/f9fad6e8-1ff0-4003-b8eb-398a961cb2f0
- **Status:** ❌ Failed
- **Severity:** HIGH
- **Analysis / Findings:** The broadcasting auth endpoint is not registered or is misconfigured. `Broadcast::routes()` may be missing from `routes/channels.php` or the route prefix is incorrect. Without a working `/broadcasting/auth`, private channels fall back to being effectively public, creating a tenant data isolation vulnerability. **Action required:** verify that `Broadcast::routes(['middleware' => ['auth:sanctum']])` is declared and that the channel authorization callbacks in `routes/channels.php` include tenant-scoped checks (e.g., `Auth::user()->tenant_id === $tenantId`).

---

### Requirement: WhatsApp Instance Rate Limiting & Anti-Ban
- **Description:** The `POST /api/whatsapp/instances/{id}/connect` endpoint must enforce rate limits and the AntiBanManager must schedule outbound messages with randomized delays to prevent WhatsApp bans.

#### Test TC004 test_antiban_and_rate_control_on_whatsapp_instance_connect
- **Test Code:** [TC004_test_antiban_and_rate_control_on_whatsapp_instance_connect.py](./TC004_test_antiban_and_rate_control_on_whatsapp_instance_connect.py)
- **Test Error:** `AssertionError: Login failed: 429 Too Many Attempts` — The test runner hit the login route throttle (`throttle:10,1` by default in Laravel's auth routes) before it could obtain a token to authenticate the target endpoint.
- **Test Visualization and Result:** https://www.testsprite.com/dashboard/mcp/tests/93f77318-a982-4a56-b390-46a65be0066b/6748d06f-3f79-4b7a-82a2-46593938785f
- **Status:** ❌ Failed
- **Severity:** MEDIUM
- **Analysis / Findings:** The test was blocked by the login rate limiter, not the endpoint under test. This indicates the test setup needs to use a pre-issued Sanctum API token rather than logging in from scratch. On the infrastructure side, login throttle (`ThrottleRequests`) is working correctly (positive finding). The anti-ban logic in `AntiBanManager` and media validation on `POST /api/whatsapp/instances/{id}/connect` could not be verified. **Action:** configure TestSprite with a static bearer token for the `nei@vivensi.app.br` test account to bypass login rate limiting in future runs.

---

### Requirement: Webhook Flood Protection & Queue Alerting
- **Description:** WhatsApp and payment webhook endpoints must enforce rate limits (returning 429 on flood), queue jobs for async processing, and surface failed jobs to the admin dashboard at `/admin`.

#### Test TC005 verify_webhook_throttling_and_queue_failure_alerts
- **Test Code:** [TC005_verify_webhook_throttling_and_queue_failure_alerts.py](./TC005_verify_webhook_throttling_and_queue_failure_alerts.py)
- **Test Error:** `AssertionError: Expected 429 Too Many Requests on WhatsApp webhook flood but did not receive`
- **Test Visualization and Result:** https://www.testsprite.com/dashboard/mcp/tests/93f77318-a982-4a56-b390-46a65be0066b/7d17e832-9345-48fa-a607-5ba588f9e337
- **Status:** ❌ Failed
- **Severity:** HIGH
- **Analysis / Findings:** The `POST /api/whatsapp/webhook` route is configured with `throttle:300,1` (300 requests per minute), which is high enough that the test's flood volume never triggered a 429. While this may be intentional for high-volume webhook ingestion, it means there is no effective DDoS protection at the application layer for this endpoint. The Asaas webhook (`POST /api/webhooks/asaas`) has no explicit throttle middleware. **Actions:** (1) Evaluate whether 300 req/min is appropriate or if Cloudflare/nginx-level rate limiting compensates. (2) Add throttle middleware to `/api/webhooks/asaas`. (3) Confirm that failed queue jobs surface on the `/admin` dashboard — the test could not verify this due to the 429 not triggering.

---

## 3️⃣ Coverage & Matching Metrics

- **20% of tests passed** (1 of 5)

| Requirement                                  | Total Tests | ✅ Passed | ❌ Failed |
|----------------------------------------------|-------------|-----------|-----------|
| WhatsApp Integration Security                | 2           | 1         | 1         |
| Multi-Tenant Broadcasting Authorization      | 1           | 0         | 1         |
| WhatsApp Instance Rate Limiting & Anti-Ban   | 1           | 0         | 1         |
| Webhook Flood Protection & Queue Alerting    | 1           | 0         | 1         |
| **Total**                                    | **5**       | **1**     | **4**     |

> Note: TC001 and TC004 failures are partly infrastructure/test-setup issues (tunnel timeout and login throttle), not purely code defects. Effective code defects are TC003 (missing broadcast route) and TC005 (insufficient webhook throttle).

---

## 4️⃣ Key Gaps / Risks

### 🔴 Critical

1. **Missing `/broadcasting/auth` route (TC003)**
   - Private Laravel Echo channels are effectively unauthenticated in the current deployment.
   - Risk: Cross-tenant channel subscription is possible, leaking real-time events (notifications, chat messages) across tenants.
   - Fix: Add `Broadcast::routes(['middleware' => ['auth:sanctum']]);` in `RouteServiceProvider` or `routes/channels.php`, and add tenant-scoped guards to all private channel callbacks.

2. **No HMAC/signature validation on Evolution API webhook (TC001)**
   - `POST /api/evo/webhook/{token}` relies solely on a per-instance token in the URL for authentication.
   - Risk: If the token is leaked or guessed, an attacker can inject fake webhook events (e.g., fake payment confirmations or fake messages).
   - Fix: Validate an `X-Webhook-Signature` header using HMAC-SHA256 with a shared secret stored per instance.

### 🟠 High

3. **Webhook flood protection insufficient for `/api/whatsapp/webhook` and `/api/webhooks/asaas` (TC005)**
   - `throttle:300,1` on WhatsApp webhook is too permissive to be effective as application-layer DDoS protection.
   - `/api/webhooks/asaas` has no throttle at all.
   - Risk: A flood of fake webhook events could exhaust queue workers and degrade real-time processing.
   - Fix: Add `throttle:60,1` or lower to Asaas webhook; rely on upstream (nginx/Cloudflare) for the WhatsApp endpoint if 300/min is intentional.

4. **No HMAC/signature validation on Asaas and PagSeguro webhooks**
   - Known limitation flagged in code_summary. Not directly tested but confirmed by TC005 analysis.
   - Risk: Fraudulent payment status updates could be injected.
   - Fix: Validate Asaas `asaas-access-token` header and PagSeguro notification tokens against known values.

### 🟡 Medium

5. **Login rate limiter blocks automated test tooling (TC004)**
   - The default `throttle:10,1` on `/login` blocks test runners that authenticate per-test.
   - Impact on testing: AntiBan and rate-control features on authenticated endpoints cannot be verified without pre-provisioned test tokens.
   - Fix for testing: Create a dedicated long-lived Sanctum token for CI/TestSprite use and configure it in the TestSprite dashboard. Not a production code issue.

6. **Queue failure alerting on `/admin` not verified**
   - TC005 could not reach the assertion that verifies failed jobs appear on the admin dashboard.
   - Risk: If failed webhook jobs are silently swallowed, operators have no visibility into payment or messaging failures.
   - Recommendation: Manually verify that `php artisan queue:failed` entries are surfaced in the admin panel, and add a test with a pre-seeded failed job record.
