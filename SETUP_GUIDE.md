# Buku Tu Internet — Complete Setup Guide

> **Last updated:** 2026-06-30
> **Status:** Production-ready, tunnel active

This document covers EVERYTHING needed to set up the Buku Tu Internet hotspot billing system from scratch, including the SSH tunnel bridge between the shared hosting server and the MikroTik router.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [All Credentials & IPs](#2-all-credentials--ips)
3. [Prerequisites](#3-prerequisites)
4. [Part A: Shared Hosting Setup (cPanel)](#4-part-a-shared-hosting-setup-cpanel)
5. [Part B: MikroTik Router Configuration](#5-part-b-mikrotik-router-configuration)
6. [Part C: ISP Router (Huawei) Port Forwarding](#6-part-c-isp-router-huawei-port-forwarding)
7. [Part D: The SSH Tunnel Bridge (Critical)](#7-part-d-the-ssh-tunnel-bridge-critical)
8. [Part E: PHPNuxBill Configuration](#8-part-e-phpnuxbill-configuration)
9. [Part F: Pesapal Payment Gateway](#9-part-f-pesapal-payment-gateway)
10. [Part G: Captive Portal Files](#10-part-g-captive-portal-files)
11. [Part H: Cron Jobs](#11-part-h-cron-jobs)
12. [Part I: Creating Internet Packages](#12-part-i-creating-internet-packages)
13. [Part J: Testing Everything](#13-part-j-testing-everything)
14. [Troubleshooting](#14-troubleshooting)
15. [Maintenance](#15-maintenance)

---

## 1. Architecture Overview

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐     ┌──────────┐
│  Customer   │────▶│  MikroTik Router  │────▶│  cPanel Host │────▶│  MySQL   │
│  WiFi Phone │     │  (192.168.88.1)   │     │  (Namecheap) │     │  (local) │
└─────────────┘     └────────┬─────────┘     └──────┬──────┘     └──────────┘
                             │                       │
                             │  API on port 8728     │  PHPNuxBill
                             │         ⬇             │  connects to
                             │    ┌──────────┐       │  localhost:18728
                             │    │ Huawei   │       │        ⬆
                             │    │ ISP Router│       │   SSH Reverse
                             │    │ .100.1   │       │   Tunnel
                             │    └────┬─────┘       │        │
                             │         │              │   ┌────┴────────┐
                             │  Port Forward:        │   │ Tunnel Bridge│
                             │  8443→192.168.100.128 │   │ (Dev Machine)│
                             │         │              │   └─────────────┘
                             │    ╔════╧════╗        │
                             │    ║  ISP    ║        │
                             │    ║ BLOCKS  ║        │
                             │    ║ INBOUND ║        │
                             │    ╚════════╝        │
```

### The Problem
The shared hosting server (Namecheap, US-based) **cannot** make direct outbound connections to the MikroTik router in Tanzania because:
1. The ISP blocks inbound connections on all ports except the Huawei-forwarded ones
2. The cPanel server's outbound connections to the MikroTik's public IP are blocked by the ISP

### The Solution
An SSH reverse tunnel runs on a **bridge machine** (the developer's MacBook) that:
- Is on the same local network as the MikroTik (can reach 192.168.88.1)
- Has SSH access to the cPanel server
- Creates a TCP relay chain: `cPanel:localhost:18728 → Bridge:9999 → MikroTik:8728`

---

## 2. All Credentials & IPs

### cPanel Hosting (Namecheap)

| Item | Value |
|------|-------|
| cPanel URL | `https://198.54.114.198:2083` |
| cPanel Username | `afrihwam` |
| cPanel Password | *(stored in SSH key, ask administrator)* |
| cPanel SSH Host | `198.54.114.198` |
| cPanel SSH Port | `21098` |
| cPanel SSH User | `afrihwam` |
| cPanel SSH Key | `~/.ssh/cpanel_afrihwam` |
| Website Domain | `test.africanpishonsafaris.co.tz` |
| Website IP (A record) | `198.54.114.198` |
| PHPNuxBill Path | `/home/afrihwam/test.africanpishonsafaris.co.tz/` |
| PHPNuxBill Admin | `https://test.africanpishonsafaris.co.tz/admin/` |
| PHPNuxBill Admin User | `admin` |
| PHPNuxBill Admin Pass | `admin` *(change immediately)* |

### Database (MySQL on cPanel)

| Item | Value |
|------|-------|
| Host | `localhost` |
| Database Name | `afrihwam_test` |
| Username | `afrihwam_africanpishonsafaris` |
| Password | `a#n#jmFnbiaQ` |
| Port | `3306` |

### MikroTik Router

| Item | Value |
|------|-------|
| Model | RB951Ui-2HnD |
| RouterOS Version | 7.21.4 (long-term) |
| Router Name | `BukuTu-Router` |
| LAN IP (bridge) | `192.168.88.1/24` |
| WAN IP (ether1, from Huawei) | `192.168.100.128/24` |
| Public IP | `102.211.251.118` |
| Hotspot Gateway | `10.5.50.1` |
| SSH/Winbox User | `admin` |
| SSH/Winbox Password | `letmein` |
| API User | `admin` |
| API Password | `letmein` |
| API Port (internal) | `8728` |
| WiFi SSID | `MikroTik-A21AC2` (open, no password) |
| Hotspot Subnet | `10.5.50.0/24` |

### Huawei ISP Router

| Item | Value |
|------|-------|
| Model | Huawei ONT (exact model on sticker) |
| Admin IP | `192.168.100.1` |
| WAN Interface | `1_INTERNET_R_VID_3041` |
| MikroTik Internal IP | `192.168.100.128` |

### Pesapal (Sandbox)

| Item | Value |
|------|-------|
| Consumer Key | `BTzzAXOsrQKoiVl6tvi2uBdAUoQXVHFc` |
| Consumer Secret | `BOH41CazPyS3dMp8WXD2n+fBtL8=` |
| Environment | `sandbox` |
| Currency | `TZS` |
| IPN ID (registered) | `3bc6c89e-8fff-4606-ba29-da363df81ac0` |

### Tunnel Bridge Machine (Developer MacBook)

| Item | Value |
|------|-------|
| Local Relay Port | `9999` (forwards to MikroTik 192.168.88.1:8728) |
| cPanel Tunnel Port | `18728` (PHPNuxBill connects to 127.0.0.1:18728) |
| Watchdog Script | `/tmp/mt_tunnel/watchdog.sh` |
| Relay Log | `/tmp/mt_tunnel/relay.log` |

### PHPNuxBill Router Config (in database)

| Field | Value |
|-------|-------|
| Name | `Buku Tu Hotspot` |
| IP Address | `127.0.0.1:18728` |
| Username | `admin` |
| Password | `letmein` |

---

## 3. Prerequisites

You will need:
- **SSH access** to the cPanel server (port 21098, key-based)
- **SSH access** to the MikroTik router (192.168.88.1, password: letmein)
- **A bridge machine** that is on the same LAN as the MikroTik AND can SSH to cPanel
- **Python 3** installed on the bridge machine
- **Homebrew** (macOS) for sshpass: `brew install sshpass`
- **cPanel login** for database and file management

---

## 4. Part A: Shared Hosting Setup (cPanel)

### 4.1 Create Subdomain
1. Log into cPanel at `https://198.54.114.198:2083`
2. Go to **Domains → Subdomains**
3. Create: `test.africanpishonsafaris.co.tz`
4. Document root: `/home/afrihwam/test.africanpishonsafaris.co.tz`

### 4.2 Create MySQL Database
1. cPanel → **Databases → MySQL Databases**
2. Create database: `afrihwam_test`
3. Create user: `afrihwam_africanpishonsafaris` with password `a#n#jmFnbiaQ`
4. Grant ALL privileges to the user on the database

### 4.3 Upload PHPNuxBill Files
1. Copy ALL contents from the `src/` directory
2. Upload to `/home/afrihwam/test.africanpishonsafaris.co.tz/` via:
   - cPanel File Manager (upload zip, extract)
   - Or SFTP to `198.54.114.198:21098`

```bash
# Via SCP (if you have SSH key):
scp -P 21098 -r src/* afrihwam@198.54.114.198:/home/afrihwam/test.africanpishonsafaris.co.tz/
```

### 4.4 Configure config.php
Edit `/home/afrihwam/test.africanpishonsafaris.co.tz/config.php`:

```php
<?php
$protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || $_SERVER["SERVER_PORT"] == 443) ? "https://" : "http://";
$host = $_SERVER["HTTP_HOST"];
$baseDir = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
define("APP_URL", $protocol . $host . $baseDir);

$_app_stage = "Live";

$db_host = "localhost";
$db_user = "afrihwam_africanpishonsafaris";
$db_pass = "a#n#jmFnbiaQ";
$db_name = "afrihwam_test";

if($_app_stage!="Live"){
    error_reporting(E_ERROR);
    ini_set("display_errors", 1);
    ini_set("display_startup_errors", 1);
}else{
    error_reporting(E_ERROR);
    ini_set("display_errors", 0);
    ini_set("display_startup_errors", 0);
}
```

### 4.5 Run Installation
1. Open `https://test.africanpishonsafaris.co.tz/` in a browser
2. The installer should detect the database settings
3. Follow the 5-step wizard:
   - Step 1: License agreement
   - Step 2: Database check (auto-detected)
   - Step 3: Create admin account (admin / admin)
   - Step 4: Basic settings
   - Step 5: Finish
4. After installation, log into `https://test.africanpishonsafaris.co.tz/admin/`

### 4.6 SSH Key Setup for Tunnel
The bridge machine needs passwordless SSH to cPanel. Add the bridge machine's SSH public key to cPanel's `authorized_keys`:

```bash
# From bridge machine:
cat ~/.ssh/id_ed25519.pub | ssh -p 21098 afrihwam@198.54.114.198 "cat >> ~/.ssh/authorized_keys"
```

Or via cPanel: **Security → SSH Access → Manage SSH Keys → Import Key**

---

## 5. Part B: MikroTik Router Configuration

### 5.1 Connect to MikroTik
```bash
# SSH (from local network):
ssh admin@192.168.88.1
# Password: letmein

# Or use Winbox: connect to 192.168.88.1
```

### 5.2 Reset to Factory (if needed)
```
/system reset-configuration no-defaults=yes skip-backup=yes
```
Wait 2 minutes, then reconnect. The default IP will be 192.168.88.1 with admin/no password.

### 5.3 Set Admin Password
```
/user set admin password=letmein
```

### 5.4 Set Router Identity
```
/system identity set name=BukuTu-Router
```

### 5.5 Verify WAN Connection
The MikroTik receives its WAN IP from the Huawei ISP router via DHCP on ether1:
```
/ip dhcp-client print
```
Should show ether1 with IP 192.168.100.128/24.

### 5.6 Create Hotspot Bridge
```
/interface bridge add name=hotspot-bridge
/ip address add address=10.5.50.1/24 interface=hotspot-bridge
```

### 5.7 Move WiFi Interface to Hotspot Bridge
```
# First, remove wlan1 from default bridge
/interface bridge port remove [find interface=wlan1]
# Add wlan1 to hotspot bridge
/interface bridge port add bridge=hotspot-bridge interface=wlan1
```

### 5.8 Create IP Pool for Hotspot Users
```
/ip pool add name=hs-pool ranges=10.5.50.2-10.5.50.254
```

### 5.9 Set Up DHCP Server for Hotspot
```
/ip dhcp-server add name=hs-dhcp interface=hotspot-bridge address-pool=hs-pool lease-time=1h disabled=no
/ip dhcp-server network add address=10.5.50.0/24 gateway=10.5.50.1 dns-server=8.8.8.8,8.8.4.4
```

### 5.10 Create Hotspot
```
/ip hotspot profile set [find name=default] dns-name=test.africanpishonsafaris.co.tz login-by=http-pap
/ip hotspot add name=hotspot1 interface=hotspot-bridge address-pool=hs-pool profile=default disabled=no
```

### 5.11 Configure Walled Garden
Allow your portal and Pesapal domains without authentication:
```
/ip hotspot walled-garden add dst-host=test.africanpishonsafaris.co.tz
/ip hotspot walled-garden add dst-host=*.pesapal.com
/ip hotspot walled-garden add dst-host=*.africanpishonsafaris.co.tz
```

### 5.12 Firewall - Allow API and SSH from WAN
```
# Allow established connections
/ip firewall filter add chain=input action=accept connection-state=established,related,untracked comment="defconf: accept established,related,untracked"

# Drop invalid
/ip firewall filter add chain=input action=drop connection-state=invalid comment="defconf: drop invalid"

# Allow ICMP (ping)
/ip firewall filter add chain=input action=accept protocol=icmp comment="defconf: accept ICMP"

# Allow SSH from WAN
/ip firewall filter add chain=input action=accept protocol=tcp in-interface=ether1 dst-port=22 comment="Allow SSH from WAN"

# Allow API from WAN
/ip firewall filter add chain=input action=accept protocol=tcp in-interface=ether1 dst-port=8728 comment="Allow API from WAN"

# Drop all other WAN input
/ip firewall filter add chain=input action=drop in-interface-list=!LAN comment="defconf: drop all not coming from LAN"

# Allow hotspot forward traffic
/ip firewall filter add chain=forward action=accept connection-state=established,related,untracked comment="defconf: accept established,related,untracked"
/ip firewall filter add chain=forward action=drop connection-state=invalid comment="defconf: drop invalid"
/ip firewall filter add chain=forward action=drop connection-nat-state=!dstnat in-interface-list=WAN comment="defconf: drop all from WAN not DSTNATed"
```

### 5.13 NAT (Masquerade) for Internet Access
```
# General masquerade
/ip firewall nat add chain=srcnat action=masquerade out-interface-list=WAN ipsec-policy=out,none comment="defconf: masquerade"

# Hotspot masquerade
/ip firewall nat add chain=srcnat action=masquerade src-address=10.5.50.0/24 out-interface=ether1 comment="Hotspot NAT"
```

### 5.14 Enable API Service
```
/ip service enable api
/ip service set api port=8728 disabled=no
```
Verify: `/ip service print` — API should show port 8728, enabled.

### 5.15 Set Static DNS (Optional)
```
/ip dns set servers=8.8.8.8,8.8.4.4
```

### 5.16 Upload Captive Portal Files
Upload the custom `login.html` from the `hotspot/` directory to the MikroTik:
```bash
# Using FTP:
curl -T hotspot/login.html ftp://admin:letmein@192.168.88.1/hotspot/login.html
```

---

## 6. Part C: ISP Router (Huawei) Port Forwarding

The MikroTik is behind a Huawei ONT/router from the ISP. The Huawei router's admin panel is at `192.168.100.1`.

### 6.1 Log into Huawei Router
1. Connect to the MikroTik WiFi or LAN
2. Open `http://192.168.100.1`
3. Default credentials are usually on the back sticker

### 6.2 Configure Port Forwarding
Go to **Advanced → NAT → Port Mapping** (or **Forward Rules → Port Mapping Configuration**):

Create a new rule:
| Field | Value |
|-------|-------|
| Type | User-defined |
| Enable Port Mapping | ✅ Checked |
| Mapping Name | `MikroTik API` |
| WAN Name | `1_INTERNET_R_VID_3041` |
| Internal Host | `192.168.100.128` |
| External Source IP Address | *(leave blank = any)* |
| Protocol | `TCP` |
| Internal port number | `8728` |
| External port number | `8443` |
| External source port number | *(leave blank)* |

> **Note:** External port 8443 was chosen because port 443 was blocked by the ISP. If 8443 also doesn't work, try 8080, 8443, or other high ports.

### 6.3 Reboot Huawei Router
After saving the port forwarding rule, **reboot the Huawei router** (unplug power for 30 seconds). The port forwarding only takes effect after reboot.

### 6.4 Verify Port Forwarding
From a machine OUTSIDE your local network (use phone on cellular data, NOT WiFi):
```
Open: http://102.211.251.118:8443
```
You should see the MikroTik API greeting (garbled text).

---

## 7. Part D: The SSH Tunnel Bridge (Critical)

**This is the most important part.** The shared hosting server cannot directly reach the MikroTik. The tunnel bridge solves this.

### 7.1 How It Works
```
cPanel PHPNuxBill → 127.0.0.1:18728 → SSH Reverse Tunnel → Bridge:9999 → Python Relay → MikroTik 192.168.88.1:8728
```

Two components run on the bridge machine:
1. **Python TCP Relay**: Listens on `127.0.0.1:9999`, forwards to `192.168.88.1:8728`
2. **SSH Reverse Tunnel**: Maps `cPanel:18728` → `bridge:9999`

### 7.2 Bridge Machine Requirements
- On the same LAN as the MikroTik (can reach 192.168.88.1)
- Python 3 installed
- SSH key-based access to cPanel (see 4.6)
- macOS or Linux

### 7.3 SSH Config on Bridge Machine
Add to `~/.ssh/config`:
```
Host cpanel-afrihwam
    HostName 198.54.114.198
    User afrihwam
    Port 21098
    IdentityFile ~/.ssh/cpanel_afrihwam
    StrictHostKeyChecking accept-new
    ServerAliveInterval 60
    ServerAliveCountMax 10
    TCPKeepAlive yes
```

### 7.4 The Private SSH Key for cPanel
The private key file `~/.ssh/cpanel_afrihwam` is required. If you don't have it, generate a new key pair and add the public key to cPanel:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/cpanel_afrihwam -N ""
cat ~/.ssh/cpanel_afrihwam.pub | ssh -p 21098 afrihwam@198.54.114.198 "cat >> ~/.ssh/authorized_keys"
```

Or import via cPanel: **Security → SSH Access → Manage SSH Keys → Import Key**.

### 7.5 Install sshpass (for MikroTik SSH)
```bash
# macOS:
brew install sshpass
# Linux:
sudo apt install sshpass   # Debian/Ubuntu
sudo yum install sshpass   # RHEL/CentOS
```

### 7.6 Create the Watchdog Script
Save to `/opt/bukutu-tunnel/watchdog.sh` (or any location):

```bash
#!/bin/bash
# ==================================================
# Buku Tu Internet — Tunnel Watchdog
# Keeps the cPanel ↔ MikroTik connection alive
# ==================================================

LOG_FILE="/opt/bukutu-tunnel/watchdog.log"
RELAY_LOG="/opt/bukutu-tunnel/relay.log"
MIKROTIK_IP="192.168.88.1"
MIKROTIK_PORT="8728"
RELAY_PORT="9999"
CPANEL_PORT="18728"
CHECK_INTERVAL="15"

# Ensure log directory exists
mkdir -p "$(dirname "$LOG_FILE")"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S'): $1" >> "$LOG_FILE"; }

# Cleanup on exit
cleanup() { 
    log "Watchdog stopping"
    # Kill any child processes
    jobs -p | xargs kill 2>/dev/null
    exit 0
}
trap cleanup EXIT INT TERM

# ----- Python TCP Relay -----
start_relay() {
    log "Starting Python relay on :$RELAY_PORT -> $MIKROTIK_IP:$MIKROTIK_PORT"
    python3 -c "
import socket, threading, select, sys, os

def relay(a, b):
    '''Bidirectional TCP relay between two sockets'''
    try:
        while True:
            r, _, _ = select.select([a, b], [], [], 30)
            if a in r:
                d = a.recv(4096)
                if not d: break
                b.sendall(d)
            if b in r:
                d = b.recv(4096)
                if not d: break
                a.sendall(d)
    except: pass

def handle(client_sock, client_addr):
    remote_sock = None
    try:
        remote_sock = socket.create_connection(
            ('$MIKROTIK_IP', $MIKROTIK_PORT), 5
        )
        relay(client_sock, remote_sock)
    except Exception as e:
        pass
    finally:
        try: client_sock.close()
        except: pass
        try: remote_sock.close()
        except: pass

srv = socket.socket()
srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
try:
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEPORT, 1)
except: pass
srv.bind(('127.0.0.1', $RELAY_PORT))
srv.listen(10)
sys.stdout.write('RELAY_READY:$RELAY_PORT\n')
sys.stdout.flush()

while True:
    client, addr = srv.accept()
    threading.Thread(target=handle, args=(client, addr), daemon=True).start()
" >> "$RELAY_LOG" 2>&1 &
}

# ----- SSH Reverse Tunnel -----
start_ssh_tunnel() {
    log "Starting SSH reverse tunnel: cPanel:$CPANEL_PORT -> localhost:$RELAY_PORT"
    ssh -o ConnectTimeout=10 \
        -o StrictHostKeyChecking=no \
        -o TCPKeepAlive=yes \
        -o ServerAliveInterval=30 \
        -o ServerAliveCountMax=3 \
        -o ExitOnForwardFailure=no \
        -f -N -R ${CPANEL_PORT}:127.0.0.1:${RELAY_PORT} \
        cpanel-afrihwam 2>/dev/null
}

# ----- Health Checks -----
check_relay() {
    lsof -ti:${RELAY_PORT} > /dev/null 2>&1
}

check_tunnel() {
    # Check if cPanel port is reachable
    ssh -o ConnectTimeout=5 -o StrictHostKeyChecking=no cpanel-afrihwam \
        "php -r '\$fp=@fsockopen(\"127.0.0.1\",${CPANEL_PORT},\$e,\$s,2);exit(\$fp?0:1);'" \
        2>/dev/null
}

check_full_chain() {
    # Test PHPNuxBill can actually talk to MikroTik through the tunnel
    ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no cpanel-afrihwam \
        "php -r '
        require_once \"/home/afrihwam/test.africanpishonsafaris.co.tz/init.php\";
        use PEAR2\\\\Net\\\\RouterOS\\\\Client;
        use PEAR2\\\\Net\\\\RouterOS\\\\Request;
        try {
            \$c = new Client(\"127.0.0.1\", \"admin\", \"letmein\", ${CPANEL_PORT});
            \$r = \$c->sendSync(new Request(\"/system/identity/print\"));
            exit(0);
        } catch(Exception \$e) {
            exit(1);
        }'" 2>/dev/null
}

# ----- Main Loop -----
log "=== Buku Tu Tunnel Watchdog Started ==="
log "Relay: localhost:$RELAY_PORT -> $MIKROTIK_IP:$MIKROTIK_PORT"
log "Tunnel: cPanel:localhost:$CPANEL_PORT -> bridge:$RELAY_PORT"

while true; do
    # Check and restart relay
    if ! check_relay; then
        log "WARNING: Relay dead, restarting..."
        start_relay
    fi

    # Check and restart SSH tunnel
    if ! check_tunnel; then
        log "WARNING: SSH tunnel dead, restarting..."
        start_ssh_tunnel
    fi

    # Periodic full chain test (every 5 minutes = 20 intervals)
    if [ $(( $(date +%s) % 300 )) -lt $CHECK_INTERVAL ]; then
        if check_full_chain; then
            log "HEALTH: Full chain OK"
        else
            log "ERROR: Full chain FAILED"
        fi
    fi

    sleep $CHECK_INTERVAL
done
```

### 7.7 Make It Run on Boot (macOS)
Create a LaunchAgent plist at `~/Library/LaunchAgents/com.bukutu.tunnel.plist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
 "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.bukutu.tunnel</string>
    <key>ProgramArguments</key>
    <array>
        <string>/bin/bash</string>
        <string>/opt/bukutu-tunnel/watchdog.sh</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardOutPath</key>
    <string>/opt/bukutu-tunnel/watchdog.log</string>
    <key>StandardErrorPath</key>
    <string>/opt/bukutu-tunnel/watchdog.log</string>
</dict>
</plist>
```

Load it:
```bash
launchctl load ~/Library/LaunchAgents/com.bukutu.tunnel.plist
```

### 7.8 Make It Run on Boot (Linux/systemd)
Create `/etc/systemd/system/bukutu-tunnel.service`:

```ini
[Unit]
Description=Buku Tu Internet Tunnel Watchdog
After=network.target

[Service]
Type=simple
ExecStart=/bin/bash /opt/bukutu-tunnel/watchdog.sh
Restart=always
RestartSec=10
User=youruser

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable bukutu-tunnel
sudo systemctl start bukutu-tunnel
```

### 7.9 Verify Tunnel is Working
```bash
# From bridge machine:
lsof -i:9999          # Should show Python relay
ps aux | grep "ssh.*-R 18728"  # Should show SSH tunnel

# From any machine:
ssh -p 21098 afrihwam@198.54.114.198 "php -r '
\$fp=@fsockopen(\"127.0.0.1\",18728,\$e,\$s,3);
echo \$fp ? \"TUNNEL OK\\n\" : \"TUNNEL FAIL\\n\";
'"
```

---

## 8. Part E: PHPNuxBill Configuration

### 8.1 Log into Admin
1. Go to `https://test.africanpishonsafaris.co.tz/admin/`
2. Username: `admin`, Password: `admin`

### 8.2 General Settings
**Settings → General Settings** (or **Settings → App**):
- Company Name: `Buku Tu Internet`
- Timezone: `Africa/Dar_es_Salaam`
- Currency Code: `TZS`
- Date Format: `d M Y`
- Save

### 8.3 Configure Router
**Settings → Routers → Add Router** (or via database):

If adding via admin UI:
- Name: `Buku Tu Hotspot`
- IP Address: `127.0.0.1` (not the MikroTik IP! This goes through the tunnel)
- API Port: `18728`
- Username: `admin`
- Password: `letmein`
- Description: `Main MikroTik Router`

If adding via database (already configured):
```sql
INSERT INTO tbl_routers (name, ip_address, username, password, description, enabled)
VALUES ('Buku Tu Hotspot', '127.0.0.1:18728', 'admin', 'letmein', 'Main MikroTik Router', 1);
```

### 8.4 Test Router Connection
In the admin panel, go to **Settings → Routers** and click the **Test** button next to "Buku Tu Hotspot". It should show "Online" if the tunnel is working.

To verify via CLI:
```bash
ssh -p 21098 afrihwam@198.54.114.198 "php -r '
require_once \"/home/afrihwam/test.africanpishonsafaris.co.tz/init.php\";
try {
    \$client = (new MikrotikHotspot())->getClient(\"127.0.0.1:18728\", \"admin\", \"letmein\");
    echo \"ROUTER OK\n\";
} catch(Exception \$e) {
    echo \"FAILED: \" . \$e->getMessage() . \"\n\";
}
'"
```

---

## 9. Part F: Pesapal Payment Gateway

### 9.1 Plugin Files
The Pesapal plugin files are already deployed to:
- `/home/afrihwam/test.africanpishonsafaris.co.tz/system/paymentgateway/pesapal.php`
- `/home/afrihwam/test.africanpishonsafaris.co.tz/system/paymentgateway/ui/pesapal.tpl`
- `/home/afrihwam/test.africanpishonsafaris.co.tz/system/paymentgateway/pesapal_currency.json`
- `/home/afrihwam/test.africanpishonsafaris.co.tz/system/paymentgateway/pesapal_methods.json`
- `/home/afrihwam/test.africanpishonsafaris.co.tz/callback/pesapal.php`
- `/home/afrihwam/test.africanpishonsafaris.co.tz/callback/pesapal_ipn.php`

### 9.2 IPN Log Table
Run this SQL to create the IPN logging table:
```sql
CREATE TABLE IF NOT EXISTS tbl_pesapal_ipn_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_tracking_id VARCHAR(255),
    merchant_reference VARCHAR(255),
    status VARCHAR(50),
    payload TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 9.3 Configure in Admin Panel
1. **Settings → Payment Gateway → Pesapal**
2. Enter Consumer Key: `BTzzAXOsrQKoiVl6tvi2uBdAUoQXVHFc`
3. Enter Consumer Secret: `BOH41CazPyS3dMp8WXD2n+fBtL8=`
4. Environment: `Sandbox` (for testing) or `Live` (for production)
5. Currency: `TZS`
6. Country: `Tanzania`
7. Save
8. IPN URL is auto-registered on save

### 9.4 Pesapal Sandbox vs Live URLs
| Environment | Base URL |
|-------------|----------|
| Sandbox | `https://cybqa.pesapal.com/pesapalv3/` |
| Live | `https://pay.pesapal.com/v3/` |

### 9.5 Test Pesapal Connection
After saving, try creating a test order. Use these sandbox credentials:
- Phone: `2547XXXXXXXX` (any valid format)
- PIN: `1234`

---

## 10. Part G: Captive Portal Files

The captive portal files live in the `hotspot/` directory of this project and are uploaded to the MikroTik's hotspot directory.

### 10.1 Upload Portal Files
```bash
# Upload to MikroTik via FTP
curl -T hotspot/login.html ftp://admin:letmein@192.168.88.1/hotspot/login.html
curl -T hotspot/alogin.html ftp://admin:letmein@192.168.88.1/hotspot/alogin.html
curl -T hotspot/logout.html ftp://admin:letmein@192.168.88.1/hotspot/logout.html
curl -T hotspot/status.html ftp://admin:letmein@192.168.88.1/hotspot/status.html
curl -T hotspot/errors.txt ftp://admin:letmein@192.168.88.1/hotspot/errors.txt
```

### 10.2 login.html Purpose
The `login.html` is a redirect page. When a user connects to WiFi, the MikroTik hotspot captures them and serves this page, which immediately redirects them to the PHPNuxBill portal:
```html
<meta http-equiv="refresh" content="0;url=https://test.africanpishonsafaris.co.tz/?_route=login">
```

---

## 11. Part H: Cron Jobs

### 11.1 cPanel Cron Job
In cPanel → **Cron Jobs**, add:

```
* * * * * /usr/local/bin/php /home/afrihwam/test.africanpishonsafaris.co.tz/system/cron.php > /dev/null 2>&1
```

This runs every minute and handles:
- Session expiry
- Payment verification
- User cleanup
- MikroTik sync

### 11.2 Tunnel Watchdog
The tunnel watchdog runs on the bridge machine (see Section 7) and auto-restarts.

---

## 12. Part I: Creating Internet Packages

### 12.1 Bandwidth Profiles
**Services → Bandwidth → Add Bandwidth**

Create these profiles:

| Name | Download Rate | Download Unit | Upload Rate | Upload Unit |
|------|--------------|---------------|-------------|-------------|
| 1 Mbps | 1 | Mbps | 512 | Kbps |
| 2 Mbps | 2 | Mbps | 1 | Mbps |
| 5 Mbps | 5 | Mbps | 2 | Mbps |
| 10 Mbps | 10 | Mbps | 5 | Mbps |

### 12.2 Internet Plans
**Services → Plans → Add Plan**

Create plans for each speed tier. Examples (prices can be adjusted):

| Plan Name | Bandwidth | Price (TZS) | Type | Validity | Validity Unit |
|-----------|-----------|-------------|------|----------|---------------|
| 1 Mbps - Daily | 1 Mbps | 1,000 | Hotspot | 1 | Days |
| 1 Mbps - Weekly | 1 Mbps | 5,000 | Hotspot | 7 | Days |
| 1 Mbps - Monthly | 1 Mbps | 15,000 | Hotspot | 1 | Months |
| 2 Mbps - Daily | 2 Mbps | 1,500 | Hotspot | 1 | Days |
| 2 Mbps - Weekly | 2 Mbps | 7,000 | Hotspot | 7 | Days |
| 2 Mbps - Monthly | 2 Mbps | 20,000 | Hotspot | 1 | Months |
| 5 Mbps - Daily | 5 Mbps | 2,500 | Hotspot | 1 | Days |
| 5 Mbps - Weekly | 5 Mbps | 12,000 | Hotspot | 7 | Days |
| 5 Mbps - Monthly | 5 Mbps | 35,000 | Hotspot | 1 | Months |
| 10 Mbps - Daily | 10 Mbps | 4,000 | Hotspot | 1 | Days |
| 10 Mbps - Weekly | 10 Mbps | 20,000 | Hotspot | 7 | Days |
| 10 Mbps - Monthly | 10 Mbps | 55,000 | Hotspot | 1 | Months |

**For each plan, set:**
- Type: `Hotspot`
- TypeBP: `Unlimited`
- Limit Type: `Time_Limit`
- Router: `Buku Tu Hotspot`
- Device: `MikrotikHotspot`
- Enabled: `Yes`
- Prepaid: `Yes`

### 12.3 Via Database (Bulk Insert)
To create plans in bulk, run this SQL on the `afrihwam_test` database:
```sql
-- Bandwidth profiles
INSERT INTO tbl_bandwidth (name_bw, rate_down, rate_down_unit, rate_up, rate_up_unit) VALUES
('1 Mbps', 1, 'Mbps', 512, 'Kbps'),
('2 Mbps', 2, 'Mbps', 1, 'Mbps'),
('5 Mbps', 5, 'Mbps', 2, 'Mbps'),
('10 Mbps', 10, 'Mbps', 5, 'Mbps');

-- Get the IDs (1,2,3,4) and create plans
INSERT INTO tbl_plans (name_plan, id_bw, price, type, typebp, limit_type, time_limit, time_unit, validity, validity_unit, routers, device, enabled, prepaid) VALUES
('1 Mbps - Daily', 1, '1000', 'Hotspot', 'Unlimited', 'Time_Limit', 24, 'Hrs', 1, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('1 Mbps - Weekly', 1, '5000', 'Hotspot', 'Unlimited', 'Time_Limit', 168, 'Hrs', 7, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('1 Mbps - Monthly', 1, '15000', 'Hotspot', 'Unlimited', 'Time_Limit', 720, 'Hrs', 1, 'Months', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('2 Mbps - Daily', 2, '1500', 'Hotspot', 'Unlimited', 'Time_Limit', 24, 'Hrs', 1, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('2 Mbps - Weekly', 2, '7000', 'Hotspot', 'Unlimited', 'Time_Limit', 168, 'Hrs', 7, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('2 Mbps - Monthly', 2, '20000', 'Hotspot', 'Unlimited', 'Time_Limit', 720, 'Hrs', 1, 'Months', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('5 Mbps - Daily', 3, '2500', 'Hotspot', 'Unlimited', 'Time_Limit', 24, 'Hrs', 1, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('5 Mbps - Weekly', 3, '12000', 'Hotspot', 'Unlimited', 'Time_Limit', 168, 'Hrs', 7, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('5 Mbps - Monthly', 3, '35000', 'Hotspot', 'Unlimited', 'Time_Limit', 720, 'Hrs', 1, 'Months', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('10 Mbps - Daily', 4, '4000', 'Hotspot', 'Unlimited', 'Time_Limit', 24, 'Hrs', 1, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('10 Mbps - Weekly', 4, '20000', 'Hotspot', 'Unlimited', 'Time_Limit', 168, 'Hrs', 7, 'Days', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes'),
('10 Mbps - Monthly', 4, '55000', 'Hotspot', 'Unlimited', 'Time_Limit', 720, 'Hrs', 1, 'Months', 'Buku Tu Hotspot', 'MikrotikHotspot', 1, 'yes');
```

---

## 13. Part J: Testing Everything

### 13.1 Tunnel Health
```bash
# From bridge machine:
# Check relay
lsof -i:9999

# Check SSH tunnel
ps aux | grep "ssh.*-R 18728"

# Full chain test
ssh cpanel-afrihwam "php -r '
require_once \"/home/afrihwam/test.africanpishonsafaris.co.tz/init.php\";
use PEAR2\\Net\\RouterOS\\Client;
use PEAR2\\Net\\RouterOS\\Request;
\$c = new Client(\"127.0.0.1\", \"admin\", \"letmein\", 18728);
\$r = \$c->sendSync(new Request(\"/system/identity/print\"));
echo \"NAME: \" . \$r->getProperty(\"name\") . \"\\n\";
echo \"ROUTER OK\\n\";
'"
# Expected: NAME: BukuTu-Router, ROUTER OK
```

### 13.2 WiFi Captive Portal Test
1. Connect to WiFi SSID `MikroTik-A21AC2`
2. Open any website — you should be redirected
3. You should see the PHPNuxBill login page
4. If you see the MikroTik hotspot login page instead, check:
   - Hotspot DNS name is set correctly
   - Walled garden includes your domain
   - login.html is uploaded

### 13.3 Payment Test
1. Register a test user on the portal
2. Select a package
3. Choose Pesapal as the payment method
4. You should be redirected to Pesapal sandbox
5. Complete payment with test credentials
6. After payment, you should be redirected back
7. Check that the user is activated (can browse internet)

### 13.4 Plan Creation Test
1. Log into admin panel
2. **Services → Plans → Add** (or **Hotspot → Plans**)
3. Fill in plan details and save
4. It should save without hanging (tunnel must be active)

---

## 14. Troubleshooting

### "Plan creation just loads/hangs"
**Cause:** PHPNuxBill cannot reach the MikroTik API
**Fix:** Verify the tunnel is running (Section 13.1)

### "Error connecting to RouterOS"
**Cause:** Tunnel is down, wrong port, or wrong credentials
**Fix:**
1. Check tunnel health from bridge machine
2. Verify router IP in database: `SELECT * FROM tbl_routers;` (should be `127.0.0.1:18728`)
3. Verify password: `letmein`

### "Connection refused" from cPanel
**Cause:** SSH reverse tunnel is up but Python relay is dead
**Fix:** Restart the watchdog on the bridge machine

### "Connection timed out" from cPanel
**Cause:** SSH reverse tunnel is completely down
**Fix:** Restart the watchdog on the bridge machine

### "Portal not loading on WiFi"
**Cause:** Hotspot DNS or walled garden not configured
**Fix:**
- Check `/ip hotspot profile print` — dns-name should be set
- Check `/ip hotspot walled-garden print` — domain should be listed
- Restart hotspot: `/ip hotspot disable hotspot1; /ip hotspot enable hotspot1`

### "Users not getting internet after payment"
**Cause:** MikroTik API user creation failed
**Fix:**
1. Verify tunnel is working
2. Check PHP error log on cPanel
3. Check MikroTik logs: `/log print where topics~hotspot`

### General Debugging
```bash
# Check tunnel chain step by step:
# 1. Can bridge reach MikroTik API?
php -r '$fp=fsockopen("192.168.88.1",8728,$e,$s,3);echo $fp?"OK\n":"FAIL\n";'

# 2. Is Python relay running?
lsof -i:9999

# 3. Is SSH tunnel to cPanel alive?
ps aux | grep "ssh.*-R 18728"

# 4. Is cPanel port reachable?
ssh cpanel-afrihwam "php -r '\$fp=@fsockopen(\"127.0.0.1\",18728,\$e,\$s,3);echo \$fp?\"OK\\n\":\"FAIL\\n\";'"

# 5. Full API test:
ssh cpanel-afrihwam "php /tmp/router_test.php"
```

---

## 15. Maintenance

### Daily
- Check tunnel watchdog is running
- Verify router status in PHPNuxBill admin shows "Online"
- Review Pesapal transactions

### Weekly
- Check watchdog logs: `tail -100 /opt/bukutu-tunnel/watchdog.log`
- Check relay logs: `tail -100 /opt/bukutu-tunnel/relay.log`
- Database backup (cPanel → Backup)

### Monthly
- Update PHPNuxBill if new version available
- Check MikroTik RouterOS for updates
- Review and rotate Pesapal credentials if needed

### When Changing the Bridge Machine
1. Set up SSH config on the new machine (Section 7.3)
2. Copy the private key `~/.ssh/cpanel_afrihwam`
3. Install Python 3 and sshpass
4. Create and start the watchdog (Section 7.6-7.8)
5. Verify: `ssh cpanel-afrihwam "php /tmp/router_test.php"`

### When Changing cPanel or Domain
1. Update `config.php` with new database credentials
2. Update `APP_URL` in config.php
3. Update MikroTik hotspot DNS name: `/ip hotspot profile set dns-name=newdomain.com`
4. Update walled garden entries
5. Update Pesapal callback URLs if needed

---

## Quick Reference: All Commands

### Start the Tunnel (from bridge machine)
```bash
/opt/bukutu-tunnel/watchdog.sh &
```

### Restart Tunnel
```bash
kill $(pgrep -f watchdog.sh) && /opt/bukutu-tunnel/watchdog.sh &
```

### Check Tunnel Status
```bash
lsof -i:9999 && echo "Relay OK" || echo "Relay DOWN"
ssh -p 21098 afrihwam@198.54.114.198 "php -r '\$fp=@fsockopen(\"127.0.0.1\",18728,\$e,\$s,2);echo \$fp?\"Tunnel OK\\n\":\"Tunnel DOWN\\n\";'"
```

### Test Router Connection
```bash
ssh -p 21098 afrihwam@198.54.114.198 "php -r '
require_once \"/home/afrihwam/test.africanpishonsafaris.co.tz/init.php\";
use PEAR2\\Net\\RouterOS\\Client;
use PEAR2\\Net\\RouterOS\\Request;
\$c = new Client(\"127.0.0.1\", \"admin\", \"letmein\", 18728);
\$r = \$c->sendSync(new Request(\"/system/identity/print\"));
echo \"Router: \" . \$r->getProperty(\"name\") . \"\\n\";
'"
```

### SSH to cPanel
```bash
ssh -p 21098 afrihwam@198.54.114.198
```

### SSH to MikroTik (from local network)
```bash
ssh admin@192.168.88.1
# Password: letmein
```

---

**End of Setup Guide**
