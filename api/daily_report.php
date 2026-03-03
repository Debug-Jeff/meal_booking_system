<?php
// =====================================================
// API: Daily Admin Report (triggered via cron-job.org)
// =====================================================
require_once '../includes/db.php';
require_once '../includes/mailer.php';

header('Content-Type: application/json');

// ── Authenticate via CRON_SECRET ──────────────────────────────────────────
$secret      = getenv('CRON_SECRET') ?: '';
$authHeader  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$provided    = str_replace('Bearer ', '', $authHeader);

if (!$secret || !hash_equals($secret, trim($provided))) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// ── Gather today's stats ──────────────────────────────────────────────────
$today = date('Y-m-d');

$totals = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='pending')  AS pending,
        SUM(status='approved') AS approved,
        SUM(status='rejected') AS rejected,
        SUM(status='consumed') AS consumed
    FROM bookings
    WHERE date = '$today'
")->fetch_assoc();

$byType = $conn->query("
    SELECT m.type, COUNT(*) AS cnt
    FROM bookings b
    JOIN menus m ON b.menu_id = m.id
    WHERE b.date = '$today'
    GROUP BY m.type
    ORDER BY FIELD(m.type,'Breakfast','Lunch','Dinner')
")->fetch_all(MYSQLI_ASSOC);

// ── Build HTML email body ─────────────────────────────────────────────────
$dateLabel = date('l, d F Y');

$typeRows = '';
foreach ($byType as $row) {
    $typeRows .= "<tr><td style='padding:6px 12px;'>{$row['type']}</td><td style='padding:6px 12px;text-align:right;font-weight:bold;'>{$row['cnt']}</td></tr>";
}
if (!$typeRows) $typeRows = "<tr><td colspan='2' style='padding:6px 12px;color:#999;'>No bookings by meal type.</td></tr>";

$bodyHtml = "
<p>Here is the meal booking summary for <strong>{$dateLabel}</strong>:</p>

<table style='width:100%;border-collapse:collapse;border:1px solid #eee;border-radius:8px;overflow:hidden;margin:12px 0;'>
  <thead style='background:#cc0000;color:#fff;'>
    <tr>
      <th style='padding:8px 12px;text-align:left;'>Status</th>
      <th style='padding:8px 12px;text-align:right;'>Count</th>
    </tr>
  </thead>
  <tbody>
    <tr style='background:#f9f9f9;'><td style='padding:6px 12px;'>Total Bookings</td><td style='padding:6px 12px;text-align:right;font-weight:bold;'>{$totals['total']}</td></tr>
    <tr><td style='padding:6px 12px;color:#856404;'>⏳ Pending</td><td style='padding:6px 12px;text-align:right;'>{$totals['pending']}</td></tr>
    <tr style='background:#f9f9f9;'><td style='padding:6px 12px;color:#155724;'>✅ Approved</td><td style='padding:6px 12px;text-align:right;'>{$totals['approved']}</td></tr>
    <tr><td style='padding:6px 12px;color:#721c24;'>❌ Rejected</td><td style='padding:6px 12px;text-align:right;'>{$totals['rejected']}</td></tr>
    <tr style='background:#f9f9f9;'><td style='padding:6px 12px;color:#0c5460;'>🍴 Consumed</td><td style='padding:6px 12px;text-align:right;'>{$totals['consumed']}</td></tr>
  </tbody>
</table>

<p style='margin-top:16px;'><strong>Breakdown by Meal Type:</strong></p>
<table style='width:100%;border-collapse:collapse;border:1px solid #eee;border-radius:8px;overflow:hidden;'>
  <thead style='background:#fac823;'>
    <tr><th style='padding:8px 12px;text-align:left;'>Meal Type</th><th style='padding:8px 12px;text-align:right;'>Bookings</th></tr>
  </thead>
  <tbody>{$typeRows}</tbody>
</table>
<p style='margin-top:16px;color:#666;font-size:13px;'>Login to the admin panel to view full details and validate meals.</p>
";

$emailHtml = buildEmailHtml("Daily Booking Report — {$dateLabel}", $bodyHtml);

// ── Send to all admins ────────────────────────────────────────────────────
$admins = $conn->query("SELECT email, fullname FROM users WHERE role IN ('admin','super_admin')")->fetch_all(MYSQLI_ASSOC);
$sent   = 0;
foreach ($admins as $admin) {
    if (sendEmail($admin['email'], $admin['fullname'], "ANU Daily Report — {$dateLabel}", $emailHtml)) {
        $sent++;
    }
}

// ── Log it ────────────────────────────────────────────────────────────────
$conn->query("INSERT INTO system_logs (user_id, action, details, ip) VALUES (NULL, 'Daily Report Sent', 'Sent to $sent admin(s) for $today', '" . $_SERVER['REMOTE_ADDR'] . "')");

echo json_encode([
    'ok'         => true,
    'date'       => $today,
    'total'      => (int)$totals['total'],
    'sent_to'    => $sent,
    'admins'     => count($admins),
]);
