<?php
session_start();
require 'config.php';

$faqs = [];
try {
    $faqs = [
        ['question' => 'How fresh is the lettuce?', 'answer' => 'Our lettuce is harvested only after you order -- it stays growing in our hydroponic system until your order is confirmed. We harvest, quality-check, pack, and deliver all on the same day. When properly refrigerated at 2-4C, whole heads stay fresh for 5-7 days.', 'category' => 'Freshness'],
        ['question' => 'What lettuce varieties do you offer?', 'answer' => 'We offer Romaine, Batavia, Bianca, Dabi, Red Lettuce, Estrosa, Olmetie, Mixed Greens, Garden Salad Mix, and various bundles and wholesale options. All are hydroponically grown and chemical-free.', 'category' => 'Products'],
        ['question' => 'How do I place an order?', 'answer' => 'Browse our products, add items to your cart, review your cart, enter delivery details, choose your payment method, and confirm your order. You will receive a confirmation with your order number.', 'category' => 'Orders'],
        ['question' => 'What is harvest-on-demand?', 'answer' => 'Unlike supermarkets where lettuce sits on shelves for days, our harvest-on-demand model means lettuce stays growing in our hydroponic system until you order. We only harvest after your order confirmation -- usually within 1-3 hours before delivery or pick-up. This gives you unmatched freshness and reduces food waste.', 'category' => 'Orders'],
        ['question' => 'How does delivery work?', 'answer' => 'We offer same-day delivery and same-day pick-up. Delivery is FREE within Nostalji Subdivision, Paliparan I, Dasmarinas, Cavite. For locations outside the subdivision, a delivery fee is automatically calculated. Same-day delivery for orders placed before 2 PM.', 'category' => 'Delivery'],
        ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Cash on Delivery (COD), GCash, Maya, and Bank Transfer. All payment methods are secure.', 'category' => 'Orders'],
        ['question' => 'How should I store my lettuce?', 'answer' => 'Refrigerate immediately at 2-4C. Do not wash until ready to use. Keep inside a sealed container in the crisper drawer. Store away from ethylene-producing fruits like apples and bananas. Whole heads last 5-7 days refrigerated.', 'category' => 'Care'],
        ['question' => 'What is your return policy?', 'answer' => 'If your lettuce arrived wilted, damaged, or you received the wrong product, request a return within 24 hours of delivery. We will replace it at no cost.', 'category' => 'Returns'],
        ['question' => 'Do you offer wholesale pricing?', 'answer' => 'Yes! We offer competitive wholesale pricing for restaurants, grocery stores, hotels, and resellers. We have Wholesale Tray (20 cups) and Wholesale Box (50 cups) options available.', 'category' => 'Products'],
        ['question' => 'What is hydroponic farming?', 'answer' => 'Hydroponics is a method of growing plants without soil, using nutrient-rich water instead. Our lettuce is grown in controlled systems with no pesticides or chemicals -- just pure water, nutrients, and sunlight for consistently clean, crisp produce.', 'category' => 'Products'],
        ['question' => 'How do I create an account?', 'answer' => 'Go to the Register page. Enter your first name, last name, email address, phone number, address, and a password. Confirm your password and submit the form to create your account.', 'category' => 'Account'],
        ['question' => 'How do I log in?', 'answer' => 'Go to the Login page. Enter your email address and password, then click Sign In. If you forgot your password, click the "Forgot Password?" link to reset it.', 'category' => 'Account'],
        ['question' => 'How do I reset my password?', 'answer' => 'On the Login page, click "Forgot Password?" below the password field. Enter your email address and click "Send Reset Link." Follow the instructions in the email to create a new password.', 'category' => 'Account'],
        ['question' => 'How do I change my password?', 'answer' => 'Log into your account, go to your Customer Dashboard, click "Show Details" then "Change Password." Enter your current password, new password, confirm it, and save.', 'category' => 'Account'],
        ['question' => 'How do I update my profile?', 'answer' => 'Log into your account, go to your Customer Dashboard, click "Show Details" then "Edit Profile." Update your information and save changes.', 'category' => 'Account'],
        ['question' => 'How do I track my order?', 'answer' => 'Log into your account, go to Order Tracking, and enter your order number. You will see your order progress through each stage: Order Received, Payment Confirmed, Harvest Queue, Harvesting, Quality Inspection, Packing, and Delivery or Pick-Up.', 'category' => 'Orders'],
    ];
} catch (Exception $e) { $faqs = []; }

