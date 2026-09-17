# Postman Testing Guide

Complete reference for testing every route in this project with Postman. A ready-to-import collection covering all of this is at:

```
/tmp/claude-1000/-home-samnang-App-CEC-Electronic/0d03b053-10ff-4e92-91ac-499e87058a3c/scratchpad/cec-electronic-full.postman_collection.json
```

## 1. Server

Run the project via Docker (this is the correct, fully-configured way to run it — matches `www-data` file ownership, seeded DB, cached config):

```bash
cd /home/samnang/App/CEC-Electronic
APP_PORT=8081 docker compose up -d --build
```

Base URL for every request below: **`http://localhost:8081`**

`APP_PORT` defaults to `8080` in `docker-compose.yml`. We use `8081` locally because port `8080` is already taken by an unrelated project (a Spring Boot app) on this machine — pick whatever free port you like, just update the base URL to match.

## 2. CSRF — what's exempt vs. what isn't

This app is server-rendered (Blade + sessions), so every state-changing route (`POST`/`PUT`/`PATCH`/`DELETE`) normally requires a CSRF token tied to the session cookie, or Postman gets `419 Page Expired`.

**Already exempt from CSRF** (added specifically for easy API-style testing):
- `POST /register`
- `POST /login`
- `POST /logout`
- `POST /admin/login`
- `POST /admin/logout`

**Still CSRF-protected** (real protection left in place — cart, checkout/payment, and all admin product/order/pricing mutations):
- Everything else that isn't a `GET`.

