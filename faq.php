<?php
session_start();
require 'config.php';
// No login required for FAQ page - public facing

// ---------------------------------------------------------------
// Load FAQs from the database or use defaults
// ---------------------------------------------------------------
$faqs = [];
try {
    // If you have a database table for FAQs
    // $faqs = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // For demo: Use default FAQs
    $faqs = [
        ['question' => 'How fresh is the lettuce?', 'answer' => 'Our lettuce is harvested daily every morning and delivered within 24 hours. We guarantee freshness for 5-7 days when properly refrigerated at 2-4°C.', 'category' => 'Care'],
        ['question' => 'What lettuce varieties do you offer?', 'answer' => 'We offer Romaine, Iceberg, Butterhead, Green Leaf, Red Leaf, Oakleaf, Lollo Rosso, Baby Lettuce Mix, Family Salad Packs, and more. All are hydroponically grown and pesticide-free.', 'category' => 'General'],
        ['question' => 'How do I place an order?', 'answer' => 'Browse our products, add items to your cart, review your cart, enter delivery details, choose your payment method, and confirm your order. You\'ll receive a confirmation email with your order number.', 'category' => 'Orders'],
        ['question' => 'How long does delivery take?', 'answer' => 'Standard delivery takes 1-2 days. Express delivery is available within 4 hours. Same-day delivery is available for orders placed before 10 AM.', 'category' => 'Shipping'],
        ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Cash on Delivery (COD), GCash, Maya, and Bank Transfer. All payment methods are secure and encrypted.', 'category' => 'Orders'],
        ['question' => 'Do you offer free delivery?', 'answer' => 'Yes! We offer free delivery on orders ₱1,000 and above.', 'category' => 'Shipping'],
        ['question' => 'How should I store my lettuce?', 'answer' => 'Store in the refrigerator at 2-4°C in the crisper drawer. Don\'t wash until ready to use. Wrap in a paper towel to absorb moisture. Keep away from fruits that produce ethylene.', 'category' => 'Care'],
        ['question' => 'What is your return policy?', 'answer' => 'We offer a 7-day return policy. If your lettuce arrived wilted, damaged, or you received the wrong product, request a return within 7 days of delivery.', 'category' => 'Returns'],
        ['question' => 'Do you offer wholesale pricing?', 'answer' => 'Yes! We offer competitive wholesale pricing for restaurants, grocery stores, hotels, and resellers. Minimum wholesale order is ₱5,000.', 'category' => 'General'],
        ['question' => 'Is your lettuce organic?', 'answer' => 'We offer both organic and conventional options. Our organic range is grown without pesticides or chemicals and is certified organic.', 'category' => 'General'],
        ['question' => 'Can I schedule a delivery for a specific date?', 'answer' => 'Yes! You can select your preferred delivery date during checkout. We recommend choosing a date at least 1 day in advance.', 'category' => 'Shipping'],
        ['question' => 'Do you have subscription boxes?', 'answer' => 'Yes! We offer weekly and bi-weekly subscription boxes. Save 15% compared to regular orders with flexible delivery schedules.', 'category' => 'Orders'],
    ];
} catch (Exception $e) {
    $faqs = [];
}

// Map categories to filter pills
$faqCategoryMap = [
    'General'  => 'general',
    'Orders'   => 'orders',
    'Shipping' => 'shipping',
    'Returns'  => 'returns',
    'Care'     => 'care',
    'Warranty' => 'general',
    'Account'  => 'account',
];