$faqCategoryMap = [
    'Products'  => 'products', 'Orders' => 'orders', 'Delivery' => 'delivery',
    'Returns'   => 'returns', 'Care' => 'care', 'Freshness' => 'freshness', 'Account' => 'account',
];
function faqPillCategory(string $c, array $m): string { return $m[$c] ?? 'products'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Nunito',sans-serif}
    details summary::-webkit-details-marker{display:none}
    details summary{list-style:none}
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

<?php include __DIR__ . '/includes/header.php'; ?>

<section class="bg-[#17611f] text-white py-14">
  <div class="max-w-4xl mx-auto px-6">
    <h1 class="text-2xl sm:text-3xl font-black">Frequently Asked Questions</h1>
    <p class="text-[#c8e6c9] mt-2 text-sm">Find answers about our hydroponic lettuce, orders, delivery, and more</p>
  </div>
</section>

<main class="flex-1 max-w-3xl w-full mx-auto px-6 py-10">
  <div class="bg-white rounded-2xl border border-[rgba(27,94,32,0.08)] overflow-hidden">
    <div class="px-6 pt-5 pb-2">
      <div class="relative mb-4">
        <svg class="w-4 h-4 text-[#9e9e9e] absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input id="faqSearch" type="text" placeholder="Search FAQs..." class="w-full rounded-xl bg-[#f4faf5] border border-[rgba(27,94,32,0.12)] pl-11 pr-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#52b788]/40" />
      </div>
      <div id="faqPills" class="flex flex-wrap gap-2 pb-4 border-b border-[rgba(27,94,32,0.08)]">
        <button data-cat="all" class="faq-pill text-sm font-bold rounded-full px-4 py-2 bg-[#17611f] text-white transition-colors">All</button>
        <button data-cat="products" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Products</button>
        <button data-cat="orders" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Orders</button>
        <button data-cat="delivery" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Delivery</button>
        <button data-cat="returns" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Returns</button>
        <button data-cat="care" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Care</button>
        <button data-cat="account" class="faq-pill text-sm font-bold rounded-full px-4 py-2 border border-[rgba(27,94,32,0.12)] text-[#5a7a5c] hover:bg-[#e8f5e9] transition-colors">Account</button>
      </div>
    </div>

    <div id="faqList" class="px-6 pb-6">
      <?php if (empty($faqs)): ?>
        <p class="text-sm text-[#9e9e9e] text-center py-10">No FAQs available.</p>
      <?php else: ?>
        <?php foreach ($faqs as $i => $f): ?>
          <details data-cat="<?= htmlspecialchars(faqPillCategory($f['category'], $faqCategoryMap)) ?>" class="group faq-item <?= $i < count($faqs)-1 ? 'border-b border-[rgba(27,94,32,0.08)]' : '' ?> py-4">
            <summary class="flex items-center justify-between cursor-pointer">
              <span class="font-bold text-sm text-[#1a2e1c]"><?= htmlspecialchars($f['question']) ?></span>
              <svg class="w-4 h-4 text-[#9e9e9e] flex-shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <p class="mt-3 text-sm text-[#5a7a5c] leading-relaxed"><?= nl2br(htmlspecialchars($f['answer'])) ?></p>
          </details>
        <?php endforeach; ?>
      <?php endif; ?>
      <p id="faqEmpty" class="hidden text-sm text-[#9e9e9e] text-center py-10">No questions match your search. Try a different keyword or category.</p>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const pills=document.querySelectorAll('.faq-pill'),items=document.querySelectorAll('.faq-item'),search=document.getElementById('faqSearch'),empty=document.getElementById('faqEmpty');let activeCat='all';
function applyFilters(){const t=search.value.trim().toLowerCase();let v=0;items.forEach(i=>{const m=activeCat==='all'||i.dataset.cat===activeCat,s=t===''||i.textContent.toLowerCase().includes(t),sh=m&&s;i.style.display=sh?'':'none';if(sh)v++});empty.classList.toggle('hidden',v!==0)}
pills.forEach(p=>{p.addEventListener('click',()=>{pills.forEach(x=>{x.classList.remove('bg-[#17611f]','text-white');x.classList.add('border','border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]')});p.classList.add('bg-[#17611f]','text-white');p.classList.remove('border','border-[rgba(27,94,32,0.12)]','text-[#5a7a5c]');activeCat=p.dataset.cat;applyFilters()})});
search.addEventListener('input',applyFilters);
</script>
</body>
</html>
