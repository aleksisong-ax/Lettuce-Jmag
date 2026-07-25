<?php
session_start();
require 'config.php';
require_once __DIR__ . '/includes/form-helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// User no longer exists (deleted from database)
if (!$user) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

// --- Pull the customer's activity for the dashboard ---
$uid = $_SESSION['user_id'];

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get all activity data (unfiltered for stats)
$ticketStmt = $conn->prepare("SELECT id, subject, category, priority, order_number, issue_description AS detail, attachment_path, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$ticketStmt->execute([$uid]);
$tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

$warrantyStmt = $conn->prepare("SELECT id, product_name, order_number, purchase_date, warranty_issue, defect_description AS detail, proof_of_purchase_path, damage_photo_path, status, admin_note, updated_at, created_at FROM warranty_requests WHERE user_id = ? ORDER BY created_at DESC");
$warrantyStmt->execute([$uid]);
$warranties = $warrantyStmt->fetchAll(PDO::FETCH_ASSOC);

$returnStmt = $conn->prepare("SELECT id, order_number, product_name, purchase_date, reason_category, reason AS detail, product_condition, proof_of_purchase_path, damage_photo_path, status, admin_note, updated_at, created_at FROM return_requests WHERE user_id = ? ORDER BY created_at DESC");
$returnStmt->execute([$uid]);
$returns = $returnStmt->fetchAll(PDO::FETCH_ASSOC);

$feedbackStmt = $conn->prepare("SELECT id, subject, rating, comments AS detail, created_at FROM feedback WHERE user_id = ? ORDER BY created_at DESC");
$feedbackStmt->execute([$uid]);
$feedbackEntries = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);

// Merge everything into one activity feed
$allActivity = [];

foreach ($tickets as $t) {
    $allActivity[] = [
        'type' => 'Ticket',
        'type_key' => 'ticket',
        'icon' => 'images/ticket.png',
        'id' => $t['id'],
        'title' => $t['order_number'] ? 'Order ' . $t['order_number'] : 'Support Ticket',
        'detail' => $t['detail'],
        'status' => $t['status'],
        'created_at' => $t['created_at'],
        'has_admin_note' => false,
        'admin_note' => null,
        'updated_at' => null,
        'subject' => $t['subject'],
        'category' => $t['category'],
        'priority' => $t['priority'],
        'order_number' => $t['order_number'],
        'attachment_path' => decodeAttachmentPaths($t['attachment_path']),
    ];
}

foreach ($warranties as $w) {
    $allActivity[] = [
        'type' => 'Warranty',
        'type_key' => 'warranty',
        'icon' => 'images/warranty.png',
        'id' => $w['id'],
        'title' => $w['product_name'],
        'detail' => $w['detail'],
        'status' => $w['status'],
        'admin_note' => $w['admin_note'],
        'has_admin_note' => !empty($w['admin_note']),
        'updated_at' => $w['updated_at'],
        'created_at' => $w['created_at'],
        'purchase_date' => $w['purchase_date'] ?? null,
        'product_name' => $w['product_name'],
        'order_number' => $w['order_number'],
        'warranty_issue' => $w['warranty_issue'],
        'proof_of_purchase_path' => decodeAttachmentPaths($w['proof_of_purchase_path']),
        'damage_photo_path' => decodeAttachmentPaths($w['damage_photo_path']),
    ];
}

foreach ($returns as $r) {
    $allActivity[] = [
        'type' => 'Return',
        'type_key' => 'return',
        'icon' => 'images/return.png',
        'id' => $r['id'],
        'title' => $r['order_number'] ? 'Order ' . $r['order_number'] : 'Return Request',
        'detail' => $r['detail'],
        'status' => $r['status'],
        'admin_note' => $r['admin_note'],
        'has_admin_note' => !empty($r['admin_note']),
        'updated_at' => $r['updated_at'],
        'created_at' => $r['created_at'],
        'order_number' => $r['order_number'],
        'product_name' => $r['product_name'],
        'purchase_date' => $r['purchase_date'] ?? null,
        'reason_category' => $r['reason_category'],
        'product_condition' => $r['product_condition'],
        'proof_of_purchase_path' => decodeAttachmentPaths($r['proof_of_purchase_path']),
        'damage_photo_path' => decodeAttachmentPaths($r['damage_photo_path']),
    ];
}

