<?php
// Quick health check - delete after testing
$checks = [
    'PHP Version' => PHP_VERSION,
    'Server Time' => date('Y-m-d H:i:s'),
    'PDO MySQL' => extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No',
    'mbstring' => extension_loaded('mbstring') ? '✅ Yes' : '❌ No',
    'OpenSSL' => extension_loaded('openssl') ? '✅ Yes' : '❌ No',
    'Fileinfo' => extension_loaded('fileinfo') ? '✅ Yes' : '❌ No',
    'JSON' => extension_loaded('json') ? '✅ Yes' : '❌ No',
    'BCMath' => extension_loaded('bcmath') ? '✅ Yes' : '❌ No',
    'XML' => extension_loaded('xml') ? '✅ Yes' : '❌ No',
    'ZIP' => extension_loaded('zip') ? '✅ Yes' : '❌ No',
];
?>
<!DOCTYPE html>
<html>
<head><title>Health Check</title>
<style>
body{font-family:sans-serif;max-width:500px;margin:50px auto;padding:20px}
h1{color:#2563eb}
table{width:100%;border-collapse:collapse;margin-top:20px}
td{padding:10px 12px;border-bottom:1px solid #eee}
td:last-child{text-align:right;font-weight:bold}
.ok{color:#16a34a}.fail{color:#dc2626}
</style></head>
<body>
<h1>🩺 Buku Tu — Health Check</h1>
<table>
<?php foreach($checks as $label => $result): ?>
<tr>
  <td><?= $label ?></td>
  <td class="<?= str_starts_with($result, '✅') ? 'ok' : 'fail' ?>"><?= $result ?></td>
</tr>
<?php endforeach; ?>
</table>
<p style="margin-top:20px;color:#666;font-size:13px">
If you see this, PHP-FPM is working. The 503 is from something else.
</p>
</body>
</html>
