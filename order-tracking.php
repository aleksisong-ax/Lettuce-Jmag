<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$orderNumber = $_GET['order'] ?? '';
$filter = $_GET['filter'] ?? 'all';

// Get specific order or list
$orders = [];
if ($orderNumber) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->execute([$orderNumber, $_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $where = "WHERE user_id = ?";
    $params = [$_SESSION['user_id']];
    if ($filter === 'active') {
        $where .= " AND status NOT IN ('completed','cancelled','delivered')";
    } elseif ($filter === 'completed') {
        $where .= " AND status IN ('completed','delivered')";
    } elseif ($filter !== 'all' && $filter !== 'active' && $filter !== 'completed') {
        $where .= " AND status = ?";
        $params[] = $filter;
    }
    $stmt = $conn->prepare("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT 20");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$statusSteps = [
    'pending' => ['label' => 'Order Received', 'icon' => '📋', 'desc' => 'Your order has been placed and is awaiting payment confirmation.'],
    'payment_confirmed' => ['label' => 'Payment Confirmed', 'icon' => '💳', 'desc' => 'Payment confirmed. Your lettuce is queued for harvesting.'],
    'harvest_queue' => ['label' => 'Harvest Queue', 'icon' => '📝', 'desc' => 'Your order is in the harvest queue.'],
    'harvesting' => ['label' => 'Harvesting', 'icon' => '✂️', 'desc' => 'Your lettuce is being freshly harvested from our hydroponic system.'],
    'packing' => ['label' => 'Packing', 'icon' => '📦', 'desc' => 'Your freshly harvested lettuce is being carefully packed.'],
    'ready_pickup' => ['label' => 'Ready for Pick-Up', 'icon' => '🛍️', 'desc' => 'Your order is ready for pick-up at the farm.'],
    'out_delivery' => ['label' => 'Out for Delivery', 'icon' => '🚚', 'desc' => 'Your order is on its way to your delivery address.'],
    'delivered' => ['label' => 'Delivered', 'icon' => '✅', 'desc' => 'Your order has been delivered. Enjoy your fresh lettuce!'],
    'completed' => ['label' => 'Completed', 'icon' => '🎉', 'desc' => 'Order completed successfully. Thank you!'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => '✕', 'desc' => 'This order has been cancelled.'],
];

$statusOrder = array_keys($statusSteps);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order Tracking | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="max-w-4xl mx-auto px-6 py-8">
  <h1 class="text-3xl font-black mb-6">📦 Order Tracking</h1>

  <?php if (empty($orders)): ?>
    <div class="text-center py-16 bg-white rounded-xl border">
      <p class="text-5xl mb-4">📦</p>
      <h2 class="text-xl font-black mb-2">No orders found</h2>
      <p class="text-[#5a7a5c] mb-4">Start shopping to see your orders here!</p>
      <a href="products.php" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Browse Products</a>
    </div>
  <?php elseif ($orderNumber && count($orders) === 1): ?>
    <!-- Single Order Detail -->
    <?php $order = $orders[0];
      $items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
      $items->execute([$order['id']]);
      $orderItems = $items->fetchAll(PDO::FETCH_ASSOC);
      $currentIdx = array_search($order['status'], $statusOrder);
      $isCancelled = $order['status'] === 'cancelled';
    ?>
    <a href="order-tracking.php" class="inline-flex items-center gap-1 text-sm text-[#17611f] font-semibold hover:underline mb-6">← Back to All Orders</a>

    <!-- Order Status Header -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
          <p class="text-xs text-[#5a7a5c] font-bold">Order Number</p>
          <p class="font-black text-lg"><?= htmlspecialchars($order['order_number']) ?></p>
          <p class="text-xs text-[#9e9e9e]"><?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></p>
        </div>
        <span class="px-3 py-1.5 rounded-full text-xs font-bold <?= in_array($order['status'],['completed','delivered'])?'bg-green-100 text-green-700':(in_array($order['status'],['cancelled'])?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700') ?>">
          <?= $statusSteps[$order['status']]['icon'] . ' ' . $statusSteps[$order['status']]['label'] ?>
        </span>
      </div>

      <!-- Status Timeline - Vertical with icons -->
      <div class="relative">
        <?php
        $displaySteps = $statusOrder;
        if ($isCancelled) {
            // For cancelled orders, show steps up to the point of cancellation
            $displaySteps = array_slice($statusOrder, 0, $currentIdx + 1);
        }
        ?>
        <?php foreach ($displaySteps as $i => $step):
          $stepIdx = array_search($step, $statusOrder);
          $isDone = $stepIdx !== false && $currentIdx !== false && $stepIdx <= $currentIdx && !$isCancelled;
          $isCurrent = $step === $order['status'];
          $isAfter = $stepIdx !== false && $currentIdx !== false && $stepIdx > $currentIdx;
        ?>
          <div class="flex gap-4">
            <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg <?= $isCurrent ? 'bg-[#17611f] text-white shadow-lg shadow-[#17611f]/20' : ($isDone ? 'bg-[#e8f5e9] text-[#17611f]' : ($isCancelled && $stepIdx >= $currentIdx ? 'bg-red-50 text-red-400' : 'bg-gray-100 text-[#9e9e9e]')) ?>">
                <?= $statusSteps[$step]['icon'] ?>
              </div>
              <?php if ($i < count($displaySteps) - 1): ?>
                <div class="w-0.5 h-8 <?= $isDone && !$isCancelled ? 'bg-[#17611f]' : ($isCancelled && $stepIdx >= $currentIdx ? 'bg-red-200' : 'bg-gray-200') ?>"></div>
              <?php endif; ?>
            </div>
            <div class="pb-6 flex-1">
              <p class="font-bold text-sm <?= $isCurrent ? 'text-[#17611f]' : ($isDone ? 'text-[#1a2e1c]' : ($isCancelled && $stepIdx >= $currentIdx ? 'text-red-400' : 'text-[#9e9e9e]')) ?>">
                <?= $statusSteps[$step]['label'] ?>
                <?php if ($isCurrent && !$isCancelled): ?><span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#e8f5e9] text-[#17611f] animate-pulse">Current</span><?php endif; ?>
              </p>
              <p class="text-xs text-[#5a7a5c] mt-0.5"><?= $statusSteps[$step]['desc'] ?></p>
              <?php if ($isCurrent && $order['status'] === 'harvesting' && $order['estimated_harvest_time']): ?>
                <p class="text-xs text-[#17611f] font-bold mt-1">⏱ <?= htmlspecialchars($order['estimated_harvest_time']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <h3 class="font-black text-lg mb-4">🛒 Items</h3>
      <div class="space-y-2">
        <?php foreach ($orderItems as $oi): ?>
          <div class="flex justify-between text-sm py-2 border-b border-[rgba(27,94,32,0.05)] last:border-0">
            <span class="font-medium"><?= htmlspecialchars($oi['product_name']) ?> × <?= $oi['quantity'] ?></span>
            <span class="font-bold">₱<?= number_format($oi['price'] * $oi['quantity'], 2) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="flex justify-between font-black text-lg mt-3 pt-3 border-t border-[rgba(27,94,32,0.12)]">
        <span>Total</span>
        <span class="text-[#17611f]">₱<?= number_format($order['total'], 2) ?></span>
      </div>
    </div>

    <!-- Order Details -->
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6 mb-4">
      <h3 class="font-black text-lg mb-4">📋 Order Details</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div class="bg-[#f4faf5] rounded-xl p-3">
          <p class="text-xs text-[#5a7a5c] font-bold">Delivery Method</p>
          <p class="font-bold mt-0.5"><?= $order['delivery_method'] === 'pickup' ? '🛍️ Farm Pick-Up' : '🚚 Delivery' ?></p>
        </div>
        <div class="bg-[#f4faf5] rounded-xl p-3">
          <p class="text-xs text-[#5a7a5c] font-bold">Payment Method</p>
          <p class="font-bold mt-0.5"><?= strtoupper(str_replace('_', ' ', $order['payment_method'])) ?></p>
        </div>
        <div class="bg-[#f4faf5] rounded-xl p-3">
          <p class="text-xs text-[#5a7a5c] font-bold">Delivery Fee</p>
          <p class="font-bold mt-0.5 <?= $order['delivery_fee'] == 0 ? 'text-green-600' : '' ?>"><?= $order['delivery_fee'] == 0 ? 'FREE 🎉' : '₱' . number_format($order['delivery_fee'], 2) ?></p>
        </div>
        <div class="bg-[#f4faf5] rounded-xl p-3">
          <p class="text-xs text-[#5a7a5c] font-bold">Order Date</p>
          <p class="font-bold mt-0.5"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
        </div>
        <?php if ($order['delivery_method'] !== 'pickup'): ?>
          <div class="sm:col-span-2 bg-[#f4faf5] rounded-xl p-3">
            <p class="text-xs text-[#5a7a5c] font-bold">Delivery Address</p>
            <p class="font-bold mt-0.5"><?= htmlspecialchars($order['delivery_address'] . ', ' . $order['delivery_city'] . ', ' . $order['delivery_province'] . ' ' . ($order['delivery_zip'] ?? '')) ?></p>
          </div>
        <?php endif; ?>
        <?php if (!empty($order['delivery_notes'])): ?>
          <div class="sm:col-span-2 bg-[#f4faf5] rounded-xl p-3">
            <p class="text-xs text-[#5a7a5c] font-bold">Delivery Notes</p>
            <p class="font-bold mt-0.5"><?= htmlspecialchars($order['delivery_notes']) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-3">
      <a href="my-profile.php?section=orders" class="px-6 py-3 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a] transition-colors">My Orders</a>
      <a href="products.php" class="px-6 py-3 rounded-xl border border-[rgba(27,94,32,0.12)] text-sm font-bold hover:bg-[#e8f5e9] transition-colors">Continue Shopping</a>
      <?php if (!empty($order['gift_note'])): ?>
        <div class="w-full mt-2 p-4 rounded-xl bg-[#fff8e1] border border-[#ffe082]">
          <p class="text-xs font-bold text-[#e65100] mb-1">🎁 Gift Note</p>
          <p class="text-sm text-[#e65100]"><?= htmlspecialchars($order['gift_note']) ?></p>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <!-- Order List -->
    <div class="flex flex-wrap gap-2 mb-6">
      <a href="?filter=all" class="px-4 py-2 rounded-full text-xs font-bold <?= $filter==='all'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]' ?>">All Orders</a>
      <a href="?filter=active" class="px-4 py-2 rounded-full text-xs font-bold <?= $filter==='active'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]' ?>">Active</a>
      <a href="?filter=completed" class="px-4 py-2 rounded-full text-xs font-bold <?= $filter==='completed'?'bg-[#17611f] text-white':'bg-white border text-[#5a7a5c]' ?>">Completed</a>
    </div>
    <div class="space-y-3">
      <?php foreach ($orders as $o): ?>
        <a href="?order=<?= urlencode($o['order_number']) ?>" class="block bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-5 hover:shadow-md transition-all group">
          <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
            <div>
              <p class="font-black text-lg group-hover:text-[#17611f] transition-colors"><?= htmlspecialchars($o['order_number']) ?></p>
              <p class="text-xs text-[#9e9e9e]"><?= date('M j, Y · g:i A', strtotime($o['created_at'])) ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold <?= in_array($o['status'],['completed','delivered'])?'bg-green-100 text-green-700':(in_array($o['status'],['cancelled'])?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700') ?>">
              <?= $statusSteps[$o['status']]['icon'] . ' ' . $statusSteps[$o['status']]['label'] ?>
            </span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-[#5a7a5c]"><?= $o['delivery_method'] === 'pickup' ? '🛍️ Pick-Up' : '🚚 Delivery' ?> · <?= strtoupper($o['payment_method']) ?></span>
            <span class="font-bold text-[#17611f]">₱<?= number_format($o['total'], 2) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
