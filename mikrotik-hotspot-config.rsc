# MikroTik Hotspot Configuration for Buku Tu Internet
# Apply these commands via Winbox or terminal on your MikroTik

# 1. Create IP Pool for hotspot users
/ip pool add name="hotspot-pool" ranges=192.168.88.10-192.168.88.200

# 2. Create DHCP Profile
/ip hotspot profile add name="hotspot-profile" address-pool="hotspot-pool" login-by=mac,http-chap http-cookie-lifetime=1d html-directory=hotspot

# 3. Create Hotspot Server
/ip hotspot add name="hotspot1" interface=ether1 local-address=192.168.88.1 address-pool="hotspot-pool" profile="hotspot-profile" masquerade=yes

# 4. Add DNS name (optional - allows hotspot.africanpishonsafaris.co.tz)
/ip dns set servers=8.8.8.8,8.8.4.4

# 5. Create Walled Garden for Pesapal payment gateway
/ip hotspot walled-garden add dst-host=cybqa.pesapal.com action=allow
/ip hotspot walled-garden add dst-host=pay.pesapal.com action=allow
/ip hotspot walled-garden add dst-host=test.africanpishonsafaris.co.tz action=allow

# 6. Redirect all HTTP traffic to PHPNuxBill portal
/ip hotspot walled-garden add dst-address=!192.168.88.0/24 protocol=http action=return-dummy

# 7. Set up User Manager (optional - for user management)
/tool user-manager customer add name="admin" password="admin" parent=payment
/tool user-manager customer add name="reseller1" password="pass" parent=admin

# 8. Add hotspot user for testing (remove later)
/ip hotspot user add name=test password=test123 limit-bytes-total=50M

# ALTERNATIVE: Simple redirect using DNS name
# If you prefer using a DNS name for the portal:
/ip hotspot walled-garden add dst-host=test.africanpishonsafaris.co.tz action=allow
/ip hotspot walled-garden add dst-host=!test.africanpishonsafaris.co.tz action=return-dummy

# 9. Set hotspot login page redirect
/ip hotspot profile set hotspot-profile html-directory=hotspot login-page="http://test.africanpishonsafaris.co.tz/"
