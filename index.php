<?php
session_start();
require 'config.php';
require __DIR__ . '/includes/restrict-customer.php';
require __DIR__ . '/includes/navigation.php';
$products = require __DIR__ . '/includes/lettuce-catalog.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Luntiang H.A.P.A.G. | Fresh Hydroponic Lettuce</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    html { scroll-behavior: smooth; }
    .product-image { transition: transform .3s ease; }
    .product-card:hover .product-image { transform: scale(1.05); }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c]">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- ============================================================ -->
  <!-- HERO SECTION                                                 -->
  <!-- ============================================================ -->
  <section class="mx-auto max-w-[1300px] px-[34px] py-6">
    <div class="relative h-[245px] overflow-hidden rounded-2xl">
      <img src="images/lettuce/hero-farm.png" class="absolute inset-0 h-full w-full object-cover object-center" alt="Luntiang Hapag Lettuce Farm">
      <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/55 to-black/10"></div>
      <div class="relative flex h-full flex-col justify-center px-6 py-7 text-white">
        <p class="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full bg-black/60 px-3 py-1 text-xs font-black">⟳&nbsp; 100% Hydroponic · Fresh Every Weekend</p>
        <h1 class="max-w-[500px] text-[30px] font-black leading-[1.25] tracking-[-.5px] drop-shadow">Come and Visit the<br>Luntiang Hapag Lettuce Farm</h1>
        <p class="mt-3 text-sm font-bold"><span class="mr-2 text-pink-500">●</span>Nostalji Subdivision Paliparan I, Dasmariñas, Cavite</p>
        <p class="mt-3 text-xs font-semibold text-white/75">Open <strong class="text-white">Everyday</strong> · Fresh harvest of 8 hydroponic varieties</p>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- PROMO CARDS                                                  -->
    <!-- ============================================================ -->
    <div class="my-8 grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl bg-[#fff4e5] p-5">
        <p class="text-xs font-black uppercase text-amber-600">Bundle Promo</p>
        <h3 class="mt-1 text-lg font-black">Weekend Bundle at ₱260</h3>
        <p class="mt-1 text-sm text-[#5a7a5c]">6 cups + dressing + wrap kit, best value for family meals.</p>
      </div>
      <div class="rounded-2xl bg-[#eaf3ff] p-5">
        <p class="text-xs font-black uppercase text-blue-600">Wholesale Deal</p>
        <h3 class="mt-1 text-lg font-black">Bulk Tray from ₱700</h3>
        <p class="mt-1 text-sm text-[#5a7a5c]">Perfect for resellers, canteens, and events — 20 or 50 cups per pack.</p>
      </div>
      <div class="rounded-2xl bg-[#eafaf0] p-5">
        <p class="text-xs font-black uppercase text-emerald-600">Fresh Every Weekend</p>
        <h3 class="mt-1 text-lg font-black">Harvested Same-Day</h3>
        <p class="mt-1 text-sm text-[#5a7a5c]">All cups are picked and packed the morning of delivery.</p>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- PRODUCT GRID                                                 -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
      <?php foreach ($products as $product): ?>
        <article class="product-card">
          <div class="relative block w-full overflow-hidden bg-white rounded-xl">
            <img src="<?= htmlspecialchars($product['image']) ?>" class="product-image aspect-square w-full object-cover" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php if (!empty($product['bestSeller'])): ?><b class="absolute left-2 top-2 rounded bg-amber-300 px-2 py-1 text-[10px]">Best Seller</b><?php endif; ?>
          </div>
          <p class="mt-3 block text-left text-sm font-bold"><?= htmlspecialchars($product['name']) ?></p>
          <p class="text-xs text-[#5a7a5c]"><?= htmlspecialchars($product['unit']) ?></p>
          <p class="mb-3 font-black">₱<?= number_format((float)$product['price'], 2) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- ============================================================ -->
    <!-- WHY CHOOSE US                                                -->
    <!-- ============================================================ -->
    <section class="my-12">
      <h2 class="mb-6 text-center text-2xl font-black">Why Choose Us</h2>
      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-[rgba(27,94,32,0.12)] bg-white p-5 text-center">
          <p class="mb-2 text-3xl">🌱</p>
          <h3 class="font-black">100% Hydroponic</h3>
          <p class="mt-1 text-sm text-[#5a7a5c]">Clean, soil-free growing for consistently crisp lettuce.</p>
        </div>
        <div class="rounded-2xl border border-[rgba(27,94,32,0.12)] bg-white p-5 text-center">
          <p class="mb-2 text-3xl">🚚</p>
          <h3 class="font-black">Fresh Every Weekend</h3>
          <p class="mt-1 text-sm text-[#5a7a5c]">Harvested and delivered the same day, never stored long.</p>
        </div>
        <div class="rounded-2xl border border-[rgba(27,94,32,0.12)] bg-white p-5 text-center">
          <p class="mb-2 text-3xl">💸</p>
          <h3 class="font-black">Farm-Direct Pricing</h3>
          <p class="mt-1 text-sm text-[#5a7a5c]">No middlemen markup — from our farm straight to you.</p>
        </div>
        <div class="rounded-2xl border border-[rgba(27,94,32,0.12)] bg-white p-5 text-center">
          <p class="mb-2 text-3xl">📦</p>
          <h3 class="font-black">Bundles & Wholesale</h3>
          <p class="mt-1 text-sm text-[#5a7a5c]">Options for households, resellers, and events alike.</p>
        </div>
      </div>
    </section>
  </section>

  <!-- Back to Top Button -->
  <?php renderBackToTop(); ?>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
