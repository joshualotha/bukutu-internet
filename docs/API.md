# API Documentation

Complete reference for all REST API endpoints in the Buku Tu Internet billing system.

## Base URL

```
Production: https://your-domain.com/api
Local:      http://localhost:8000/api
```

## Response Format

All API responses follow a consistent format:

```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error description"
}
```

Pagination:

```json
{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 5, "path": "...", "per_page": 20, "to": 20, "total": 100 }
}
```

---

## Public Endpoints (Captive Portal)

No authentication required. Used by the hotspot captive portal.

### Packages

#### `GET /api/portal/packages`

List all active packages sorted by sort order.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "1 Hour",
      "description": "High-speed internet for one hour",
      "price": 2000.00,
      "currency": "UGX",
      "duration_minutes": 60,
      "duration_label": "1 hour",
      "upload_speed": "5M",
      "download_speed": "10M",
      "mikrotik_profile": "5M_10M",
      "is_active": true,
      "sort_order": 0
    }
  ]
}
```

#### `GET /api/portal/packages/{id}`

Get details of a specific package.

**Parameters:** `id` — Package ID

**Response:** Single package object (same structure as above)

---

### Orders

#### `POST /api/portal/orders`

Create a new order and initiate payment.

**Request Body:**
```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "package_id": 1,
  "phone_number": "+256701234567",
  "full_name": "John Doe",
  "router_id": 1
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "order_reference": "ORD-A1B2C3D4",
      "customer": { ... },
      "package": { ... },
      "amount": 2000.00,
      "status": "pending",
      "pesapal_tracking_id": "tracking-id-from-pesapal",
      "created_at": "2026-06-27T10:00:00Z"
    },
    "redirect_url": "https://pay.pesapal.com/v3/..."
  }
}
```

**Errors:**
- `400` — Package inactive or Pesapal submission failed
- `422` — Validation error (missing/invalid fields)
- `500` — Server error

#### `GET /api/portal/orders/{reference}`

Check order status by order reference.

**Parameters:** `reference` — Order reference (e.g., `ORD-A1B2C3D4`)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_reference": "ORD-A1B2C3D4",
    "status": "paid",
    "amount": 2000.00,
    "customer": { ... },
    "package": { ... },
    "payments": [ ... ],
    "paid_at": "2026-06-27T10:05:00Z"
  }
}
```

---

### Sessions

#### `GET /api/portal/session/{mac}`

Get active session for a MAC address.

**Parameters:** `mac` — MAC address (e.g., `AA:BB:CC:DD:EE:FF`)

**Response:**
```json
{
  "success": true,
  "data": {
    "session": {
      "id": 1,
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "mikrotik_username": "bt_aabbccddeeff",
      "mikrotik_profile": "5M_10M",
      "start_time": "2026-06-27T10:00:00Z",
      "expiry_time": "2026-06-27T11:00:00Z",
      "time_remaining_seconds": 3600,
      "time_remaining_formatted": "01:00:00",
      "is_active": true,
      "is_expired": false,
      "status": "active",
      "package": { ... }
    },
    "time_remaining": 3600,
    "is_active": true
  }
}
```

#### `POST /api/portal/auth/check`

Check if a MAC address has authorized internet access.

**Request Body:**
```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF"
}
```

**Response:**
```json
{
  "success": true,
  "authorized": true,
  "session": {
    "id": 1,
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "expires_at": "2026-06-27T11:00:00Z",
    "time_remaining": 3600,
    "package_name": "1 Hour",
    ...
  }
}
```

---

### Users

#### `GET /api/portal/user/{mac}`

Get customer information by MAC address.

**Parameters:** `mac` — MAC address

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "phone_number": "+256******67",
    "email": "john@example.com",
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "ip_address": "192.168.88.100",
    "device_name": "iPhone",
    "created_at": "2026-06-27T10:00:00Z"
  }
}
```

#### `POST /api/portal/user/update`

Update customer profile information.

**Request Body:**
```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "full_name": "John Doe",
  "phone_number": "+256701234567",
  "email": "john@example.com",
  "device_name": "iPhone"
}
```

All fields except `mac_address` are optional.

**Response:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Profile updated"
}
```

---

## Customer Endpoints (Authenticated)

Requires `auth:sanctum` middleware. Include Bearer token in the `Authorization` header.

### Profile

#### `GET /api/customer/profile`
#### `PUT /api/customer/profile`

### Sessions

#### `GET /api/customer/sessions` — Paginated session history
#### `GET /api/customer/sessions/{id}` — Session details

### Orders

#### `GET /api/customer/orders` — Paginated order history
#### `GET /api/customer/orders/{id}` — Order details
#### `POST /api/customer/orders` — Create new order

### Devices

#### `GET /api/customer/devices` — Connected devices list

---

## Admin Endpoints (Authenticated, Admin Only)

Requires `auth:sanctum` + `can:admin-access` middleware.

### Dashboard

