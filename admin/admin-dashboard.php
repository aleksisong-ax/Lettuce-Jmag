<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'dashboard';
$pageTitle = 'Dashboard';

// -----------------------------------------------------------
// Overview stats — all pulled live from the database.
// -----------------------------------------------------------
$totalTickets = (int)$conn->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
$openTickets = (int)$conn->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
$inProgressTickets = (int)$conn->query("SELECT COUNT(*) FROM tickets WHERE status = 'in_progress'")->fetchColumn();
$resolvedTickets = (int)$conn->query("SELECT COUNT(*) FROM tickets WHERE status IN ('resolved', 'closed')")->fetchColumn();
$totalUsers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingWarranty = (int)$conn->query("SELECT COUNT(*) FROM warranty_requests WHERE status = 'pending'")->fetchColumn();
$pendingReturns = (int)$conn->query("SELECT COUNT(*) FROM return_requests WHERE status = 'pending'")->fetchColumn();
$totalFeedback = (int)$conn->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avgRating = (float)($conn->query("SELECT COALESCE(AVG(rating), 0) FROM feedback")->fetchColumn());

// -----------------------------------------------------------
// Recent tickets (latest 6), joined to the customer's name/email.
// -----------------------------------------------------------
$recentTickets = $conn->query("
    SELECT t.id, t.subject, t.status, t.created_at,
           u.first_name, u.last_name, u.email
    FROM tickets t
    JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------
// Combined recent activity feed across all submission types.
// -----------------------------------------------------------
// -----------------------------------------------------------
// Most recent individual feedback entries (name, email, message,
// date) — pulled live from the database, guest-aware.
// -----------------------------------------------------------
$recentFeedback = $conn->query("
    SELECT f.*, u.first_name, u.last_name, u.email AS user_email
    FROM feedback f
    LEFT JOIN users u ON u.id = f.user_id
    ORDER BY f.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recentActivity = $conn->query("
    (SELECT 'ticket' AS kind, t.id, CONCAT(u.first_name, ' ', u.last_name) AS customer, t.subject AS title, t.status, t.created_at
     FROM tickets t JOIN users u ON u.id = t.user_id)
    UNION ALL
    (SELECT 'warranty' AS kind, w.id, CONCAT(u.first_name, ' ', u.last_name) AS customer, w.product_name AS title, w.status, w.created_at
     FROM warranty_requests w JOIN users u ON u.id = w.user_id)
    UNION ALL
    (SELECT 'return' AS kind, r.id, CONCAT(u.first_name, ' ', u.last_name) AS customer, r.order_number AS title, r.status, r.created_at
     FROM return_requests r JOIN users u ON u.id = r.user_id)
    ORDER BY created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// -----------------------------------------------------------
// Feedback rating distribution (real percentages, not hardcoded).
// -----------------------------------------------------------
$ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$ratingRows = $conn->query("SELECT rating, COUNT(*) AS c FROM feedback GROUP BY rating")->fetchAll(PDO::FETCH_ASSOC);
foreach ($ratingRows as $row) {
    $ratingCounts[(int)$row['rating']] = (int)$row['c'];
}
$ratingTotal = array_sum($ratingCounts);
function pct(int $count, int $total): int
{
    return $total > 0 ? (int)round(($count / $total) * 100) : 0;
}

// -----------------------------------------------------------
// Warranty status breakdown (real percentages).
// -----------------------------------------------------------
$warrantyCounts = ['approved' => 0, 'pending' => 0, 'denied' => 0];
$warrantyRows = $conn->query("SELECT status, COUNT(*) AS c FROM warranty_requests GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
foreach ($warrantyRows as $row) {
    $warrantyCounts[$row['status']] = (int)$row['c'];
}
$warrantyTotal = array_sum($warrantyCounts);

// -----------------------------------------------------------
// Return requests over the last 4 weeks (real counts, grouped by week).
// -----------------------------------------------------------
$weeklyReturns = $conn->query("
    SELECT YEARWEEK(created_at, 1) AS yw, COUNT(*) AS c
    FROM return_requests
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 4 WEEK)
    GROUP BY yw
    ORDER BY yw ASC
")->fetchAll(PDO::FETCH_ASSOC);
$weeklyReturnCounts = array_values(array_map(fn($r) => (int)$r['c'], $weeklyReturns));
while (count($weeklyReturnCounts) < 4) {
    array_unshift($weeklyReturnCounts, 0);
}
$weeklyReturnCounts = array_slice($weeklyReturnCounts, -4);
$maxWeekly = max(1, max($weeklyReturnCounts));

function statusBadge(string $status): string
{
    $map = [
        'open' => ['blue', 'Open'],
        'in_progress' => ['amber', 'In Progress'],
        'resolved' => ['green', 'Resolved'],
        'closed' => ['gray', 'Closed'],
        'pending' => ['amber', 'Pending'],
        'approved' => ['green', 'Approved'],
        'denied' => ['red', 'Denied'],
        'completed' => ['green', 'Completed'],
        'new' => ['blue', 'New'],
        'read' => ['gray', 'Read'],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    $colors = [
        'blue' => 'text-blue-600 bg-blue-500',
        'amber' => 'text-amber-600 bg-amber-500',
        'green' => 'text-green-600 bg-green-500',
        'gray' => 'text-gray-400 bg-gray-400',
        'red' => 'text-red-600 bg-red-400',
    ];
    [$textColor, $dotColor] = explode(' ', $colors[$color]);
    return "<span class=\"inline-flex items-center gap-1.5 text-[13px] font-medium $textColor\"><span class=\"w-1.5 h-1.5 rounded-full $dotColor\"></span>$label</span>";
}

function timeAgo(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 604800) return floor($diff / 86400) . "d ago";
    return date('M j', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | WoodCraft Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-serif { font-family: 'Fraunces', serif; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #d8cfbd; border-radius: 8px; }
  </style>
</head>
<body class="bg-[#F3F0E4] text-gray-900">
  <div class="flex min-h-screen">
    <?php require_once __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php require_once __DIR__ . '/includes/admin-topbar.php'; ?>

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto p-6 space-y-6">

        <!-- Overview -->
        <div>
          <h2 class="text-sm font-semibold text-gray-500 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Overview
          </h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $totalTickets ?></p>
              <p class="text-[11px] text-gray-500">Total Tickets</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $openTickets ?></p>
              <p class="text-[11px] text-gray-500">Open Tickets</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $inProgressTickets ?></p>
              <p class="text-[11px] text-gray-500">In Progress</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $resolvedTickets ?></p>
              <p class="text-[11px] text-gray-500">Resolved/Closed</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 10-8 0"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $totalUsers ?></p>
              <p class="text-[11px] text-gray-500">Total Customers</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $pendingWarranty + $pendingReturns ?></p>
              <p class="text-[11px] text-gray-500">Pending Warranty/Returns</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
              <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center mb-3"><svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg></div>
              <p class="text-2xl font-semibold text-gray-900 leading-none mb-1"><?= $totalFeedback > 0 ? number_format($avgRating, 1) : '—' ?></p>
              <p class="text-[11px] text-gray-500">Avg Rating (<?= $totalFeedback ?>)</p>
            </div>
          </div>
        </div>


        <!-- Feedback + Warranty/Returns -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-gray-900">Customer Feedback</h3>
              <span class="text-[12px] text-gray-400"><?= $ratingTotal ?> total</span>
            </div>

            <?php
            $ratingLabels = [5 => 'Excellent (5★)', 4 => 'Good (4★)', 3 => 'Neutral (3★)', 2 => 'Poor (2★)', 1 => 'Very Poor (1★)'];
            $ratingBarColors = [5 => 'bg-green-500', 4 => 'bg-blue-500', 3 => 'bg-amber-400', 2 => 'bg-orange-500', 1 => 'bg-red-500'];
            for ($i = 5; $i >= 1; $i--):
                $p = pct($ratingCounts[$i], $ratingTotal);
            ?>
              <div class="mb-3">
                <div class="flex items-center justify-between text-[12px] mb-1">
                  <span class="text-gray-600"><?= $ratingLabels[$i] ?></span>
                  <span class="text-gray-500 font-medium"><?= $p ?>%</span>
                </div>
                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full <?= $ratingBarColors[$i] ?> rounded-full" style="width:<?= $p ?>%"></div>
                </div>
              </div>
            <?php endfor; ?>

            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-3">
              <span class="text-2xl font-semibold text-gray-900"><?= $totalFeedback > 0 ? number_format($avgRating, 1) : '—' ?></span>
              <div class="text-amber-400 text-sm">★★★★★</div>
              <span class="text-[12px] text-gray-400">Based on <?= $ratingTotal ?> review<?= $ratingTotal === 1 ? '' : 's' ?></span>
            </div>
          </div>

          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-semibold text-gray-900">Warranty &amp; Returns</h3>
              <span class="text-[12px] text-gray-400"><?= $warrantyTotal ?> warranty claims</span>
            </div>
            <p class="text-[12px] text-gray-500 mb-2">Warranty Claims</p>
            <?php
            $wLabels = ['approved' => ['Approved', 'bg-green-500'], 'pending' => ['Pending Review', 'bg-amber-400'], 'denied' => ['Denied', 'bg-red-500']];
            foreach ($wLabels as $key => [$label, $color]):
                $p = pct($warrantyCounts[$key], $warrantyTotal);
            ?>
              <div class="mb-3">
                <div class="flex items-center justify-between text-[12px] mb-1">
                  <span class="text-gray-600"><?= $label ?></span>
                  <span class="text-gray-500 font-medium"><?= $p ?>%</span>
                </div>
                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full <?= $color ?> rounded-full" style="width:<?= $p ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>

            <h4 class="text-[13px] font-semibold text-gray-900 mt-5 mb-3">Return Requests (Last 4 Weeks)</h4>
            <div class="flex items-end gap-3 h-24">
              <?php
              $barColors = ['#D8B98A', '#C08D55', '#8B5E34', '#4A2E1D'];
              foreach ($weeklyReturnCounts as $i => $c):
                  $height = $c > 0 ? max(8, (int)round(($c / $maxWeekly) * 90)) : 4;
              ?>
                <div class="flex flex-col items-center gap-2 flex-1">
                  <div class="w-full rounded-t-lg" style="height:<?= $height ?>px; background:<?= $barColors[$i] ?>"></div>
                  <span class="text-[11px] text-gray-500">Wk<?= $i + 1 ?>: <?= $c ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>


      </main>
    </div>
  </div>
</body>
</html>