function faqPillCategory(string $category, array $faqCategoryMap): string
{
    return $faqCategoryMap[$category] ?? 'general';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ | Fresh Lettuce Farm</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .gradient-primary { background: linear-gradient(135deg, #16a34a, #15803d); }
    details summary::-webkit-details-marker { display: none; }
    details summary { list-style: none; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Page Header -->
  <section class="gradient-primary text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-3xl md:text-4xl font-bold">Frequently Asked Questions</h1>
      <p class="text-green-100 mt-2">Find answers to the most common questions about our fresh lettuce</p>
    </div>
  </section>

  <!-- FAQ Content -->
  <main class="flex-1 max-w-4xl w-full mx-auto px-6 py-14">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

      <div class="px-8 pt-6 pb-2">
        <!-- Search -->
        <div class="relative mb-5">
          <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
          <input id="faqSearch" type="text" placeholder="Search FAQs..." class="w-full rounded-xl bg-gray-50 border border-gray-200 pl-11 pr-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:border-primary-600 transition-colors" />
        </div>

        <!-- Category Pills -->
        <div id="faqPills" class="flex flex-wrap gap-2 pb-5 border-b border-gray-100">
          <button data-cat="all" class="faq-pill text-sm font-medium rounded-full px-4 py-2 bg-primary-600 text-white transition-colors">All</button>
          <button data-cat="general" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">General</button>
          <button data-cat="orders" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Orders</button>
          <button data-cat="shipping" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Delivery</button>
          <button data-cat="returns" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Returns</button>
          <button data-cat="care" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Care</button>
          <button data-cat="account" class="faq-pill text-sm font-medium rounded-full px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">Account</button>
        </div>
      </div>

      <!-- Accordion -->
      <div id="faqList" class="px-8 pb-6">

        <?php if (empty($faqs)): ?>
          <p class="text-sm text-gray-400 text-center py-10">No FAQs are currently available. Please check back later.</p>
        <?php else: ?>
          <?php foreach ($faqs as $i => $f): ?>
            <details data-cat="<?= htmlspecialchars(faqPillCategory($f['category'], $faqCategoryMap)) ?>" class="group faq-item <?= $i < count($faqs) - 1 ? 'border-b border-gray-100' : '' ?> py-4">
              <summary class="flex items-center justify-between cursor-pointer">
                <span class="font-medium text-gray-900"><?= htmlspecialchars($f['question']) ?></span>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
              </summary>
              <p class="mt-3 text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($f['answer'])) ?></p>
            </details>
          <?php endforeach; ?>
        <?php endif; ?>

        <p id="faqEmpty" class="hidden text-sm text-gray-400 text-center py-10">No questions match your search. Try a different keyword or category.</p>
      </div>
    </div>

    <!-- Still Have Questions -->
    <div class="mt-8 bg-primary-50 rounded-2xl p-8 text-center border border-primary-200">
      <h3 class="text-lg font-bold text-gray-900 mb-2">Still have questions?</h3>
      <p class="text-gray-600 text-sm mb-4">We're here to help! Contact us anytime.</p>
      <div class="flex flex-wrap justify-center gap-3">
        <a href="contact-us.html" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm font-medium">
          <i class="fas fa-envelope mr-2"></i> Contact Us
        </a>
        <a href="live-chat.html" class="px-6 py-2.5 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition text-sm font-medium">
          <i class="fas fa-comment mr-2"></i> Live Chat
        </a>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const pills = document.querySelectorAll('.faq-pill');
    const items = document.querySelectorAll('.faq-item');
    const search = document.getElementById('faqSearch');
    const empty = document.getElementById('faqEmpty');
    let activeCat = 'all';

    function applyFilters() {
      const term = search.value.trim().toLowerCase();
      let visibleCount = 0;
      items.forEach(item => {
        const matchesCat = activeCat === 'all' || item.dataset.cat === activeCat;
        const text = item.textContent.toLowerCase();
        const matchesSearch = term === '' || text.includes(term);
        const show = matchesCat && matchesSearch;
        item.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });
      empty.classList.toggle('hidden', visibleCount !== 0);
    }

    pills.forEach(pill => {
      pill.addEventListener('click', () => {
        pills.forEach(p => {
          p.classList.remove('bg-primary-600', 'text-white');
          p.classList.add('border', 'border-gray-200', 'text-gray-600');
        });
        pill.classList.add('bg-primary-600', 'text-white');
        pill.classList.remove('border', 'border-gray-200', 'text-gray-600');
        activeCat = pill.dataset.cat;
        applyFilters();
      });
    });

    search.addEventListener('input', applyFilters);
  </script>

</body>
</html>