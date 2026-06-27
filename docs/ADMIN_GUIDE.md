# Admin Dashboard User Guide

A comprehensive guide to managing your hotspot billing system through the Filament admin dashboard.

**URL:** `http://your-server/admin`
**Default Login:** `admin@bukutu.co.tz` / `password`

---

## Dashboard Overview

The dashboard is the first page after login. It provides real-time statistics and monitoring.

### Stats Cards (Top Row)

| Stat | Description |
|------|-------------|
| Total Customers | All registered hotspot users |
| Active Sessions | Currently online customers |
| Revenue Today | Sales amount for today |
| Revenue This Month | Sales amount for current month |
| Pending Payments | Unconfirmed payment orders |
| Expired Today | Sessions expired today |

### Charts (Middle Section)

- **Revenue Chart** — Line chart showing daily revenue for the last 30 days
- **Active Sessions Chart** — Hourly active session count for the last 30 days
- **Popular Packages** — Bar chart of top-selling packages

### Recent Orders

A table showing the 10 most recent orders with customer name, package, amount, and status.

### Router Status

Shows each configured router with its connection status (online/offline) and last seen time.

### Failed Payments

Lists recent failed payment attempts with customer details and error information.

---

## Navigation Menu

The sidebar is organized into groups:

### Sales
| Resource | Purpose |
|----------|---------|
| **Orders** | View all orders, filter by status/date, manual verification |
| **Payments** | View payment records, export data, view full API payloads |
| **Reports** | Generate and export CSV, Excel, PDF reports |

### Network
| Resource | Purpose |
|----------|---------|
| **Routers** | Add/manage MikroTik routers, test connections |
| **Active Sessions** | Monitor active sessions, suspend/extend/disconnect |

### Monitoring
| Resource | Purpose |
|----------|---------|
| **Webhook Logs** | View Pesapal IPN webhook payloads and processing status |

### Configuration
| Resource | Purpose |
|----------|---------|
| **Packages** | Create/edit internet packages with pricing, duration, and speed profiles |
| **Customers** | Manage customer accounts, view session history |

### System
| Resource | Purpose |
|----------|---------|
| **Admin Logs** | Audit trail of all admin actions |

---

## Managing Packages

### Create a Package

1. Go to **Configuration → Packages**
2. Click **New Package**
3. Fill in:
   - **Name** — Display name (e.g., "1 Hour", "Daily", "Weekly")
   - **Description** — Optional description shown on the portal
   - **Price** — Amount in local currency
   - **Duration (minutes)** — How long the access lasts
   - **Upload Speed** — Display label (e.g., "5M")
   - **Download Speed** — Display label (e.g., "10M")
   - **MikroTik Profile** — Must match a profile on your MikroTik router
   - **Active** — Toggle to enable/disable
   - **Sort Order** — Display order (lower numbers appear first)

### Tips

- Use clear, customer-friendly names
- Ensure MikroTik profile names match exactly on the router
- Disable a package instead of deleting it (preserves order history)
- Sort order controls the display sequence on the captive portal

---

## Managing Routers

### Add a Router

1. Go to **Network → Routers**
2. Click **New Router**
3. Fill in:
   - **Name** — Friendly label (e.g., "Main Office")
   - **IP Address** — Router's IP (e.g., `192.168.88.1`)
   - **API Port** — Usually `8728` (REST API) or `80` (HTTP)
   - **Username** — API user on the router
   - **Password** — Encrypted on save
   - **Location** — Optional physical location
   - **Active** — Must be active for API calls
4. Click **Save**

### Test Connection

After saving a router, click **Test Connection** to verify:
- ✅ The router is reachable via the network
- ✅ The API user credentials are correct
- ✅ The REST API service is enabled

### Connection Status

- **Online** (green) — Router responded successfully
- **Offline** (red) — Router unreachable or authentication failed
- **Unknown** (gray) — Not yet tested

---

## Managing Orders

The **Orders** page is read-only (viewing). You can:

- **Filter** by status (pending, paid, failed, expired, refunded)
- **Filter** by date range
- **Filter** by payment method
- **View** full order details with customer and package info
- **View Payment** — See the payment record associated with the order
- **Manual Verify** — Manually trigger payment verification with Pesapal

### Order Statuses

| Status | Meaning |
|--------|---------|
| `pending` | Customer hasn't completed payment yet |
| `paid` | Payment confirmed, session activated |
| `failed` | Payment declined or failed |
| `expired` | Payment window expired |
| `refunded` | Order was refunded |

---

## Managing Sessions

### View Active Sessions

1. Go to **Network → Active Sessions**
2. Filter by status (active, expired, suspended)
3. Filter by router
4. Click a session to view details

### Session Actions

| Action | Effect |
|--------|--------|
| **Suspend** | Disables hotspot access (user can't browse). Can be resumed later. |
| **Extend** | Adds extra time to the session. Enter minutes (max 30 days). |
| **Disconnect** | Immediately ends the session. Cannot be resumed. |

### Session Statuses

| Status | Meaning |
|--------|---------|
| `active` | Customer has internet access |
| `expired` | Session time ran out |
| `suspended` | Admin suspended access |

---

## Generating Reports

1. Go to **Monitoring → Reports**
2. Select a **Start Date** and **End Date**
3. Click **Apply Filter**

The reports page shows:
- **Summary Cards**: Total Orders, Revenue, Paid/Failed/Pending counts
- **Customer Retention**: Total customers, returning, one-time, retention rate
- **Popular Packages**: Top packages by order count
- **Device Usage**: Which devices customers are using
- **Failed Payments**: Details of failed transactions

### Export Options

| Button | Format | Content |
|--------|--------|---------|
| **Export CSV** | CSV | All orders in date range |
| **Export Excel** | XLSX | All orders in date range |
| **Customers Excel** | XLSX | All customers in date range |
| **Payments Excel** | XLSX | All payments in date range |
| **Export PDF** | PDF | Formatted sales report with summary |

---

## Viewing Webhook Logs

1. Go to **Monitoring → Webhook Logs**
2. View all incoming Pesapal IPN requests
3. Filter by processed/unprocessed
4. Click a log entry to see the full payload

This is useful for debugging payment issues. Each log entry shows:
- **IPN Type** — Type of notification
- **Processed** — Whether it was successfully handled
- **Error Message** — If processing failed
- **Created At** — When it was received

---

## Admin Activity Logs

Every action by admin users is logged in the **Admin Logs** page:

- User creation/modification
- Package changes
- Session management (suspend/extend/disconnect)
- Router management
- Order/Payment actions

This provides a complete audit trail for security and compliance.

---

## Best Practices

1. **Monitor the Dashboard daily** — Check for failed payments and offline routers
2. **Regularly test router connections** — Ensure routers are reachable
3. **Back up your database** — Schedule regular MySQL backups
4. **Review webhook logs** — Investigate any payment processing errors
5. **Keep packages updated** — Adjust pricing and profiles as needed
6. **Monitor active sessions** — Watch for unusual activity
7. **Check admin logs** — Review for unauthorized access attempts
