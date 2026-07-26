<?php
session_start();
require 'config.php';
$isLoggedIn = isset($_SESSION['user_id']);

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $_SESSION['cart'] = []; unset($_SESSION['selected_cart']);
    $_SESSION['cart_message'] = 'Cart cleared.';
    header("Location: cart.php"); exit();
}

// Save selection from form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sel'])) {
    $_SESSION['selected_cart'] = array_map('intval', $_POST['sel']);
}

$cartItems = []; $subtotal = 0; $selectedSubtotal = 0;
$selectedIds = $_SESSION['selected_cart'] ?? [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $idx => $item) {
        $stmt = $conn->prepare("SELECT id, name, slug, price, image, plants_available, harvest_time FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod) {
            $prod['qty'] = $item['qty']; $prod['cart_idx'] = $idx;
            $prod['line_total'] = $prod['price'] * $item['qty'];
            $subtotal += $prod['line_total'];
            $prod['selected'] = in_array((int)$item['id'], $selectedIds);
            if ($prod['selected']) $selectedSubtotal += $prod['line_total'];
            $cartItems[] = $prod;
        }
    }
}

$allSelected = count($cartItems) > 0 && count(array_filter($cartItems, fn($c) => $c['selected'])) === count($cartItems);
$selectedCount = count(array_filter($cartItems, fn($c) => $c['selected']));

