<?php
session_start();
require __DIR__ . '/../config.php';

// This page lives one folder below the project root (/articles),
// so header.php/footer.php need '../' prepended to every link.
$baseUrl = '../';
require __DIR__ . '/../includes/restrict-customer.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cleaning Your Oak Table | WoodCraft Care</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .font-serif { font-family: 'Fraunces', serif; }
  </style>
</head>
<body class="bg-[#F3F0E4] text-gray-900 min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/../includes/header.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <a href="../index.php" class="inline-flex items-center gap-2 text-sm text-[#6B4226] hover:text-[#59341C] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to Home
    </a>
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#B5702E] bg-orange-50 rounded-full px-3 py-1 mb-5">FURNITURECARE</span>
      <h1 class="font-serif text-3xl font-semibold text-gray-900 mb-4">How to clean and protect your oak dining table</h1>
      <div class="text-gray-600 text-[15px] leading-relaxed space-y-4">
        <p>Oak furniture is durable, but a little regular care keeps it looking its best for generations. Start by dusting with a soft, dry microfiber cloth to remove surface debris before it scratches the finish.</p>
        <p>For deeper cleaning, use a barely damp cloth with a mild wood-safe cleaner, then dry immediately. Avoid harsh chemicals, and reapply a food-safe wood conditioner every few months to keep the grain protected.</p>
      </div>
      
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