foreach ($feedbackEntries as $f) {
    $allActivity[] = [
        'type' => 'Feedback',
        'type_key' => 'feedback',
        'icon' => 'images/feedback.png',
        'title' => 'Feedback Submitted',
        'detail' => $f['detail'] ?: 'No comments provided.',
        'status' => null,
        'rating' => (int)$f['rating'],
        'created_at' => $f['created_at'],
        'has_admin_note' => false,
        'admin_note' => null,
        'subject' => $f['subject'],
    ];
}

// Sort by created_at descending
usort($allActivity, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

// Apply filter
$filteredActivity = $allActivity;
if ($filter !== 'all') {
    $filteredActivity = array_filter($allActivity, function($item) use ($filter) {
        return $item['type_key'] === $filter;
    });
    // Re-index array
    $filteredActivity = array_values($filteredActivity);
}

// Paginate
$totalItems = count($filteredActivity);
$totalPages = ceil($totalItems / $perPage);
$paginatedActivity = array_slice($filteredActivity, $offset, $perPage);

// Stat counters
$openTickets = count(array_filter($tickets, fn($t) => in_array($t['status'], ['open', 'in_progress'])));
$pendingWarranty = count(array_filter($warranties, fn($w) => $w['status'] === 'pending'));
$pendingReturns = count(array_filter($returns, fn($r) => $r['status'] === 'pending'));
$totalActivity = count($allActivity);
$feedbackCount = count($feedbackEntries);
$avgRating = $feedbackCount > 0
    ? round(array_sum(array_column($feedbackEntries, 'rating')) / $feedbackCount, 1)
    : null;

// Count items with admin notes
$itemsWithNotes = array_filter($allActivity, function($item) {
    return $item['has_admin_note'] === true;
});
$notesCount = count($itemsWithNotes);

// Status badge styles
$statusStyles = [
    'open'         => 'bg-blue-100 text-blue-700',
    'in_progress'  => 'bg-amber-100 text-amber-700',
    'resolved'     => 'bg-green-100 text-green-700',
    'closed'       => 'bg-gray-200 text-gray-600',
    'pending'      => 'bg-amber-100 text-amber-700',
    'approved'     => 'bg-green-100 text-green-700',
    'denied'       => 'bg-red-100 text-red-700',
    'completed'    => 'bg-green-100 text-green-700',
];

function statusLabel($status) {
    return ucwords(str_replace('_', ' ', $status));
}

// Get filter label for display
$filterLabels = [
    'all' => 'All Activity',
    'ticket' => 'Tickets',
    'warranty' => 'Warranty',
    'return' => 'Returns',
    'feedback' => 'Feedback'
];
$currentFilterLabel = $filterLabels[$filter] ?? 'All Activity';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Help Center | WoodCraft Care</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-serif { font-family: 'Fraunces', serif; }
    
    /* Filter tabs animation */
    .filter-tab {
      transition: all 0.2s ease;
    }
    .filter-tab.active {
      background-color: #6B4226;
      color: white;
    }
    .filter-tab:not(.active):hover {
      background-color: #f3f0e4;
    }
    
    /* Admin note badge pulse */
    @keyframes pulse-dot {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .note-badge {
      animation: pulse-dot 2s ease-in-out infinite;
    }
    
    /* Activity card transitions */
    .activity-card {
      transition: all 0.2s ease;
    }
    .activity-card:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    /* Pagination styles */
    .pagination-btn {
      transition: all 0.2s ease;
    }
    .pagination-btn:hover:not(.disabled) {
      background-color: #f3f0e4;
    }
    .pagination-btn.active {
      background-color: #6B4226;
      color: white;
    }
    .pagination-btn.disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>
</head>
<body class="bg-[#F3F0E4] text-gray-900">

  <!-- Header -->
  <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-20 flex items-center justify-between">
      <a href="my-profile.php" class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-[#2E1D14] flex items-center justify-center">
          <span class="text-[#D8B98A] font-serif text-lg tracking-tight">W</span>
        </div>
        <span class="text-lg font-semibold text-gray-900">WoodCraft Care</span>
      </a>
      <div class="flex items-center gap-3">
        <a href="my-profile.php" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.964 0a9 9 0 10-11.964 0m11.964 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <?= htmlspecialchars($_SESSION['first_name']) ?>
        </a>
        <a href="logout.php" class="px-5 py-2.5 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">
          Logout
        </a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-6 lg:px-8 py-12">

    <!-- Page Title -->
    <div class="mb-10">
      <h1 class="font-serif text-4xl font-semibold text-gray-900">Customer Dashboard</h1>
      <p class="text-gray-500 mt-2">Manage your WoodCraft Care account and customer services.</p>
    </div>

    <!-- User Profile Header -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 mb-8">
      <div class="font-serif text-3xl font-semibold text-gray-900 mb-6">My Profile</div>
      <div class="flex items-center gap-6">
        <div class="w-24 h-24 rounded-full bg-[#6B4226] flex items-center justify-center">
          <span class="text-white text-4xl font-bold">
            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
          </span>
        </div>
        <div>
          <h2 class="font-serif text-3xl font-semibold text-gray-900">
            <?= htmlspecialchars($user['first_name']) ?>
            <?= htmlspecialchars($user['last_name']) ?>
          </h2>
          <p class="text-gray-500 mt-1"><?= htmlspecialchars($user['email']) ?></p>
          <button type="button" id="toggleProfileInfoBtn" aria-expanded="false" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">
            Show Details
          </button>
        </div>
      </div>
    </div>

    <?php
    $ticketNumberSuffix = '';
    if (!empty($_GET['ticket_id']) && ctype_digit((string)$_GET['ticket_id'])) {
        $ticketNumberSuffix = ' Your ticket number is #WC-' . str_pad((string)$_GET['ticket_id'], 4, '0', STR_PAD_LEFT) . '.';
    }

    $successMessages = [
        'ticket'           => 'Your support ticket has been received successfully.' . $ticketNumberSuffix . ' You can monitor its progress through My Activity --> Tickets / View Conversation.',
        'warranty'         => 'Your warranty request has been submitted successfully. You can track it from your request history below.',
        'return'           => 'Your return & refund request has been submitted successfully. You can track it from your request history below.',
        'feedback'         => 'Thanks for your feedback! We really appreciate it.',
        'profile_updated'  => 'Your profile information was updated successfully.',
        'password_updated' => 'Your password was changed successfully.',
    ];
    foreach ($successMessages as $key => $msg):
        if (isset($_GET[$key])):
    ?>
        <div class="profile-alert mb-8 rounded-2xl bg-green-50 border border-green-100 text-green-700 px-6 py-4 text-sm font-medium transition-all duration-500">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; endforeach; ?>

    <?php if (array_intersect_key($_GET, $successMessages)): ?>
    <script>
        setTimeout(() => {
            document.querySelectorAll('.profile-alert').forEach((alert) => {
                alert.classList.add('opacity-0', '-translate-y-2');
                setTimeout(() => alert.remove(), 400);
            });
        }, 4000);
    </script>
    <?php endif; ?>

    <!-- Profile Information -->
    <div id="profileInformationSection" class="hidden bg-white rounded-3xl shadow-sm border border-gray-200 p-8 mb-8">
      <h2 class="font-serif text-2xl font-semibold mb-6">Profile Information</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-[#F8F8F8] rounded-2xl p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">First Name</p>
          <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['first_name']) ?></p>
        </div>
        <div class="bg-[#F8F8F8] rounded-2xl p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Last Name</p>
          <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['last_name']) ?></p>
        </div>
        <div class="bg-[#F8F8F8] rounded-2xl p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Email</p>
          <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['email']) ?></p>
        </div>
        <div class="bg-[#F8F8F8] rounded-2xl p-5">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Phone Number</p>
          <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($user['phone'] ?? 'Not provided') ?></p>
        </div>
        <div class="bg-[#F8F8F8] rounded-2xl p-5 md:col-span-2">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Address</p>
          <p class="text-lg font-semibold text-gray-900 whitespace-pre-line"><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></p>
        </div>
      </div>
      <div class="flex flex-wrap gap-4 mt-8">
        <a href="edit-profile.php" class="px-6 py-3 rounded-full bg-[#6B4226] text-white font-medium hover:bg-[#59341C] transition">Edit Profile</a>
        <a href="change-password.php" class="px-6 py-3 rounded-full border border-gray-300 text-gray-800 font-medium hover:bg-gray-100 transition">Change Password</a>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
          const toggleProfileInfoBtn = document.getElementById('toggleProfileInfoBtn');
          const profileInformationSection = document.getElementById('profileInformationSection');
          if (toggleProfileInfoBtn && profileInformationSection) {
              toggleProfileInfoBtn.addEventListener('click', () => {
                  const isHidden = profileInformationSection.classList.toggle('hidden');
                  toggleProfileInfoBtn.textContent = isHidden ? 'Show Details' : 'Hide Details';
                  toggleProfileInfoBtn.setAttribute('aria-expanded', String(!isHidden));
              });
          }
      });
    </script>

    <!-- Customer Services -->
    <section class="max-w-7xl mx-auto px-10 mb-20">
      <h2 class="heading text-3xl text-[#2F2F2F]">Customer Services</h2>
      <p class="text-gray-600 mt-2 mb-8">Choose the type of help you need and we'll get you sorted fast.</p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5">
        <!-- Submit Ticket -->
        <a href="submit-ticket.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-[#EFE8DC] hover:bg-[#8B5E3C] hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
          <div class="w-14 h-14 rounded-xl bg-[#FFF5E6] flex items-center justify-center mx-auto group-hover:bg-white/20 transition">
            <img src="images/ticket.png" alt="Submit Ticket" class="w-12 h-12 object-contain">
          </div>
          <h3 class="font-semibold text-center mt-6 text-lg group-hover:text-white transition">Submit a Ticket</h3>
          <p class="text-gray-500 text-center mt-3 text-sm leading-6 group-hover:text-[#F8F6F2] transition">Report an issue with your order or product.</p>
        </a>

        <!-- Warranty -->
        <a href="warranty-request.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-[#EFE8DC] hover:bg-[#8B5E3C] hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
          <div class="w-14 h-14 rounded-xl bg-[#EEF5FF] flex items-center justify-center mx-auto group-hover:bg-white/20">
            <img src="images/warranty.png" alt="Submit Ticket" class="w-12 h-12 object-contain">
          </div>
          <h3 class="font-semibold text-center mt-6 text-lg group-hover:text-white">Warranty Request</h3>
          <p class="text-gray-500 text-center mt-3 text-sm leading-6 group-hover:text-[#F8F6F2]">Claim your manufacturer's warranty.</p>
        </a>

        <!-- Return -->
        <a href="returns-refund.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-[#EFE8DC] hover:bg-[#8B5E3C] hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
          <div class="w-14 h-14 rounded-xl bg-[#EEF3FF] flex items-center justify-center mx-auto group-hover:bg-white/20">
            <img src="images/return.png" alt="Submit Ticket" class="w-12 h-12 object-contain">
          </div>
          <h3 class="font-semibold text-center mt-6 text-lg group-hover:text-white">Return & Refund</h3>
          <p class="text-gray-500 text-center mt-3 text-sm leading-6 group-hover:text-[#F8F6F2]">Initiate a return or request a refund.</p>
        </a>

        <!-- Live Chat -->
        <a href="live-chat.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-[#EFE8DC] hover:bg-[#8B5E3C] hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
          <div class="w-14 h-14 rounded-xl bg-[#FFF3F6] flex items-center justify-center mx-auto group-hover:bg-white/20">
            <img src="images/chat.png" alt="Submit Ticket" class="w-12 h-12 object-contain">
          </div>
          <h3 class="font-semibold text-center mt-6 text-lg group-hover:text-white">Live Chat</h3>
          <p class="text-gray-500 text-center mt-3 text-sm leading-6 group-hover:text-[#F8F6F2]">Talk to a support agent right now.</p>
        </a>

        <!-- Send Feedback -->
        <a href="feedback.php" class="group bg-white rounded-2xl p-6 shadow-sm border border-[#EFE8DC] hover:bg-[#8B5E3C] hover:-translate-y-1 hover:shadow-xl transition-all duration-300">
          <div class="w-14 h-14 rounded-xl bg-[#FFF8E8] flex items-center justify-center mx-auto group-hover:bg-white/20">
            <img src="images/feedback.png" alt="Send Feedback" class="w-10 h-10 object-contain">
          </div>
          <h3 class="font-semibold text-center mt-6 text-lg text-gray-900 group-hover:text-white">Send Feedback</h3>
          <p class="text-gray-500 text-center mt-3 text-sm leading-6 group-hover:text-[#F8F6F2]">Share your experience and help us improve.</p>
        </a>
      </div>
    </section>

    <!-- My Activity -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
          <h2 class="font-serif text-2xl font-semibold text-gray-900">My Activity</h2>
          <p class="text-sm text-gray-500 mt-1">
            Showing <?= $currentFilterLabel ?> · <?= $totalItems ?> total items
            <?php if ($notesCount > 0): ?>
              · <span class="text-[#6B4226] font-medium">💬 <?= $notesCount ?> with admin notes</span>
            <?php endif; ?>
          </p>
        </div>
        <span class="text-sm text-gray-500"><?= $totalActivity ?> total requests</span>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#FFF5E6] flex items-center justify-center flex-shrink-0">
            <img src="images/ticket.png" alt="Tickets" class="w-8 h-8 object-contain">
          </div>
          <div>
            <p class="text-2xl font-semibold text-gray-900"><?= $openTickets ?></p>
            <p class="text-sm text-gray-500">Open Tickets</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#EEF5FF] flex items-center justify-center flex-shrink-0">
            <img src="images/warranty.png" alt="Warranty" class="w-8 h-8 object-contain">
          </div>
          <div>
            <p class="text-2xl font-semibold text-gray-900"><?= $pendingWarranty ?></p>
            <p class="text-sm text-gray-500">Pending Warranty Claims</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#EEF3FF] flex items-center justify-center flex-shrink-0">
            <img src="images/return.png" alt="Returns" class="w-8 h-8 object-contain">
          </div>
          <div>
            <p class="text-2xl font-semibold text-gray-900"><?= $pendingReturns ?></p>
            <p class="text-sm text-gray-500">Pending Returns</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-[#FFF8E8] flex items-center justify-center flex-shrink-0">
            <img src="images/feedback.png" alt="Feedback" class="w-8 h-8 object-contain">
          </div>
          <div>
            <p class="text-2xl font-semibold text-gray-900"><?= $avgRating !== null ? $avgRating . '★' : '—' ?></p>
            <p class="text-sm text-gray-500"><?= $feedbackCount ?> Feedback <?= $feedbackCount === 1 ? 'Entry' : 'Entries' ?></p>
          </div>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div id="activity" class="flex flex-wrap gap-2 mb-6">
        <a href="?filter=all&page=1#activity" 
           class="filter-tab px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $filter === 'all' ? 'active bg-[#6B4226] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          All
        </a>
        <a href="?filter=ticket&page=1#activity" 
           class="filter-tab px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $filter === 'ticket' ? 'active bg-[#6B4226] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          Tickets
          <span class="text-xs opacity-70">(<?= count(array_filter($allActivity, fn($i) => $i['type_key'] === 'ticket')) ?>)</span>
        </a>
        <a href="?filter=warranty&page=1#activity" 
           class="filter-tab px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $filter === 'warranty' ? 'active bg-[#6B4226] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          Warranty
          <span class="text-xs opacity-70">(<?= count(array_filter($allActivity, fn($i) => $i['type_key'] === 'warranty')) ?>)</span>
        </a>
        <a href="?filter=return&page=1#activity" 
           class="filter-tab px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $filter === 'return' ? 'active bg-[#6B4226] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          Returns
          <span class="text-xs opacity-70">(<?= count(array_filter($allActivity, fn($i) => $i['type_key'] === 'return')) ?>)</span>
        </a>
        <a href="?filter=feedback&page=1#activity" 
           class="filter-tab px-4 py-2 rounded-full text-sm font-medium transition-colors <?= $filter === 'feedback' ? 'active bg-[#6B4226] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          Feedback
          <span class="text-xs opacity-70">(<?= count(array_filter($allActivity, fn($i) => $i['type_key'] === 'feedback')) ?>)</span>
        </a>
      </div>

      <!-- Activity Feed -->
      <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8">
        <h3 class="font-serif text-xl font-semibold mb-6">Recent Requests</h3>

        <?php if (empty($paginatedActivity)): ?>
          <div class="text-center py-10">
            <p class="text-gray-500 mb-4">No <?= strtolower($currentFilterLabel) ?> found.</p>
            <?php if ($filter !== 'all'): ?>
              <a href="?filter=all&page=1#activity" class="inline-block px-6 py-3 rounded-full bg-[#6B4226] text-white font-medium hover:bg-[#59341C] transition">
                View All Activity
              </a>
            <?php else: ?>
              <a href="submit-ticket.php" class="inline-block px-6 py-3 rounded-full bg-[#6B4226] text-white font-medium hover:bg-[#59341C] transition">
                Submit a Ticket
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="divide-y divide-gray-100">
            <?php foreach ($paginatedActivity as $idx => $item): ?>
              <div class="activity-card flex items-start gap-4 py-4 cursor-pointer hover:bg-gray-50 -mx-2 px-2 rounded-xl transition-colors" data-activity-index="<?= $idx ?>" onclick="openActivityDetail(<?= $idx ?>)">
                <div class="w-11 h-11 rounded-xl bg-[#F8F8F8] flex items-center justify-center flex-shrink-0">
                  <img src="<?= htmlspecialchars($item['icon']) ?>" alt="<?= htmlspecialchars($item['type']) ?>" class="w-7 h-7 object-contain">
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="font-semibold text-gray-900 truncate">
                      <span class="text-[11px] uppercase tracking-wide text-gray-400 mr-2"><?= htmlspecialchars($item['type']) ?></span>
                      <?= htmlspecialchars($item['title']) ?>
                    </p>
                    <?php if ($item['type'] === 'Feedback'): ?>
                      <span class="flex-shrink-0 text-amber-400 text-sm tracking-tight">
                        <?= str_repeat('★', $item['rating']) . str_repeat('☆', 5 - $item['rating']) ?>
                      </span>
                    <?php else: ?>
                      <span class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-medium <?= $statusStyles[$item['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                        <?= htmlspecialchars(statusLabel($item['status'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <p class="text-sm text-gray-500 mt-1 truncate"><?= htmlspecialchars($item['detail']) ?></p>
                  
                  <?php if ($item['type'] === 'Warranty' && !empty($item['purchase_date'])): ?>
                    <p class="text-xs text-gray-400 mt-1">Purchased on <?= date('M j, Y', strtotime($item['purchase_date'])) ?></p>
                  <?php endif; ?>
                  
                  <!-- Admin Note - Now with better visibility -->
                  <?php if ($item['has_admin_note'] && !empty($item['admin_note'])): ?>
                    <div class="mt-2 bg-amber-50 border-l-4 border-amber-400 rounded-r-xl px-4 py-3">
                      <div class="flex items-start gap-2">
                        <div class="flex-shrink-0 mt-0.5">
                          <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700">
                            <span class="note-badge w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                            💬 Admin Note
                          </span>
                        </div>
                        <div class="flex-1">
                          <p class="text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($item['admin_note']) ?></p>
                          <?php if (!empty($item['updated_at'])): ?>
                            <p class="text-[11px] text-gray-400 mt-1">Updated: <?= date('M j, Y g:i A', strtotime($item['updated_at'])) ?></p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  
                  <div class="flex items-center justify-between gap-3 mt-1">
                    <p class="text-xs text-gray-400"><?= date('M j, Y \a\t g:i A', strtotime($item['created_at'])) ?></p>
                    <?php if ($item['type'] === 'Ticket' && isset($item['id'])): ?>
                      <a href="ticket-view.php?id=<?= (int)$item['id'] ?>" onclick="event.stopPropagation()" class="flex-shrink-0 text-xs font-medium text-[#6B4226] hover:underline">View Conversation →</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
              <p class="text-sm text-gray-500">
                Showing <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalItems) ?> of <?= $totalItems ?>
              </p>
              <div class="flex gap-1">
                <!-- Previous -->
                <a href="?filter=<?= urlencode($filter) ?>&page=<?= max(1, $page - 1) ?>#activity" 
                   class="pagination-btn px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 <?= $page <= 1 ? 'disabled text-gray-400' : 'text-gray-700 hover:bg-gray-50' ?>">
                  ←
                </a>
                
                <!-- Page numbers -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<a href="?filter=' . urlencode($filter) . '&page=1#activity" class="pagination-btn px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 text-gray-700 hover:bg-gray-50">1</a>';
                    if ($startPage > 2) {
                        echo '<span class="px-2 py-1.5 text-sm text-gray-400">…</span>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                  <a href="?filter=<?= urlencode($filter) ?>&page=<?= $i ?>#activity" 
                     class="pagination-btn px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 <?= $i === $page ? 'active bg-[#6B4226] text-white border-[#6B4226]' : 'text-gray-700 hover:bg-gray-50' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
                
                <?php
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<span class="px-2 py-1.5 text-sm text-gray-400">…</span>';
                    }
                    echo '<a href="?filter=' . urlencode($filter) . '&page=' . $totalPages . '#activity" class="pagination-btn px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                }
                ?>
                
                <!-- Next -->
                <a href="?filter=<?= urlencode($filter) ?>&page=<?= min($totalPages, $page + 1) ?>#activity" 
                   class="pagination-btn px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-200 <?= $page >= $totalPages ? 'disabled text-gray-400' : 'text-gray-700 hover:bg-gray-50' ?>">
                  →
                </a>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Activity Detail Modal -->
    <div id="activityDetailModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[1000] p-4" onclick="if(event.target === this) closeActivityDetail()">
      <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-6">
          <div>
            <span id="detailTypeBadge" class="inline-block text-[11px] font-semibold tracking-wide text-[#B5702E] bg-orange-50 rounded-full px-3 py-1 mb-3"></span>
            <h2 id="detailTitle" class="font-serif text-2xl font-semibold text-gray-900"></h2>
          </div>
          <button type="button" onclick="closeActivityDetail()" class="text-gray-400 hover:text-gray-600 transition-colors text-2xl leading-none">✕</button>
        </div>

        <div class="space-y-4">
          <div id="detailStatusRow" class="flex items-center gap-3"></div>

          <div id="detailFieldsGrid" class="grid grid-cols-2 gap-3"></div>

          <div id="detailDetailRow" class="bg-[#F8F6F2] rounded-xl px-4 py-3">
            <span id="detailDetailLabel" class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block mb-1">Details</span>
            <p id="detailDetail" class="text-gray-800 text-sm whitespace-pre-line"></p>
          </div>

          <div id="detailAttachmentsRow" class="hidden bg-[#F8F6F2] rounded-xl px-4 py-3">
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block mb-1">Attachments</span>
            <div id="detailAttachments" class="flex flex-col gap-1"></div>
          </div>

          <div id="detailAdminNoteRow" class="hidden bg-amber-50 border-l-4 border-amber-400 rounded-r-xl px-4 py-3">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 mb-1">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
              💬 Admin Note
            </span>
            <p id="detailAdminNote" class="text-sm text-gray-700 whitespace-pre-line"></p>
            <p id="detailAdminNoteUpdated" class="text-[11px] text-gray-400 mt-1"></p>
          </div>

          <div id="detailCreatedRow" class="text-xs text-gray-400 pt-2 border-t border-gray-100"></div>
        </div>
      </div>
    </div>

  </main>

  <script id="activityData" type="application/json"><?= json_encode(
      $paginatedActivity,
      JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
  ) ?></script>

  <script>
    const activityData = JSON.parse(document.getElementById('activityData').textContent);

    const statusStyleMap = {
      open: 'bg-blue-100 text-blue-700',
      in_progress: 'bg-amber-100 text-amber-700',
      resolved: 'bg-green-100 text-green-700',
      closed: 'bg-gray-200 text-gray-600',
      pending: 'bg-amber-100 text-amber-700',
      approved: 'bg-green-100 text-green-700',
      denied: 'bg-red-100 text-red-700',
      completed: 'bg-green-100 text-green-700',
    };

    function statusLabelJs(status) {
      return status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function formatDateTime(str) {
      if (!str) return '';
      const d = new Date(str.replace(' ', 'T'));
      if (isNaN(d)) return str;
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
        ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    function formatDate(str) {
      if (!str) return '';
      const d = new Date(str.replace(' ', 'T'));
      if (isNaN(d)) return str;
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function fieldHtml(label, value) {
      if (value === null || value === undefined || value === '') return '';
      return '<div class="bg-[#F8F6F2] rounded-xl px-4 py-3">' +
        '<span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block mb-1">' + label + '</span>' +
        '<p class="text-gray-800 text-sm">' + escapeHtmlJs(String(value)) + '</p></div>';
    }

    function escapeHtmlJs(str) {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function attachmentLink(paths, label) {
      if (!paths || !paths.length) return '';
      return paths.map(function (path, i) {
        const fileName = path.split('/').pop();
        const numberedLabel = paths.length > 1 ? label + ' ' + (i + 1) : label;
        return '<a href="' + encodeURI(path) + '" target="_blank" rel="noopener" class="text-sm text-[#6B4226] hover:underline flex items-center gap-1.5">📎 ' + escapeHtmlJs(numberedLabel) + ': ' + escapeHtmlJs(fileName) + '</a>';
      }).join('');
    }

    function openActivityDetail(index) {
      const item = activityData[index];
      if (!item) return;

      document.getElementById('detailTypeBadge').textContent = item.type.toUpperCase();
      document.getElementById('detailTitle').textContent = item.title;

      // Status or star rating
      const statusRow = document.getElementById('detailStatusRow');
      if (item.type === 'Feedback') {
        const rating = parseInt(item.rating, 10) || 0;
        statusRow.innerHTML = '<span class="text-amber-400 text-lg tracking-tight">' +
          '★'.repeat(rating) + '☆'.repeat(5 - rating) + '</span>';
      } else {
        const cls = statusStyleMap[item.status] || 'bg-gray-100 text-gray-600';
        statusRow.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-medium ' + cls + '">' +
          statusLabelJs(item.status || '') + '</span>';
      }

      // Build the grid of original form fields, specific to each activity type
      const grid = document.getElementById('detailFieldsGrid');
      let fields = '';
      let detailLabel = 'Details';

      if (item.type === 'Ticket') {
        fields += fieldHtml('Subject', item.subject);
        fields += fieldHtml('Category', item.category);
        fields += fieldHtml('Priority', item.priority);
        fields += fieldHtml('Order Number', item.order_number);
        detailLabel = 'Issue Description';
      } else if (item.type === 'Warranty') {
        fields += fieldHtml('Product Name', item.product_name);
        fields += fieldHtml('Order Number', item.order_number);
        fields += fieldHtml('Purchase Date', item.purchase_date ? formatDate(item.purchase_date) : '');
        fields += fieldHtml('Warranty Issue', item.warranty_issue);
        detailLabel = 'Defect Description';
      } else if (item.type === 'Return') {
        fields += fieldHtml('Order Number', item.order_number);
        fields += fieldHtml('Product Name', item.product_name);
        fields += fieldHtml('Purchase Date', item.purchase_date ? formatDate(item.purchase_date) : '');
        fields += fieldHtml('Reason Category', item.reason_category);
        fields += fieldHtml('Product Condition', item.product_condition);
        detailLabel = 'Reason for Return';
      } else if (item.type === 'Feedback') {
        fields += fieldHtml('Subject', item.subject);
        detailLabel = 'Comments';
      }

      grid.innerHTML = fields;
      grid.classList.toggle('hidden', fields === '');
      document.getElementById('detailDetailLabel').textContent = detailLabel;

      // Full detail text
      document.getElementById('detailDetail').textContent = item.detail || '';

      // Attachments (varies by type)
      const attachRow = document.getElementById('detailAttachmentsRow');
      const attachContainer = document.getElementById('detailAttachments');
      let attachments = '';
      if (item.type === 'Ticket') {
        attachments += attachmentLink(item.attachment_path, 'Attachment');
      } else if (item.type === 'Warranty' || item.type === 'Return') {
        attachments += attachmentLink(item.proof_of_purchase_path, 'Proof of Purchase');
        attachments += attachmentLink(item.damage_photo_path, 'Damage Photo');
      }
      attachContainer.innerHTML = attachments;
      attachRow.classList.toggle('hidden', attachments === '');

      // Admin note
      const noteRow = document.getElementById('detailAdminNoteRow');
      if (item.has_admin_note && item.admin_note) {
        document.getElementById('detailAdminNote').textContent = item.admin_note;
        document.getElementById('detailAdminNoteUpdated').textContent = item.updated_at ?
          'Updated: ' + formatDateTime(item.updated_at) : '';
        noteRow.classList.remove('hidden');
      } else {
        noteRow.classList.add('hidden');
      }

      document.getElementById('detailCreatedRow').textContent = 'Submitted on ' + formatDateTime(item.created_at);

      document.getElementById('activityDetailModal').classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeActivityDetail() {
      document.getElementById('activityDetailModal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeActivityDetail();
    });
  </script>

</body>
</html>