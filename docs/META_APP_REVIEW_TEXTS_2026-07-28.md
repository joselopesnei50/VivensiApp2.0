# App Review — texts for NC5HUBDIGITAL-EMP (2026-07-28)

Copy-paste ready. All six requests in one submission, aligned to the same
server-to-server architecture that got rejected last time due to unclear
video (business_management).

**Common opening paragraph** — reuse in ALL six "Tell us how you're using"
fields. This is the fix for the previous rejection:

> **Server-to-server architecture (applies to all permissions requested):**
> This app is a server-to-server SaaS platform. The Facebook Login for
> Business front-end flow is used **only** to obtain an authorization code.
> The backend then exchanges the code for a long-lived System User Access
> Token and makes all subsequent Meta API calls server-side, from our
> production infrastructure (AWS Lightsail, https://vivensi.app.br). The
> end user does not interact with the Meta API directly at any point after
> login. Tokens are encrypted at rest with AES-256 and stored per-tenant
> in a MySQL 8 database with row-level tenant isolation.

---

## 1. `business_management` (RESUBMISSION — was rejected)

### Tell us how you're using this permission or feature

> **Server-to-server architecture:**
> [use common opening paragraph above]
>
> **Specific use case:**
> Vivensi uses `business_management` exclusively as a mandatory part of
> the Facebook Login for Business (Embedded Signup) flow for WhatsApp
> Business Cloud API onboarding. When a Vivensi client (NGO, small
> business, or self-employed professional) clicks "Connect WhatsApp
> Business" in our interface, we call `FB.login` with our Login
> Configuration. Meta returns an authorization code. Our backend then
> exchanges the code for a **System User Access Token** on behalf of the
> client. This token is used **exclusively** to access the client's
> WhatsApp Business assets (WABA IDs and phone number IDs) within their
> Business Portfolio, in order to persist `waba_id`, `phone_number_id`,
> and `graph_access_token` (AES-256 encrypted) in our `whatsapp_instances`
> table, associated with the client's tenant.
>
> **What we do NOT do:**
> We do not access ad accounts, page catalogs, or other business assets
> outside of WhatsApp. We do not use this permission for analytics,
> marketing, or aggregated/anonymized data queries. We only read the
> minimum required to enable the client's WhatsApp integration.
>
> **Why this permission is strictly necessary:**
> Meta requires `business_management` as a mandatory scope inside the
> Embedded Signup flow — without it, the generated System User Access
> Token does not authorize access to the client's WhatsApp Business
> assets, and the entire onboarding fails.
>
> **Note on previous rejection (2026-07-27):**
> The previous screencast did not show the complete server-side flow
> because our app was in Development Mode at the time and `FB.login`
> with the WhatsApp Embedded Signup configuration returned error
> `1349223 "JSSDK not enabled"` — a known Meta limitation for non-Live
> apps. This new screencast demonstrates the server-side flow using a
> System User Access Token issued to our own test business, which is
> the same code path executed for client onboarding once the app is
> Live. Per Meta's own guidance, when an app is server-to-server, the
> front-end interaction is documented rather than shown.

### Video walkthrough

*(reference the video URL you upload — see `META_VIDEO_SCRIPT_2026-07-28.md`)*

---

## 2. `whatsapp_business_messaging` (already approved — do NOT resubmit)

Already approved on 2026-07-27. No action needed.

---

## 3. `whatsapp_business_management` (already approved — do NOT resubmit)

Already approved on 2026-07-27. No action needed.

---

## 4. `pages_show_list`

### Tell us how you're using this permission or feature

> [use common opening paragraph above]
>
> **Specific use case:**
> After a Vivensi client authorizes Facebook Login for Business, our
> backend calls `GET /me/accounts?fields=id,name,picture,access_token,instagram_business_account`
> from our server to obtain the list of Facebook Pages the client
> administers. This list is displayed to the client inside Vivensi so
> they can choose which Page(s) to enable for automated content
> publishing. Without this permission we cannot show the client the list
> of their own Pages, which is the first step of connecting their social
> presence to our scheduling platform.
>
> **Data handling:**
> We persist only `page_id`, `page_name`, and page picture URL in our
> `social_accounts` table. The Page access token is stored encrypted
> (AES-256) and associated with the client's tenant.

### Video walkthrough

*(same video, timestamp 0:30 – 1:15 shows this call)*

---

## 5. `pages_read_engagement`

### Tell us how you're using this permission or feature

> [use common opening paragraph above]
>
> **Specific use case:**
> Vivensi displays post-publication engagement statistics (likes,
> comments count, reach) to the client inside the platform's post
> scheduling dashboard. This helps small businesses and NGOs measure
> the effectiveness of their scheduled content. The backend calls
> `GET /{page_id}/posts?fields=id,message,created_time,likes.summary(true),comments.summary(true),reactions.summary(true)`
> using the Page access token obtained via `pages_show_list`.
>
> **Data handling:**
> Engagement counts are cached for 1 hour and shown in a client-facing
> dashboard. We do not aggregate this data across clients, do not sell
> or share it, and do not use it for advertising.

### Video walkthrough

*(same video, timestamp 3:00 – 3:30 shows the engagement dashboard)*

---

## 6. `pages_manage_posts`

### Tell us how you're using this permission or feature

> [use common opening paragraph above]
>
> **Specific use case:**
> This is the core permission for Vivensi's content scheduling feature.
> Clients (NGOs, small businesses, freelancers) create post drafts inside
> Vivensi — with AI-assisted caption and image generation — and schedule
> them to publish on their Facebook Pages at a chosen date and time.
>
> **Flow:**
> 1. Client creates a scheduled post via `/social/posts/create` with
>    caption, image, and target Page(s).
> 2. Our Laravel scheduler (Supervisor-managed worker) picks up the
>    scheduled post at the exact time.
> 3. Backend calls `POST /{page_id}/photos` (with image) or
>    `POST /{page_id}/feed` (text only), using the Page access token.
> 4. Meta returns the post ID. Vivensi persists it in our
>    `scheduled_posts.facebook_post_id` column and marks the post as
>    published.
> 5. Client sees confirmation in the dashboard and can view the live
>    post on Facebook.
>
> **Why the permission is essential:**
> Without `pages_manage_posts`, the client cannot use the core value
> proposition of our platform (scheduled publishing). Manually posting
> at odd hours is exactly the pain point Vivensi solves for small
> organizations without dedicated social media staff.
>
> **Data handling:**
> Post content (caption, image) is stored in our database only to
> preserve history and re-publishing capability. We do not repurpose
> client content elsewhere and delete it on client request per LGPD.

### Video walkthrough

*(same video, timestamp 2:00 – 2:45 shows scheduling + immediate publish)*

---

## 7. `instagram_basic`

### Tell us how you're using this permission or feature

> [use common opening paragraph above]
>
> **Specific use case:**
> Vivensi needs to display the client's Instagram Business account
> username and profile picture inside the platform when a client
> connects their Facebook Page that has a linked Instagram Business
> account. The backend calls
> `GET /{page_id}?fields=instagram_business_account` and then
> `GET /{ig_id}?fields=id,username,profile_picture_url` to fetch the
> minimum identification data. This confirms to the client that the
> right Instagram account is connected before they schedule any content.
>
> **Data handling:**
> We persist only `instagram_business_id` and `instagram_username` in
> our `social_accounts` table.

### Video walkthrough

*(same video, timestamp 1:15 – 1:30 shows IG account being detected
after Page connection)*

---

## 8. `instagram_content_publish`

### Tell us how you're using this permission or feature

> [use common opening paragraph above]
>
> **Specific use case:**
> Same scheduling feature as `pages_manage_posts`, extended to Instagram
> Business accounts. When the client schedules a post targeting Instagram,
> our backend uses the two-step Instagram Content Publishing API:
>
> **Flow:**
> 1. `POST /{ig_business_id}/media` with `image_url` (a temporary URL
>    from our media storage) + `caption` — Meta returns a `container_id`.
> 2. `POST /{ig_business_id}/media_publish` with `creation_id={container_id}`
>    — Meta publishes and returns the IG post ID.
> 3. Vivensi persists `instagram_post_id` in `scheduled_posts` and marks
>    published.
>
> **Why the permission is essential:**
> Same rationale as `pages_manage_posts` — scheduled publishing is the
> core value delivered by Vivensi to small organizations. Without this
> permission, IG posting is impossible via the API.
>
> **Data handling:**
> Same as `pages_manage_posts` — content stored only for history and
> re-publishing, deleted on client request per LGPD.

### Video walkthrough

*(same video, timestamp 4:00 – 4:45 shows IG scheduling + publish)*

---

## Data handling section (mostly unchanged from last submission)

- **Data controller:** NC5 HUB DIGITAL LTDA (Brazil)
- **Data processors:** No — Vivensi operates on its own infrastructure
- **Provided personal data to public authorities (past 12 months):** No
- **LGPD policies in place:** all four checkboxes:
  - Provisions for challenging unlawful requests
  - Data minimization policy
  - Documentation of requests
  - Required review of legality

## Web reviewer instructions

*Same as the previous submission — testing credentials remain valid:*

> **How to access Vivensi for review:**
> URL: https://vivensi.app.br/login
> Email: metareview@vivensi.app.br
> Password: password
>
> **What this app does with Meta APIs:**
> 1. Facebook Login for Business — Embedded Signup for WhatsApp
>    Business Cloud API
> 2. WhatsApp Business Cloud API — messages, templates, subscribed_apps,
>    register
> 3. Business Manager API — `/me/businesses` for asset verification
> 4. Facebook Pages API — list, read engagement, publish posts
> 5. Instagram Graph API — read business account, publish content
>
> Facebook Login for Business is used ONLY inside the Embedded Signup
> flow for WhatsApp and inside the "Connect Social Accounts" flow for
> Pages/Instagram publishing. It is NOT the primary authentication
> method for Vivensi (that is email+password).
>
> **Testing steps:**
>
> Test 1 — WhatsApp (business_management, whatsapp_business_messaging,
> whatsapp_business_management):
> 1. Login at https://vivensi.app.br/login
> 2. Sidebar → WhatsApp → "Conectar WhatsApp" (or
>    https://vivensi.app.br/whatsapp/cloud/connect)
> 3. Click "Conectar com Facebook" — FB.login initiates Embedded Signup
> 4. Backend exchanges code for System User Access Token
> 5. Access /whatsapp/cloud/templates to manage templates
> 6. Templates hello_world already approved — click "Enviar teste"
>    to send a real WhatsApp message
>
> Test 2 — Social publishing (pages_show_list, pages_read_engagement,
> pages_manage_posts, instagram_basic, instagram_content_publish):
> 1. Sidebar → Marketing → Redes Sociais → "Contas Conectadas" (or
>    https://vivensi.app.br/social/accounts)
> 2. Click "Conectar Facebook" — OAuth dialog appears with all requested
>    scopes
> 3. After authorization, backend fetches Pages via `/me/accounts` and
>    IG Business via `/{page_id}?fields=instagram_business_account`
> 4. Sidebar → Redes Sociais → "Novo post" — create a post targeting FB
>    Page and/or IG Business
> 5. Choose "Publicar agora" — worker picks up and publishes
> 6. Post appears live on the actual Facebook Page and/or Instagram
>    Business account
>
> **Infrastructure:**
> - Test WABA ID: 1331534925713250 / Phone Number ID: 1168655716337509
> - Test phone: +1 (555) 167-1404
> - Multi-tenancy at DB level (tenant_id column in all business tables)
> - Credentials encrypted AES-256 (waba_id, phone_number_id, tokens)
> - System User Access Token per client (never a global Vivensi token)
> - Webhook validated via HMAC-SHA256 at /api/whatsapp/cloud-webhook
>
> Technical contact: contato@vivensi.app.br

## Is Facebook Login integrated on this platform?

**Yes**

## Payment / geo-blocking / app store questions

- **Payment:** N/A — Vivensi is a web-based SaaS, not distributed via
  app stores. No payment required for reviewer access.
- **Geo-blocking:** N/A — Vivensi is globally accessible at
  https://vivensi.app.br without geographic restrictions.
- **Supporting documentation:** *(optional)* — attach the same video as
  a `.mp4` file if the platform allows in addition to the video URL.