> ⚠️ Security note: the exemption above is a real, permanent reduction in protection on those 5 routes (any external site could trigger a registration/login/logout via a visitor's browser). It was requested explicitly for this project. Revert by removing the `->withoutMiddleware([...])` calls in `routes/web.php` if that's no longer wanted.

### Handling CSRF for the protected routes

Two options:

1. **Use the provided Postman collection** — it has a collection-level pre-request script that automatically fetches a fresh CSRF token from the homepage and attaches it as `X-CSRF-TOKEN` to every request. Nothing to copy-paste.
2. **Manual per-request**: `GET /` (or any page), grab the token from `<meta name="csrf-token" content="...">` in the HTML, send it as header `X-CSRF-TOKEN: <token>` on your next request, using the same Postman cookie jar (same tab/session) so the session cookie carries over.

For the CSRF-exempt routes, none of this is needed — just send the request.

## 3. Auth flows

### Customer register
```
POST /register
Accept: application/json

{
    "name": "Test User",
    "email": "test-user-100@example.com",
    "password": "12345678",
    "password_confirmation": "12345678"
}
```
- `200` → `{"message": "Account created.", "redirect": "..."}`
- `422` → validation errors (e.g. email already taken)

### Customer login
```
POST /login
Accept: application/json

{
    "email": "test-user-100@example.com",
    "password": "12345678"
}
```
- `200` → `{"message": "Logged in successfully.", "redirect": "..."}`
- `422` → wrong credentials

### Customer logout
```
POST /logout
Accept: application/json
```
Requires the session cookie from a prior login (same Postman tab/run).

### Admin login
```
POST /admin/login
Accept: application/json

{
    "email": "admin@cecelectronic.com",
    "password": "your_private_admin_password"
}
```
Credentials come from `.env` → `ADMIN_EMAIL` / `ADMIN_PASSWORD`.
- `200` → `{"message": "Logged in successfully.", "redirect": "..."}`
- `422` → wrong credentials

### Admin logout
```
POST /admin/logout
Accept: application/json
```

## 4. Storefront (public, no auth, no CSRF — all GET)

| Route | Notes |
|---|---|
| `GET /` | Home |
| `GET /search?q=laptop&type=name` | `type` is `name`, `sku`, or `brand` |
| `GET /brands` | Brand list |
| `GET /brands/{slug}` | Single brand — slugs come from `config/brands.php` |
| `GET /category/{slug}` | e.g. `laptops`, `desktops`, `gaming`, `monitors`, `components`, `accessories`, `printers` |
| `GET /product/{slug}` | Look up a real slug via `GET /admin/products` first |

## 5. Cart (guest session, CSRF-protected, no login required)

| Route | Body |
|---|---|
| `GET /cart` | — |
| `POST /cart/{product}` | `{"quantity": 1}` (optional, defaults to 1) |
| `PATCH /cart/items/{cartItem}` | `{"quantity": 2}` (required; `0` removes the item) |
| `DELETE /cart/items/{cartItem}` | — |

`{product}` is a product ID. `{cartItem}` is a cart item ID (from the `GET /cart` response, or the JSON returned by the "Add to cart" call).

## 6. Checkout (requires customer login, CSRF-protected)

```
POST /checkout
Accept: application/json
X-CSRF-TOKEN: <token>

{
    "customer_name": "Test User",
    "customer_email": "test-user-100@example.com",
    "customer_phone": "012345678",
    "address_line_1": "123 Test St",
    "address_line_2": null,
    "city": "Phnom Penh",
    "province": null,
    "country": null,
    "shipping_method": "standard",
    "payment_method": "cod"
}
```
Required: `customer_name`, `customer_phone`, `address_line_1`, `city`. Everything else optional.
- Success → `302` redirect to `/checkout/success/{order}` (no JSON body branch on success for this one)
- `422` → validation errors

Other checkout routes:
- `GET /checkout` — cart summary page (redirects to login if not authenticated)
- `GET /checkout/success/{order}` — order confirmation page
- `GET /checkout/payment-status/{order}` — JSON: `{"order_number", "payment_status", "is_paid", "paid_at"}`

## 7. Account (requires customer login)

| Route | Notes |
|---|---|
| `GET /account` | Dashboard, last 10 orders |
| `GET /account/orders` | Paginated order list |
| `GET /account/orders/{order}` | Single order — 403 if it's not yours |

## 8. Admin — Products (requires admin login, mutations are CSRF-protected)

| Route | Body / Notes |
|---|---|
| `GET /admin` | Dashboard stats |
| `GET /admin/products` | Send `Accept: application/json` for a JSON page of products |
| `GET /admin/products/{product}` | Always JSON |
| `POST /admin/products` | `{"name": "...", "price": 199.99, "category_id": 1, "supplier_id": 1, "description": "...", "stock_quantity": 10, "sku": "...", "is_active": true, "is_featured": false}` — `name` and `price` required; `image` upload needs `multipart/form-data` instead of JSON |
| `PUT /admin/products/{product}` | Same fields as store |
| `DELETE /admin/products/{product}` | — |

## 9. Admin — Categories

| Route | Body |
|---|---|
| `GET /admin/categories` | — |
| `POST /admin/categories` | `{"name": "...", "parent_id": null, "description": "...", "is_active": true, "sort_order": 0}` — `name` required |
| `PUT /admin/categories/{category}` | Same fields |
| `DELETE /admin/categories/{category}` | — |

## 10. Admin — Orders

| Route | Body |
|---|---|
| `GET /admin/orders` | — |
| `GET /admin/orders/{order}` | Marks payment notification as seen as a side effect |
| `PUT /admin/orders/{order}` | `{"status": "shipped", "payment_status": "paid", "delivery_zone_id": null, "delivery_provider_id": null, "tracking_number": "...", "shipped_at": "2026-09-13", "delivered_at": null}` — `status` and `payment_status` required |

## 11. Admin — Customers (read-only)

| Route | Notes |
|---|---|
| `GET /admin/customers` | Grouped by `customer_phone`, paginated |
| `GET /admin/customers/{phone}` | 404 if no orders exist for that phone |

## 12. Admin — Suppliers

| Route | Body |
|---|---|
| `GET /admin/suppliers` | — |
| `POST /admin/suppliers` | `{"name": "...", "company_name": "...", "email": "...", "phone": "...", "website": "...", "address": "...", "contact_person": "...", "payment_terms": "...", "is_active": true, "notes": "..."}` — only `name` required |
| `PUT /admin/suppliers/{supplier}` | Same fields |
| `DELETE /admin/suppliers/{supplier}` | — |

## 13. Admin — Delivery Zones

| Route | Body |
|---|---|
| `GET /admin/delivery-zones` | — |
| `POST /admin/delivery-zones` | `{"name": "...", "city": "...", "province": "...", "delivery_fee": 200, "free_delivery_minimum": null, "estimated_days": 1, "is_active": true}` — only `name` required |
| `PUT /admin/delivery-zones/{deliveryZone}` | Same fields |
| `DELETE /admin/delivery-zones/{deliveryZone}` | — |

## 14. Webhook (server-to-server, CSRF-exempt by design — not for manual Postman testing)

`POST /webhooks/bakong` — called by the Bakong payment relay when a payment completes. Not meant to be tested manually; requires knowledge of an order's `bakong_session_id`.

## 15. Troubleshooting

| Symptom | Cause |
|---|---|
| `419 Page Expired` | Missing/stale CSRF token, or session cookie not carried between requests (use the same Postman tab/cookie jar) |
| `{"timestamp": ..., "status": 404, ...}` (Spring-Boot-shaped JSON) | You hit the wrong port — that's a different, unrelated Java project on this machine, not this Laravel app |
| `ECONNREFUSED` | The Docker container isn't running — run `docker compose up -d` and check `docker ps` |
| `403` on `/account/orders/{order}` or `/checkout/success/{order}` | The order doesn't belong to the logged-in customer |
| `404` on `/admin/customers/{phone}` | No orders exist yet for that phone number — create one via checkout first |
