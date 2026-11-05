# WordPress → Shopify Referral Tracking + n8n

A small local development project with:
- WordPress landing/referral website
- Shopify outbound redirect with click tracking
- n8n webhook to receive referral events
- MariaDB for WordPress and PostgreSQL for n8n

## 1. Start the stack

Requirements: Docker Desktop with Docker Compose.

```bash
cp .env.example .env
```

Edit `.env` and replace all example passwords. Then run:

```bash
docker compose up -d
```

Open:
- WordPress: http://localhost:8080
- n8n: http://localhost:5678

Complete the WordPress installation in the browser.

## 2. Configure the WordPress plugin

The plugin folder is mounted into WordPress. In WordPress Admin:
1. Open **Plugins** and activate **Shopify Referral Tracker**.
2. Open **Settings → Shopify Referral Tracker**.
3. Set your Shopify store URL, e.g. `https://your-store.myshopify.com`.
4. Set the n8n webhook URL to `http://n8n:5678/webhook/shopify-referral-click`.
   - This is the internal Docker network address. It is the correct address for WordPress to reach n8n.
5. Save settings.

If the plugin does not appear, check that the folder `wordpress-plugin/shopify-referral-tracker` exists and refresh Plugins.

## 3. Import and activate the n8n workflow

1. Sign in to n8n.
2. Choose **Overview → Workflows → Import from File**.
3. Select `n8n/workflows/shopify-referral-click.json`.
4. Save the workflow and activate it.
5. The production webhook path is `/webhook/shopify-referral-click`.

## 4. Add a referral button to WordPress

Create or edit a page and add:

```text
[shopify_referral_button label="Visit our store" campaign="homepage"]
```

When a visitor clicks:
1. WordPress receives `/go/shopify/`.
2. The plugin sends a click event to n8n without delaying the redirect.
3. The visitor is redirected to the configured Shopify store.

You can use a different campaign label, e.g. `summer-promo` or `blog-sidebar`.

## 5. Test the webhook

After activating the workflow, open the n8n production webhook URL from a browser-based API client or send a POST request from a terminal:

```bash
curl -X POST http://localhost:5678/webhook/shopify-referral-click \
  -H "Content-Type: application/json" \
  -d '{"event":"shopify_referral_click","source":"manual-test","campaign":"test"}'
```

Check the n8n Executions view for the received event.

## Important notes

- This starter tracks outbound clicks, not completed Shopify purchases or revenue.
- Purchase attribution requires a Shopify-side integration, app/webhook, or analytics setup.
- The visitor identifier is a salted, truncated hash of the IP address; it is pseudonymous, not anonymous. Add an appropriate privacy notice and obtain consent where required by applicable law.
- The example uses local HTTP and is intended for development. Use HTTPS, strong secrets, backups, and restricted access before public deployment.
- The n8n workflow is intentionally minimal: it receives and normalizes events. A next iteration can store events in a database, send notifications, or build a dashboard.