$deliveryFee = 50.00; $isFreeDeliveryZone = false;
if ($isLoggedIn) {
    $us = $conn->prepare("SELECT address FROM users WHERE id = ?"); $us->execute([$_SESSION['user_id']]);
    $u = $us->fetch(PDO::FETCH_ASSOC); $ua = $u['address'] ?? '';
    $isFreeDeliveryZone = stripos($ua, 'nostalji') !== false || stripos($ua, 'paliparan') !== false;
}
$promo = $_SESSION['applied_promo'] ?? null; $discount = 0;
if ($promo) { $discount = $promo['discount_type'] === 'percentage' ? $selectedSubtotal * ($promo['discount_value'] / 100) : $promo['discount_value']; if ($promo['is_free_delivery']) $deliveryFee = 0; }
if ($isFreeDeliveryZone) $deliveryFee = 0;
if ($selectedCount === 0) $deliveryFee = 0;
$total = max(0, $selectedSubtotal + $deliveryFee - $discount);
$message = $_SESSION['cart_message'] ?? ''; unset($_SESSION['cart_message']);
?><!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart | Luntiang H.A.P.A.G.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>body{font-family:'Nunito',sans-serif;background:#f4faf5}</style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">
<?php include __DIR__.'/includes/header.php'; ?>
<main class="max-w-5xl mx-auto px-6 py-8">
<h1 class="text-2xl font-black mb-6">Shopping Cart</h1>
<?php if ($message): ?><div class="mb-4 rounded-xl px-4 py-3 text-sm bg-[#e8f5e9] text-[#17611f] border border-[#c8e6c9]"><?=htmlspecialchars($message)?></div><?php endif; ?>
<?php if (empty($cartItems)): ?>
<div class="text-center py-16 bg-white rounded-xl border"><p class="text-xl font-bold mb-2">Your cart is empty</p>
<p class="text-[#5a7a5c] mb-4">Browse our fresh hydroponic lettuce.</p>
<a href="products.php" class="inline-flex px-6 py-2.5 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Browse Products</a></div>
<?php else: ?>
<form id="cartForm" method="POST" action="checkout.php">
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-3">
  <!-- Select All -->
  <div class="bg-white rounded-xl border p-4 flex items-center gap-3">
    <input type="checkbox" id="selectAll" onchange="toggleAll(this)" <?=$allSelected?'checked':''?> class="w-4 h-4 accent-[#17611f]">
    <label for="selectAll" class="font-bold text-sm cursor-pointer select-none">Select All (<?=count($cartItems)?> items)</label>
  </div>
  <?php foreach ($cartItems as $ci): ?>
  <div class="bg-white rounded-xl border p-4 flex gap-4 items-start">
    <input type="checkbox" name="sel[]" value="<?=$ci['id']?>" <?=$ci['selected']?'checked':''?> onchange="recalc()" class="mt-1 w-4 h-4 accent-[#17611f] item-cb">
    <img src="<?=htmlspecialchars($ci['image']?:'images/lettuce/hero-farm.png')?>" class="w-20 h-20 rounded-lg object-cover" alt="">
    <div class="flex-1">
      <a href="product.php?slug=<?=urlencode($ci['slug'])?>" class="font-bold text-sm hover:text-[#17611f]"><?=htmlspecialchars($ci['name'])?></a>
      <p class="text-xs text-[#5a7a5c]">Harvest time: <?=htmlspecialchars($ci['harvest_time']?:'1-3 hours')?></p>
      <p class="font-black text-[#17611f] text-sm mt-1">P<?=number_format($ci['price'],2)?> each</p>
      <div class="flex items-center gap-3 mt-2">
        <form action="cart-actions.php" method="POST" class="inline-flex items-center gap-1" onsubmit="setTimeout(()=>location.reload(),200)">
          <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=$ci['id']?>"><input type="hidden" name="redirect" value="cart">
          <input type="number" name="qty" value="<?=$ci['qty']?>" min="1" max="<?=$ci['plants_available']?>" class="w-14 text-center text-sm font-bold border rounded-lg py-1 outline-none" onchange="this.form.submit()">
        </form>
        <a href="cart-actions.php?action=remove&id=<?=$ci['id']?>&redirect=cart" class="px-3 py-1 rounded-lg border border-red-200 text-xs font-bold text-red-500 hover:bg-red-50 transition-colors">Remove</a>
      </div>
    </div>
    <p class="font-black text-[#17611f]">P<?=number_format($ci['line_total'],2)?></p>
  </div>
  <?php endforeach; ?>
  <a href="?clear=1" onclick="return confirm('Clear all items?')" class="inline-flex items-center gap-1 px-4 py-2 rounded-xl border border-red-200 text-sm font-bold text-red-500 hover:bg-red-50 transition-colors">Clear Cart</a>
</div>
<!-- Summary -->
<div class="bg-white rounded-xl border p-5 h-fit sticky top-24">
  <h2 class="font-black text-lg mb-4">Order Summary</h2>
  <div class="space-y-2 text-sm mb-4">
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Selected Items</span><span class="font-bold" id="selCount"><?=$selectedCount?></span></div>
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Subtotal</span><span class="font-bold" id="subtotalDisplay">P<?=number_format($selectedSubtotal,2)?></span></div>
    <div class="flex justify-between"><span class="text-[#5a7a5c]">Delivery Fee</span><span class="font-bold <?=$deliveryFee==0?'text-green-600':''?>" id="delFeeDisplay"><?=$deliveryFee==0?'FREE':'P'.number_format($deliveryFee,2)?></span></div>
    <?php if($isFreeDeliveryZone && $deliveryFee==0):?><p class="text-xs text-green-600">Free delivery - Nostalji Subdivision</p><?php endif;?>
    <?php if($discount>0):?><div class="flex justify-between"><span class="text-[#5a7a5c]">Discount</span><span class="font-bold text-red-500">-P<?=number_format($discount,2)?></span></div><?php endif;?>
  </div>
  <div class="flex justify-between font-black text-lg border-t pt-3 mb-4"><span>Total</span><span class="text-[#17611f]" id="totalDisplay">P<?=number_format($total,2)?></span></div>
  <details class="mb-4"><summary class="text-sm font-bold text-[#17611f] cursor-pointer hover:underline">Add Promo Code</summary>
    <form action="cart-actions.php" method="POST" class="flex gap-2 mt-2">
      <input type="hidden" name="action" value="apply_promo"><input type="hidden" name="redirect" value="cart">
      <input type="text" name="promo_code" placeholder="Enter code" class="flex-1 border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40">
      <button class="px-4 py-2 rounded-xl bg-[#17611f] text-white text-sm font-bold hover:bg-[#14521a]">Apply</button>
    </form>
    <?php if($promo):?><p class="text-xs text-green-600 mt-1"><?=htmlspecialchars($promo['code'])?> applied</p><?php endif;?>
  </details>
  <?php if ($isLoggedIn): ?><button type="submit" class="w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Proceed to Checkout</button>
  <?php else: ?><a href="login.php" class="block text-center w-full py-3 rounded-xl bg-[#17611f] text-white font-bold hover:bg-[#14521a]">Login to Checkout</a><p class="text-xs text-center mt-2 text-[#9e9e9e]">You need an account to complete your order</p><?php endif; ?>
  <a href="products.php" class="block text-center w-full py-2.5 mt-2 rounded-xl border text-sm font-bold hover:bg-[#e8f5e9]">Continue Shopping</a>
</div>
</div>
</form>
<?php endif; ?>
</main>

<script>
const items = <?=json_encode(array_map(function($c){return['id'=>(int)$c['id'],'price'=>(float)$c['price'],'qty'=>(int)$c['qty']];},$cartItems))?>;
const cbs = document.querySelectorAll('.item-cb');
const selectAllCb = document.getElementById('selectAll');

function recalc(){
  let st=0, cnt=0;
  cbs.forEach(cb=>{if(cb.checked){let id=parseInt(cb.value);let it=items.find(i=>i.id===id);if(it){st+=it.price*it.qty;cnt++;}}});
  let df=<?=$isFreeDeliveryZone?1:0?>?0:(cnt===0?0:<?=$deliveryFee?>);
  let d=<?=$promo?$discount:0?>;
  if(cnt===0) df=0;
  let tot=Math.max(0,st+df-d);
  document.getElementById('selCount').textContent=cnt;
  document.getElementById('subtotalDisplay').textContent='P'+st.toFixed(2);
  document.getElementById('delFeeDisplay').textContent=df===0?'FREE':'P'+df.toFixed(2);
  document.getElementById('delFeeDisplay').className='font-bold '+(df===0?'text-green-600':'');
  document.getElementById('totalDisplay').textContent='P'+tot.toFixed(2);
  // Sync selectAll
  if(selectAllCb) selectAllCb.checked = cnt === cbs.length && cbs.length > 0;
}
function toggleAll(el){
  cbs.forEach(cb=>cb.checked=el.checked);
  recalc();
}
// Initial state
if(selectAllCb) selectAllCb.checked = <?=$allSelected?'true':'false'?>;
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
</body>
</html>
