<?php
session_start();
require 'config.php';
require __DIR__ . '/includes/navigation.php';

// Fetch featured & best-selling products from DB
try {
    $featured = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY is_best_seller DESC, plants_available DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to static catalog if DB tables don't exist yet
    $featured = require __DIR__ . '/includes/lettuce-catalog.php';
    $featured = array_slice($featured, 0, 8);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Luntiang H.A.P.A.G. | Fresh Hydroponic Harvest-on-Demand Lettuce</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f4faf5; }
    html { scroll-behavior: smooth; }
    .product-card { transition: all .25s ease; }
    .product-card:hover { box-shadow: 0 8px 28px rgba(27,94,32,.1); transform: translateY(-3px); }
    .product-image { transition: transform .35s ease; }
    .product-card:hover .product-image { transform: scale(1.06); }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- ============================================================ -->
<!-- HERO                                                          -->
<!-- ============================================================ -->
<section class="max-w-7xl mx-auto px-6 py-6">
  <div class="relative h-[340px] sm:h-[380px] overflow-hidden rounded-2xl">
    <img src="images/lettuce/hero-farm.png" class="absolute inset-0 h-full w-full object-cover object-center" alt="Hydroponic Lettuce Farm">
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-black/10"></div>
    <div class="relative flex h-full flex-col justify-center px-6 sm:px-10 text-white">
      <span class="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-[#17611f]/85 px-3 py-1 text-xs font-black">100% Hydroponic · Harvest-on-Demand</span>
      <h1 class="max-w-[520px] text-[26px] sm:text-[32px] font-black leading-[1.2] tracking-[-.5px]">
        Harvested Only After You Order
      </h1>
      <p class="mt-3 max-w-[460px] text-sm sm:text-base text-white/90">
        Farm-to-table freshness — lettuce stays growing until your order is confirmed. Same-day harvest, pack, and delivery.
      </p>
      <div class="mt-5 flex flex-wrap gap-3">
        <a href="products.php" class="inline-flex items-center gap-2 rounded-xl bg-white text-[#17611f] px-5 py-2.5 text-sm font-black hover:bg-[#e8f5e9] transition-colors">🛍️ Shop Now</a>
        <a href="about.php" class="inline-flex items-center gap-2 rounded-xl bg-white/15 text-white px-5 py-2.5 text-sm font-bold hover:bg-white/25 transition-colors">Learn More</a>
      </div>
    </div>
  </div>

  <!-- Trust Strip -->
  <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5 py-4 text-xs sm:text-sm font-bold text-[#17611f]">
    <span></span><span class="text-[#c8e6c9]">|</span>
    <span>🌱 Hydroponic</span><span class="text-[#c8e6c9]">|</span>
    <span>🚚 Same-Day Delivery</span><span class="text-[#c8e6c9]">|</span>
    <span>🛍️ Pick-Up</span><span class="text-[#c8e6c9]">|</span>
    
  </div>

  <!-- Promo Cards -->
  <div class="grid gap-4 sm:grid-cols-3 mb-10">
    <a href="products.php?filter=best_seller" class="rounded-2xl bg-[#fff8e1] border border-[#ffecb3] p-5 hover:shadow-md transition-all">
      <p class="text-xs font-black uppercase text-amber-700">Best Seller</p>
      <h3 class="mt-1 text-lg font-black">Weekend Bundle · ₱260</h3>
      <p class="mt-1 text-sm text-[#5a7a5c]">6 cups + dressing + wrap kit</p>
    </a>
    <a href="products.php?category=wholesale" class="rounded-2xl bg-[#e8f5e9] border border-[#c8e6c9] p-5 hover:shadow-md transition-all">
      <p class="text-xs font-black uppercase text-[#17611f]">📦 Wholesale</p>
      <h3 class="mt-1 text-lg font-black">Bulk Trays from ₱700</h3>
      <p class="mt-1 text-sm text-[#5a7a5c]">Restaurants, canteens & events</p>
    </a>
    <a href="about.php" class="rounded-2xl bg-[#e3f2fd] border border-[#bbdefb] p-5 hover:shadow-md transition-all">
      <p class="text-xs font-black uppercase text-blue-700">⟳ Why Harvest-on-Demand?</p>
      <h3 class="mt-1 text-lg font-black">Fresher Than Supermarkets</h3>
      <p class="mt-1 text-sm text-[#5a7a5c]">Never pre-cut. Never stored. Zero waste.</p>
    </a>
  </div>

  <!-- Featured Products -->
  <section>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-black">Fresh Lettuce & Bundles</h2>
        <p class="text-sm text-[#5a7a5c] mt-1">All hydroponically grown — harvested only after you order</p>
      </div>
      <a href="products.php" class="text-sm font-bold text-[#17611f] hover:underline">View All →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
      <?php foreach (array_slice($featured, 0, 8) as $p): 
        $pid = $p['id'] ?? 0;
        $pslug = $p['slug'] ?? '';
        $pimg = $p['image'] ?? 'images/lettuce/hero-farm.png';
        $pname = $p['name'] ?? '';
        $pvariety = $p['variety'] ?? $p['unit'] ?? '';
        $pprice = (float)($p['price'] ?? 0);
        $pbest = $p['is_best_seller'] ?? $p['bestSeller'] ?? false;
        $pavail = $p['plants_available'] ?? 999;
      ?>
        <article class="product-card bg-white rounded-xl overflow-hidden border border-[rgba(27,94,32,0.08)]">
          <a href="product.php?slug=<?= urlencode($pslug) ?>" class="block relative overflow-hidden">
            <img src="<?= htmlspecialchars($pimg) ?>" class="product-image aspect-square w-full object-cover" alt="<?= htmlspecialchars($pname) ?>">
            <?php if ($pbest): ?><b class="absolute left-2 top-2 rounded bg-[#f9a825] px-2 py-1 text-[10px] font-black text-white">🏆 Best</b><?php endif; ?>
            
          </a>
          <div class="p-3">
            <a href="product.php?slug=<?= urlencode($pslug) ?>" class="block">
              <p class="text-sm font-bold hover:text-[#17611f] transition-colors line-clamp-1"><?= htmlspecialchars($pname) ?></p>
            </a>
            <p class="text-xs text-[#5a7a5c] truncate"><?= htmlspecialchars($pvariety) ?></p>
            <div class="flex items-center justify-between mt-2">
              <p class="font-black text-[#17611f]">₱<?= number_format($pprice, 2) ?></p>
            </div>
            <a href="cart-actions.php?action=add&id=<?= $pid ?>" class="block mt-2 text-center text-xs font-bold py-1.5 rounded-lg bg-[#17611f] text-white hover:bg-[#14521a] transition-colors">🛒 Add to Cart</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>

<!-- How It Works -->
<section class="bg-white border-y border-[rgba(27,94,32,0.06)] py-10 mt-10">
  <div class="max-w-7xl mx-auto px-6 text-center">
    <h2 class="text-2xl font-black mb-8">How Harvest-on-Demand Works</h2>
    <div class="flex flex-wrap items-start justify-center gap-2 lg:gap-3">
      <?php foreach ([['🛒','You Order','Browse & place order'],['✅','Confirmed','Payment verified'],['✂️','Harvest','Cut within 1–3 hrs'],['📦','Pack','Freshly packed'],['🏠','Deliver','Same-day']] as $step): ?>
        <div class="bg-[#f4faf5] rounded-xl p-4 text-center w-[100px] sm:w-[120px]">
          <div class="w-10 h-10 rounded-full bg-[#e8f5e9] flex items-center justify-center mx-auto mb-2 text-xl"><?= $step[0] ?></div>
          <p class="font-black text-xs"><?= $step[1] ?></p>
          <p class="text-[10px] text-[#5a7a5c] mt-0.5"><?= $step[2] ?></p>
        </div>
        <?php if ($step[0] !== '🏠'): ?><span class="self-center text-[#c8e6c9] text-xl font-black">→</span><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- About Snippet -->
<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">🌿 About Our Farm</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">Luntiang H.A.P.A.G. grows 8 hydroponic lettuce varieties in Nostalji Subdivision, Dasmariñas, Cavite. Chemical-free, soil-free, harvested on demand.</p>
      <a href="about.php" class="text-sm font-bold text-[#17611f] hover:underline">Learn more →</a>
    </div>
    <div class="bg-white rounded-xl border border-[rgba(27,94,32,0.08)] p-6">
      <h3 class="font-black text-lg mb-2">✅ Freshness Guarantee</h3>
      <p class="text-sm text-[#5a7a5c] leading-relaxed mb-3">Every order is harvested fresh. If your lettuce arrives wilted, damaged, or not right — we replace it free. Just let us know within 24 hours.</p>
      <a href="contact-support.php" class="text-sm font-bold text-[#17611f] hover:underline">Contact Support →</a>
    </div>
  </div>
</section>

<?php renderBackToTop(); ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
