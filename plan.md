# Buku Tu Internet — Implementation Plan (PHPNuxBill + Pesapal on Shared Hosting)

> **STATUS: Router Reconfigured — Ready to Test**
>
> **Approach:** PHPNuxBill running on shared hosting with MySQL, talking directly to MikroTik via MikroTik API. Pesapal plugin for payments. No Docker, no FreeRADIUS.

---

## Phase 1: Shared Hosting Setup

- [x] Upload `src/` contents to shared hosting (public_html or subfolder)
- [x] Create MySQL database via cPanel (or hosting control panel)
- [x] DB configured at config.php
- [x] PHPNuxBill installed and running at https://test.africanpishonsafaris.co.tz/
- [x] Admin account created (admin/admin)
- [x] Log in to admin dashboard

---

## Phase 2: Configure PHPNuxBill

- [x] Settings → General Settings:
  - Company Name: Buku Tu Internet
  - Timezone: Africa/Dar_es_Salaam
- [ ] Settings → Payment Gateway → Pesapal:
  - Enter Consumer Key and Consumer Secret
  - Set Environment to `sandbox` first
  - Save (IPN auto-registers)
- [x] Settings → Routers:
  - Router added: Buku Tu Hotspot
  - IP: 127.0.0.1:18728 (tunnel to MikroTik via SSH reverse tunnel)
  - Username: admin
  - Password: letmein
  - ✅ API connection tested and working via SSH reverse tunnel
  - ⚠️   Tunnel runs on local dev machine (auto-restart watchdog active)
- [ ] Create packages (1 Hour, Daily, Weekly, Monthly)
- [ ] Test the captive portal loads

---

## Phase 3: MikroTik Hotspot Configuration

- [x] Factory reset performed (clean slate)
- [x] hotspot-bridge created (10.5.50.1/24)
- [x] wlan1 moved from default bridge to hotspot-bridge
- [x] `/ip hotspot` — hotspot1 created and enabled on hotspot-bridge
- [x] `/ip hotspot profile` — default profile (dns-name=test.africanpishonsafaris.co.tz, login-by=http-pap)
- [x] `/ip hotspot walled-garden` — allow portal + pesapal domains
- [x] `/ip pool` — hs-pool (10.5.50.2-10.5.50.254) created
- [x] `/ip dhcp-server` — hs-dhcp on hotspot-bridge with hs-pool (1h lease)
- [x] `/ip firewall nat` — masquerade for 10.5.50.0/24 via WAN
- [x] `/ip firewall filter` — Allow API from WAN (port 8728) + SSH (port 22)
- [x] SSL certificate created (hotspot-ssl), www-ssl enabled
- [x] Static DNS: test.africanpishonsafaris.co.tz → 198.54.114.198
- [x] login.html uploaded — HTTP redirect to PHPNuxBill portal
- [x] Identity set to BukuTu-Router
- [ ] **TEST: Connect to WiFi and verify portal loads**

### MikroTik Credentials

| Item | Value |
|------|-------|
| Router name | BukuTu-Router |
| Router IP (internal/LAN) | 192.168.88.1 |
| Router IP (public) | 102.211.251.118 |
| Hotspot gateway | 10.5.50.1 |
| SSH/Winbox user | admin |
| SSH/Winbox pass | letmein |
| API user | admin |
| API pass | letmein |
| API port | 8728 |
| Hotspot subnet | 10.5.50.0/24 |
| WiFi SSID | MikroTik-A21AC2 (open) |

### ISP Router Port Forwarding

| External Port | Internal IP | Internal Port | Protocol | Purpose |
|---------------|-------------|---------------|----------|---------|
| 8728 | 192.168.100.128 | 8728 | TCP | MikroTik API |
| 22 | 192.168.100.128 | 22 | TCP | MikroTik SSH |

> **Note:** ISP router (Huawei) must forward ports 8728 and 22 → 192.168.100.128 for PHPNuxBill API and remote SSH access.

---

## Phase 4: Pesapal Testing (Sandbox)

- [ ] Configure Pesapal credentials in PHPNuxBill
- [ ] Create a test user
- [ ] Order a package via the portal
- [ ] Pay with Pesapal sandbox credentials
- [ ] Verify payment redirects back
- [ ] Verify user gets activated on MikroTik automatically
- [ ] Check IPN logs in database
- [ ] Debug any issues

---

## Phase 5: Go Live

- [ ] Get Pesapal LIVE Consumer Key and Secret
- [ ] Update Pesapal settings to LIVE environment
- [ ] Test with small real payment
- [ ] Customize portal templates:
  - Add Buku Tu Internet logo
  - Set brand colors
  - Update footer text
- [ ] Enable SSL on shared hosting (Let's Encrypt or hosting SSL)
- [ ] Enable Swahili language if needed
- [ ] Set up email notifications (SMTP settings in PHPNuxBill)
- [ ] Add cron job in cPanel (every minute):
  ```
  php /home/youruser/public_html/system/cron.php
  ```

---

## Phase 6: Monitoring

- [ ] Daily database backups (cPanel backup or manual)
- [ ] Monitor Pesapal transactions via admin dashboard
- [ ] Check MikroTik active users daily
- [ ] Set up Telegram notifications for:
  - Failed payments
  - New user registrations
  - Revenue alerts

---

## Notes

- **PHPNuxBill talks directly to MikroTik API** — no FreeRADIUS needed
- **The captive portal** is PHPNuxBill's built-in login/registration pages
- **Cron job** handles session expiry, payment verification, and cleanup
- **Pesapal IPN** is the callback URL registered automatically
- **Your domain** needs to be accessible from the MikroTik router
- **Shared hosting PHP version** must be 8.2+
- **API via NAT** — Huawei ISP router works; LTE routers may break API auth

---

**Last updated:** 2026-06-30
**Status:** Router fully reconfigured. SSH reverse tunnel established and working. Auto-restart watchdog active. Plans can now be created without hanging.

📖 **Full setup guide:** See [SETUP_GUIDE.md](./SETUP_GUIDE.md) for complete documentation covering every step from scratch.
