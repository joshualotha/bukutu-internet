# MikroTik Hotspot Configuration Guide

This guide explains how to configure your MikroTik router to work with the Buku Tu Internet billing system.

## Prerequisites

- A MikroTik router with Hotspot feature enabled
- RouterOS v6.45+ (v7 recommended)
- REST API access enabled
- Network access between the billing server and the router

---

## Step 1: Enable the MikroTik REST API

The billing system communicates with MikroTik via its REST API on port **8728** (HTTP) or **8729** (HTTPS).

### Via WinBox:

1. Open **WinBox** and connect to your router
2. Go to **IP → Services**
3. Ensure **www** (port 80) or **www-ssl** (port 443) is **enabled**
4. Restrict access to your billing server's IP:
   - Double-click the service → **Available From**: enter your server IP

### Via CLI (SSH):

```bash
# Enable web access (needed for REST API)
/ip service set www disabled=no
/ip service set www-ssl disabled=no

# Restrict to your billing server IP (recommended)
/ip service set www address=192.168.1.100/32
/ip service set www-ssl address=192.168.1.100/32
```

> **Note:** The REST API listens on the same port as the web interface (www = 80, www-ssl = 443). The billing system connects to **http://{router-ip}:{port}/rest/**.

---

## Step 2: Create an API User

Create a dedicated user for the billing system:

```bash
/user add name=bukutu-api password=StrongP@ssw0rd group=full
```

> **Important:** The user needs `full` permissions to manage hotspot users. In production, create a custom group with only necessary permissions.

---

## Step 3: Enable Hotspot

If you haven't already set up a hotspot:

```bash
# Create a hotspot on ether2 (adjust interface name)
/ip hotspot setup
```

Or manually configure:

```bash
# Create hotspot server
/ip hotspot profile add name=hotspot-profile use-radius=no
/ip hotspot add name=hotspot1 interface=ether2 address-pool=dhcp_pool1 profile=hotspot-profile

# Create hotspot users directory
/ip hotspot user profile add name=default shared-users=1
```

---

## Step 4: Configure Bandwidth Profiles

For each internet package you create in the billing system, create a matching MikroTik profile:

```bash
# 1 Mbps / 1 Mbps profile
/ip hotspot user profile add name=1M_1M rate-limit="1M/1M" shared-users=1

# 2 Mbps / 2 Mbps profile
/ip hotspot user profile add name=2M_2M rate-limit="2M/2M" shared-users=1

# 5 Mbps / 5 Mbps profile
/ip hotspot user profile add name=5M_5M rate-limit="5M/5M" shared-users=1

# 10 Mbps / 10 Mbps profile
/ip hotspot user profile add name=10M_10M rate-limit="10M/10M" shared-users=1

# Asymmetric profile: 5M upload / 10M download
/ip hotspot user profile add name=5M_10M rate-limit="5M/10M" shared-users=1

# Monthly unlimited profile (no speed limit)
/ip hotspot user profile add name=Unlimited
```

> **Profile naming convention:** The profile name in MikroTik must match the `mikrotik_profile` field in the billing system's Package configuration.

---

## Step 5: Configure Hotspot Wall Garden (Captive Portal)

Configure MikroTik to redirect users to the captive portal:

```bash
# Add the billing server to the wall garden (bypass hotspot login)
/ip hotspot walled-garden add dst-host=*.your-domain.com
/ip hotspot walled-garden add dst-host=your-server-ip

# Configure hotspot to redirect to your landing page
/ip hotspot profile set [find] hotspot-address=0.0.0.0
/ip hotspot set [find] address-pool=dhcp_pool1
```

The hotspot will redirect users to:

```
http://{your-server}/?mac={mac-address}&ip={ip-address}
```

---

## Step 6: Add the Router to the Billing System

1. Log in to the admin dashboard at **http://your-server/admin**
2. Go to **Network → Routers**
3. Click **New Router**
4. Enter:
   - **Name:** A friendly name (e.g., "Office Router")
   - **IP Address:** The router's IP address
   - **API Port:** `8728` (default, or 80 if using HTTP)
   - **Username:** The API user (`bukutu-api`)
   - **Password:** The API user's password
5. Save the router
6. Click **Test Connection** to verify

---

## Step 7: Configure Packages

1. Go to **Configuration → Packages**
2. Click **New Package**
3. Set:
   - **Name:** e.g., "1 Hour", "Daily", "Weekly"
   - **Price:** Amount in local currency
   - **Duration:** In minutes (60 = 1 hour, 1440 = 1 day, etc.)
   - **Upload/Download Speed:** For display purposes
   - **MikroTik Profile:** Must match the profile name on the router (e.g., `1M_1M`)

---

## Authentication Flow

```
1. Customer connects to WiFi
2. MikroTik Hotspot redirects to: http://portal/?mac=AA:BB:CC:DD:EE:FF&ip=192.168.88.x
3. Customer selects a package and pays via Pesapal
4. System calls Pesapal:submitOrder() → redirect to Pesapal payment page
5. Customer completes payment on Pesapal
6. Pesapal sends IPN to /webhook/pesapal/ipn
7. System verifies payment → calls MikroTik:authorizeByMac(mac, profile)
8. Customer is granted internet access
9. At expiry, system calls MikroTik:deauthorizeByMac(mac)
10. Customer is disconnected
```

---

## API Methods Used by the System

| Method | MikroTik Endpoint | Purpose |
|--------|-------------------|---------|
| `testConnection()` | `GET /rest/system/resource` | Check router connectivity |
| `authorizeByMac()` | `PUT /rest/ip/hotspot/user` + hotspot bypass | Grant access to customer |
| `deauthorizeByMac()` | Remove from bypass list | Revoke access |
| `getActiveUsers()` | `GET /rest/ip/hotspot/active` | List connected users |
| `disconnectSession()` | `DELETE /rest/ip/hotspot/active/{id}` | Force disconnect |
| `getSystemResources()` | `GET /rest/system/resource` | Router health monitoring |
| `createHotspotUser()` | `PUT /rest/ip/hotspot/user` | Pre-provision user |
| `enableHotspotUser()` | `PATCH /rest/ip/hotspot/user/{id}` | Enable disabled user |
| `disableHotspotUser()` | `PATCH /rest/ip/hotspot/user/{id}` | Suspend user |

---

## Security Best Practices

1. **Use a dedicated API user** with minimal required permissions
2. **Restrict API access** to the billing server's IP only
3. **Use HTTPS** for the API connection (port 8729) in production
4. **Create a firewall rule** to block external access to ports 80/8728
5. **Regularly audit hotspot users** and remove stale entries
6. **Set up logging** to monitor failed API authentication attempts

## Troubleshooting

**"Connection refused" when testing:**
```bash
# On the router, verify the service is running:
/ip service print
# Check if the port is accessible from your server:
telnet {router-ip} 8728
```

**"Authentication failed":**
```bash
# Verify the username and password:
/user print
# If the password has special characters, try a simpler one:
/user set bukutu-api password=newpassword
```

**Hotspot redirect not working:**
```bash
# Verify hotspot configuration:
/ip hotspot print
/ip hotspot server print
# Check that the hotspot server is enabled
```

**Users not getting disconnected after expiry:**
```bash
# Check the billing server can reach the router:
ping {router-ip}
# Ensure the API user has permission to modify hotspot settings:
/user print detail where name=bukutu-api
```
