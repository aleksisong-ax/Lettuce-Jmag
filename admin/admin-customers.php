<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin-auth.php';

$activePage = 'customers';
$pageTitle = 'Customers';

$message = '';
$messageType = '';
$emailParam = trim($_GET['email'] ?? '');
$search = trim($_GET['q'] ?? '');

$customer = null;
$tickets = [];
$warranty = [];
$returns = [];

if ($emailParam !== '') {
    // Single customer detail view
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$emailParam]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $uid = $customer['id'];
        $tickets = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
        $tickets->execute([$uid]);
        $tickets = $tickets->fetchAll(PDO::FETCH_ASSOC);

        $warranty = $conn->prepare("SELECT * FROM warranty_requests WHERE user_id = ? ORDER BY created_at DESC");
        $warranty->execute([$uid]);
        $warranty = $warranty->fetchAll(PDO::FETCH_ASSOC);

        $returns = $conn->prepare("SELECT * FROM return_requests WHERE user_id = ? ORDER BY created_at DESC");
        $returns->execute([$uid]);
        $returns = $returns->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
    $sql = "
        SELECT u.*,
            (SELECT COUNT(*) FROM tickets t WHERE t.user_id = u.id) AS ticket_count,
            (SELECT COUNT(*) FROM warranty_requests w WHERE w.user_id = u.id) AS warranty_count,
            (SELECT COUNT(*) FROM return_requests r WHERE r.user_id = u.id) AS return_count
        FROM users u
        WHERE 1=1
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }
    $sql .= " ORDER BY u.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCustomers = (int)$conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $customer) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (!preg_match('/^\d{11}$/', $phone)) {
        $message = 'Please enter a valid 11-digit phone number using numbers only.';
        $messageType = 'error';
    } else {
        try {
            $duplicate = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $duplicate->execute([$email, $customer['id']]);

            if ($duplicate->rowCount() > 0) {
                $message = 'That email address is already in use by another account.';
                $messageType = 'error';
            } else {
                $update = $conn->prepare("
                    UPDATE users
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");

                $success = $update->execute([$first_name, $last_name, $email, $phone, $address, $customer['id']]);

                if ($success) {
                    header('Location: admin-customers.php?email=' . urlencode($email));
                    exit();
                } else {
                    $message = 'Something went wrong updating the customer profile. Please try again.';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customers | WoodCraft Admin</title>
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

      <main class="flex-1 overflow-y-auto p-6 space-y-5">

        <?php if ($emailParam !== ''): ?>

          <a href="admin-customers.php" class="inline-flex items-center gap-2 text-sm text-[#6B4226] hover:text-[#59341C] transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Customers
          </a>

          <?php if (!$customer): ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-sm text-gray-400">No customer found with that email.</div>
          <?php else: ?>
            <?php if (!empty($message)): ?>
              <div data-flash-message class="rounded-2xl border px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700' ?>">
                <?= htmlspecialchars($message) ?>
              </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex items-center gap-4">
              <div class="w-14 h-14 rounded-full bg-[#6B4226] text-white text-lg font-semibold flex items-center justify-center"><?= strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)) ?></div>
              <div class="flex-1">
                <h1 class="font-serif text-2xl font-semibold text-gray-900"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></h1>
                <p class="text-[13px] text-gray-500"><?= htmlspecialchars($customer['email']) ?> · <?= htmlspecialchars($customer['phone']) ?> · Joined <?= date('M j, Y', strtotime($customer['created_at'])) ?></p>
              </div>
              <button type="button" id="toggleCustomerEditBtn" aria-expanded="false"
                      class="px-4 py-2 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">
                Edit
              </button>
            </div>

            <form id="customerEditForm" method="POST" class="hidden bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
              <div class="flex items-center justify-between">
                <h2 class="font-serif text-xl font-semibold text-gray-900">Edit Customer Information</h2>
                <span class="text-[11px] uppercase tracking-wide text-gray-400">Admin Update</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="first_name" class="block text-sm font-medium text-gray-800 mb-2">First Name</label>
                  <input type="text" id="first_name" name="first_name" required
                         value="<?= htmlspecialchars($_POST['first_name'] ?? $customer['first_name']) ?>"
                         class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
                </div>
                <div>
                  <label for="last_name" class="block text-sm font-medium text-gray-800 mb-2">Last Name</label>
                  <input type="text" id="last_name" name="last_name" required
                         value="<?= htmlspecialchars($_POST['last_name'] ?? $customer['last_name']) ?>"
                         class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
                </div>
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-800 mb-2">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? $customer['email']) ?>"
                       class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
              </div>

              <div>
                <label for="phone" class="block text-sm font-medium text-gray-800 mb-2">Phone Number</label>
                <input type="text" id="phone" name="phone" placeholder="09123456789" required minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]*"
                       value="<?= htmlspecialchars($_POST['phone'] ?? $customer['phone']) ?>"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                       class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
              </div>

              <div>
                <label for="address" class="block text-sm font-medium text-gray-800 mb-2">Address</label>
                <textarea id="address" name="address" rows="3" placeholder="Street, City, State, ZIP Code" required
                          class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors resize-y"><?= htmlspecialchars($_POST['address'] ?? $customer['address'] ?? '') ?></textarea>
              </div>

              <div class="flex gap-3">
                <button type="submit" class="px-5 py-2.5 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">Save Changes</button>
                <a href="admin-customers.php?email=<?= urlencode($customer['email']) ?>" class="px-5 py-2.5 rounded-full border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</a>
              </div>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const toggleCustomerEditBtn = document.getElementById('toggleCustomerEditBtn');
                    const customerEditForm = document.getElementById('customerEditForm');

                    if (toggleCustomerEditBtn && customerEditForm) {
                        toggleCustomerEditBtn.addEventListener('click', () => {
                            const isHidden = customerEditForm.classList.toggle('hidden');
                            toggleCustomerEditBtn.textContent = isHidden ? 'Edit' : 'Hide Details';
                            toggleCustomerEditBtn.setAttribute('aria-expanded', String(!isHidden));
                        });
                    }
                });
            </script>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
              <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Tickets (<?= count($tickets) ?>)</h3>
                <?php if (empty($tickets)): ?><p class="text-[13px] text-gray-400">None yet.</p><?php endif; ?>
                <div class="space-y-2">
                  <?php foreach ($tickets as $t): ?>
                    <a href="admin-ticket-detail.php?id=<?= $t['id'] ?>" class="block text-[13px] text-gray-700 hover:text-[#6B4226] truncate">#WC-<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?> — <?= htmlspecialchars($t['subject']) ?></a>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Warranty Claims (<?= count($warranty) ?>)</h3>
                <?php if (empty($warranty)): ?><p class="text-[13px] text-gray-400">None yet.</p><?php endif; ?>
                <div class="space-y-2">
                  <?php foreach ($warranty as $w): ?>
                    <p class="text-[13px] text-gray-700 truncate">
                      <?= htmlspecialchars($w['product_name']) ?>
                      <?php if (!empty($w['purchase_date'])): ?>
                        <span class="text-gray-400">· Purchased <?= date('M j, Y', strtotime($w['purchase_date'])) ?></span>
                      <?php endif; ?>
                      — <span class="text-gray-400"><?= ucfirst($w['status']) ?></span>
                    </p>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Returns (<?= count($returns) ?>)</h3>
                <?php if (empty($returns)): ?><p class="text-[13px] text-gray-400">None yet.</p><?php endif; ?>
                <div class="space-y-2">
                  <?php foreach ($returns as $r): ?>
                    <p class="text-[13px] text-gray-700 truncate">Order #<?= htmlspecialchars($r['order_number']) ?> — <span class="text-gray-400"><?= ucfirst($r['status']) ?></span></p>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

        <?php else: ?>

          <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search customers by name or email..." class="w-full max-w-md rounded-full border border-gray-200 px-4 py-2 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors" />
            <button type="submit" class="px-4 py-2 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">Search</button>
          </form>

          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left">
                <thead>
                  <tr class="text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-100">
                    <th class="py-3 px-4 font-medium">Customer</th>
                    <th class="py-3 px-4 font-medium">Email</th>
                    <th class="py-3 px-4 font-medium">Phone</th>
                    <th class="py-3 px-4 font-medium">Address</th>
                    <th class="py-3 px-4 font-medium">Tickets</th>
                    <th class="py-3 px-4 font-medium">Warranty</th>
                    <th class="py-3 px-4 font-medium">Returns</th>
                    <th class="py-3 px-4 font-medium">Joined</th>
                    <th class="py-3 px-4 font-medium">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($customers)): ?>
                    <tr><td colspan="9" class="py-10 px-4 text-center text-sm text-gray-400">No customers found.</td></tr>
                  <?php else: foreach ($customers as $c): ?>
                    <tr class="border-b border-gray-50 last:border-0 hover:bg-gray-50/60">
                      <td class="py-3 px-4 text-[13px] font-medium text-gray-900"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-500"><?= htmlspecialchars($c['email']) ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-500"><?= htmlspecialchars($c['phone']) ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-500 max-w-[200px] truncate" title="<?= htmlspecialchars($c['address'] ?? '') ?>">
                        <?= htmlspecialchars($c['address'] ?: '—') ?>
                      </td>
                      <td class="py-3 px-4 text-[13px] text-gray-500"><?= $c['ticket_count'] ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-500"><?= $c['warranty_count'] ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-500"><?= $c['return_count'] ?></td>
                      <td class="py-3 px-4 text-[13px] text-gray-400"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                      <td class="py-3 px-4"><a href="admin-customers.php?email=<?= urlencode($c['email']) ?>" class="text-[12px] font-medium text-[#6B4226] hover:underline">View</a></td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        <?php endif; ?>

      </main>
    </div>
  </div>
</body>
</html>