#### `GET /api/admin/dashboard/metrics`
```json
{
  "success": true,
  "data": {
    "total_customers": 150,
    "active_sessions": 42,
    "revenue_today": 250000,
    "revenue_this_month": 5250000,
    "pending_payments": 3,
    "total_routers": 2,
    "online_routers": 2,
    "offline_routers": 0,
    "expired_sessions_today": 15
  }
}
```

#### `GET /api/admin/dashboard/charts`
```json
{
  "success": true,
  "data": {
    "revenue_chart": { "labels": [...], "data": [...] },
    "active_users_chart": { "labels": [...], "data": [...] },
    "popular_packages": { "labels": [...], "data": [...] }
  }
}
```

### Routers

#### `GET /api/admin/routers/{id}/test` — Test router connectivity
#### `POST /api/admin/routers/{id}/sync` — Sync active users from router

### Sessions

#### `POST /api/admin/sessions/{id}/suspend` — Suspend an active session
**Request:** `{"reason": "Abuse"}`
#### `POST /api/admin/sessions/{id}/extend` — Extend session duration
**Request:** `{"minutes": 60}`

### Payments

#### `POST /api/admin/payments/{id}/refund` — Refund a payment
**Response:** `{"success": true, "message": "Payment refunded successfully"}`

---

## Webhook Endpoints

### Pesapal IPN

#### `POST /webhook/pesapal/ipn`

Receives payment notifications from Pesapal. This endpoint is **not** under `/api` prefix and is **excluded from CSRF protection**.

**Request (from Pesapal):**
```json
{
  "OrderTrackingId": "tracking-id",
  "OrderMerchantReference": "merchant-ref",
  "ipn_type": "payment_complete"
}
```

**Response:** Always returns HTTP `200 OK` regardless of processing result.

---

## API Resources (Response Shapes)

### CustomerResource
```json
{
  "id": 1,
  "full_name": "John Doe",
  "phone_number": "+256******67",
  "email": "john@example.com",
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "ip_address": "192.168.88.100",
  "device_name": "iPhone",
  "router": { ... },
  "orders_count": 5,
  "active_sessions_count": 1,
  "created_at": "2026-06-01T10:00:00Z",
  "updated_at": "2026-06-27T10:00:00Z"
}
```

### PackageResource
```json
{
  "id": 1,
  "name": "1 Hour",
  "description": "...",
  "price": 2000.00,
  "currency": "UGX",
  "duration_minutes": 60,
  "duration_label": "1 hour",
  "upload_speed": "5M",
  "download_speed": "10M",
  "mikrotik_profile": "5M_10M",
  "is_active": true,
  "sort_order": 0
}
```

### OrderResource
```json
{
  "id": 1,
  "order_reference": "ORD-A1B2C3D4",
  "customer": { ... },
  "package": { ... },
  "router": { ... },
  "payments": [ ... ],
  "active_sessions": [ ... ],
  "amount": 2000.00,
  "currency": "UGX",
  "status": "paid",
  "status_label": "Paid",
  "payment_method": "mobile_money",
  "pesapal_tracking_id": "tracking-id",
  "transaction_reference": "ref-123",
  "paid_at": "2026-06-27T10:05:00Z",
  "expired_at": null,
  "created_at": "2026-06-27T10:00:00Z"
}
```

### ActiveSessionResource
```json
{
  "id": 1,
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "mikrotik_username": "bt_aabbccddeeff",
  "mikrotik_profile": "5M_10M",
  "start_time": "2026-06-27T10:00:00Z",
  "expiry_time": "2026-06-27T11:00:00Z",
  "time_remaining_seconds": 3600,
  "time_remaining_formatted": "01:00:00",
  "status": "active",
  "is_active": true,
  "is_expired": false,
  "disconnected_at": null,
  "created_at": "2026-06-27T10:00:00Z"
}
```

### PaymentResource
```json
{
  "id": 1,
  "order_id": 1,
  "amount": 2000.00,
  "currency": "UGX",
  "provider": "pesapal",
  "provider_reference": "merchant-ref",
  "provider_tracking_id": "tracking-id",
  "payment_method": "mobile_money",
  "phone_number": "+256******67",
  "status": "paid",
  "confirmation_code": "conf-123",
  "payment_time": "2026-06-27T10:05:00Z",
  "created_at": "2026-06-27T10:00:00Z"
}
```

### RouterResource
```json
{
  "id": 1,
  "name": "Office Router",
  "ip_address": "192.168.88.1",
  "api_port": 8728,
  "location": "Kampala",
  "is_active": true,
  "connection_status": "online",
  "last_seen_at": "2026-06-27T10:00:00Z",
  "customers_count": 50,
  "active_sessions_count": 20,
  "created_at": "2026-06-01T10:00:00Z"
}
```

---

## Rate Limiting

All API endpoints are rate-limited:

| Endpoint Group | Limit |
|---------------|-------|
| Portal (public) | 60 requests per minute per IP |
| Customer (authenticated) | 100 requests per minute per user |
| Admin | 200 requests per minute per admin |
| Order creation | 10 requests per minute per IP (anti-abuse) |

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

On exceeding the limit, a `429 Too Many Requests` response is returned.
