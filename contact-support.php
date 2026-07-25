<?php
session_start();
require 'config.php';
require __DIR__ . '/includes/restrict-customer.php';

$feedbackMessage = "";
$feedbackMessageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $comments = trim($_POST['feedback_message'] ?? '');
    $rating = $_POST['rating'] ?? '';

    if (empty($rating) || !ctype_digit((string)$rating) || $rating < 1 || $rating > 5) {

        $feedbackMessage = "Please choose a star rating before submitting.";
        $feedbackMessageType = "error";

    } elseif (empty($name) || empty($email)) {

        $feedbackMessage = "Please fill in your name and email address.";
        $feedbackMessageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $feedbackMessage = "Please enter a valid email address.";
        $feedbackMessageType = "error";

    } else {

        // Logged-in users get their feedback linked to their account;
        // guests are still accepted and stored using the name/email
        // they typed into the form.
        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $conn->prepare("
            INSERT INTO feedback (user_id, guest_name, guest_email, subject, rating, comments)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $userId,
            $userId ? null : $name,
            $userId ? null : $email,
            $subject !== '' ? $subject : null,
            (int)$rating,
            $comments !== '' ? $comments : null
        ]);

        if ($success) {
            header("Location: contact-support.php?feedback_sent=1");
            exit();
        } else {
            $feedbackMessage = "Something went wrong submitting your feedback. Please try again.";
            $feedbackMessageType = "error";
        }

    }

}

if (isset($_GET['feedback_sent'])) {
    $feedbackMessage = "Thank you! Your feedback was submitted successfully.";
    $feedbackMessageType = "success";
}

$prefillName = isset($_SESSION['user_id'])
    ? trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name'])
    : ($_POST['full_name'] ?? '');
$prefillEmail = isset($_SESSION['user_id'])
    ? $_SESSION['email']
    : ($_POST['email'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Support | WoodCraft Care</title>
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
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Page Content -->
  <main class="flex-1 max-w-5xl w-full mx-auto px-6 py-14">

    <!-- Heading -->
    <div class="text-center mb-10">
      <h1 class="font-serif text-4xl font-semibold text-gray-900 mb-2">Contact Support</h1>
      <p class="text-gray-500">We're here to help. Choose the best way to reach us.</p>
    </div>

    <!-- Contact Methods Card -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">

  <!-- Heading -->
  <div class="pt-4 pb-3">
    <p class="text-center text-[10px] font-semibold tracking-[0.18em] uppercase text-[#B5702E]">
      Ways to Get in Touch
    </p>
  </div>

  <div class="grid md:grid-cols-3">

    <!-- ================= CALL US ================= -->
    <div class="border-r border-gray-200 px-7 py-8 flex">

      <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">

        <!-- Icon -->
        <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-[#B5702E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
        </div>

        <div class="flex flex-col min-h-[255px]">

          <div>
            <h3 class="font-semibold text-gray-900 text-lg">
              Call Us
            </h3>

            <p class="text-sm text-gray-500 mt-1 leading-6">
              Speak with our support specialist in real-time.
            </p>

            <p class="font-semibold text-[#B5702E] mt-8">
              +63 (925) 234-5678
            </p>

            <p class="text-xs text-gray-500 leading-5 mt-4">
              Mon – Fri, 9:00 AM – 6:00 PM<br>
              (Sat, 9:00 AM – 3:00 PM)
            </p>
          </div>

          <a href="tel:+19252345678"
             class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#B5702E] text-sm font-medium text-[#6B4226] hover:bg-gray-50 transition-colors">
            Call Now
          </a>

        </div>

      </div>

    </div>

    <!-- ================= EMAIL ================= -->
    <div class="border-r border-gray-200 px-7 py-8 flex">

      <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">

        <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-[#B5702E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
        </div>

        <div class="flex flex-col min-h-[255px]">

          <div>

            <h3 class="font-semibold text-gray-900 text-lg">
              Email Us
            </h3>

            <p class="text-sm text-gray-500 mt-1 leading-6">
              Send us an email and we'll respond within 24 hours.
            </p>

            <p class="font-semibold text-[#6B4226] mt-8">
              support@woodcraft.com
            </p>

            <p class="text-xs text-gray-500 leading-5 mt-4">
              We typically reply within one business day.
            </p>

          </div>

          <a href="mailto:support@woodcraft.com"
             class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#B5702E] text-sm font-medium text-[#6B4226] hover:bg-gray-50 transition-colors">
            Send Email
          </a>

        </div>

      </div>

    </div>
    <!-- ================= LIVE CHAT ================= -->
    <div class="px-7 py-8 flex">

      <div class="grid grid-cols-[48px_1fr] gap-x-4 w-full">

        <!-- Icon -->
        <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center">
          <svg class="w-5 h-5 text-[#B5702E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
          </svg>
        </div>

        <div class="flex flex-col min-h-[255px]">

          <div>

            <h3 class="font-semibold text-gray-900 text-lg">
              Live Chat
            </h3>

            <p class="text-sm text-gray-500 mt-1 leading-6">
              Chat with our support agent in real-time.
            </p>

            <p class="font-semibold text-green-600 mt-8">
              Available
            </p>

            <!-- Added invisible line so this section has the same height -->
            <p class="text-xs text-gray-500 leading-5 mt-4">
              Mon – Sat, 9:00 AM – 6:00 PM<br>
              <span class="invisible">(Sat, 9:00 AM – 3:00 PM)</span>
            </p>

          </div>

          <a href="live-chat.php"
             class="inline-flex w-fit mt-auto px-7 py-2 rounded-md border border-[#B5702E] text-sm font-medium text-[#6B4226] hover:bg-gray-50 transition-colors">
            Start Chat
          </a>

        </div>

      </div>

    </div>

  </div>

</div>

    <!-- Feedback + Topics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

      <!-- Feedback Form -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-7">
        <h3 class="font-semibold text-gray-900 mb-1">Send Us Your Feedback</h3>
        <p class="text-sm text-gray-500 mb-5">We value your opinion. Help us improve by sharing your feedback.</p>

        <?php if ($feedbackMessage): ?>
          <div class="mb-4 rounded-xl px-4 py-3 text-sm <?= $feedbackMessageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
            <?= htmlspecialchars($feedbackMessage) ?>
          </div>
        <?php endif; ?>

        <form class="space-y-4" method="POST" id="feedbackForm">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1.5">Full Name</label>
              <input type="text" name="full_name" required placeholder="Enter your full name"
                     value="<?= htmlspecialchars($prefillName) ?>"
                     <?= isset($_SESSION['user_id']) ? 'readonly' : '' ?>
                     class="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors <?= isset($_SESSION['user_id']) ? 'bg-gray-50 text-gray-500' : '' ?>" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1.5">Email Address</label>
              <input type="email" name="email" required placeholder="Enter your email address"
                     value="<?= htmlspecialchars($prefillEmail) ?>"
                     <?= isset($_SESSION['user_id']) ? 'readonly' : '' ?>
                     class="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors <?= isset($_SESSION['user_id']) ? 'bg-gray-50 text-gray-500' : '' ?>" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Subject</label>
            <select name="subject" class="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm text-gray-500 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors">
              <option value="">Select a subject</option>
              <option>General Feedback</option>
              <option>Website Feedback</option>
              <option>Other</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Your Feedback</label>
            <textarea rows="4" name="feedback_message" placeholder="Share your thoughts..." class="w-full rounded-lg border border-gray-200 px-3.5 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/30 focus:border-[#6B4226] transition-colors"></textarea>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Rate Your Experience</label>
            <div id="starRating" class="flex gap-1.5">
              <button type="button" data-star="1" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
              <button type="button" data-star="2" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
              <button type="button" data-star="3" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
              <button type="button" data-star="4" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
              <button type="button" data-star="5" class="star-btn text-2xl text-gray-300 hover:text-amber-400 transition-colors">★</button>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="" />
          </div>

          <button type="submit" name="submit_feedback" value="1" class="px-6 py-3 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">Submit Feedback</button>
        </form>
      </div>

      <!-- Common Support Topics -->
      <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-7">
        <h3 class="font-semibold text-gray-900 mb-1">Common Support Topics</h3>
        <p class="text-sm text-gray-500 mb-5">Find help with our most common services.</p>

        <div class="divide-y divide-gray-100">
          <a href="submit-ticket.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Submit a Support Ticket</p>
              <p class="text-xs text-gray-400">Report an issue or ask a question</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="warranty-request.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Warranty Request</p>
              <p class="text-xs text-gray-400">Request repair or warranty service</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="returns-refund.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8h-1"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Return &amp; Refund Request</p>
              <p class="text-xs text-gray-400">Request a return or refund</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="login.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Track Existing Requests</p>
              <p class="text-xs text-gray-400">Check the status of your requests</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="articles/article-assemble-bed-frame.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Product Assembly Help</p>
              <p class="text-xs text-gray-400">Get help with assembly instructions</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="faq.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-pink-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-3.5 5H7.5L4 13m16 0H4"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Delivery &amp; Shipping</p>
              <p class="text-xs text-gray-400">Questions about your delivery</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>

          <a href="articles/article-clean-oak-table.php" class="flex items-center gap-4 py-3.5 group">
            <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c1.5 2 2 4 1 6-1 2-3 2-3 4 0 1.5 1 2 1 2m-6-5c0 4.97 4.03 9 9 9s9-4.03 9-9"/></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Product Care &amp; Maintenance</p>
              <p class="text-xs text-gray-400">Learn how to care for your furniture</p>
            </div>
            <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 flex-shrink-0 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Support Hours -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-7 grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
      <div class="flex items-start gap-4">
        <div class="w-11 h-11 rounded-full bg-orange-50 flex items-center justify-center flex-shrink-0">
          <svg class="w-5 h-5 text-[#B5702E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <h3 class="font-semibold text-gray-900 mb-2">Support Hours</h3>
          <div class="text-sm text-gray-600 space-y-1">
            <div class="flex justify-between gap-6"><span>Mon - Fri</span><span class="text-gray-400">9:00 AM - 6:00 PM</span></div>
            <div class="flex justify-between gap-6"><span>Saturday</span><span class="text-gray-400">9:00 AM - 3:00 PM</span></div>
            <div class="flex justify-between gap-6"><span>Sunday &amp; Holidays</span><span class="text-gray-400">Closed</span></div>
          </div>
        </div>
      </div>
      <img src="https://images.unsplash.com/photo-1615529182904-14819c35db37?q=80&w=800&auto=format&fit=crop" alt="Wooden dining table" class="w-full h-32 object-cover rounded-2xl" />
    </div>

  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    const starBtns = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('ratingInput');
    let selectedRating = 0;
    starBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        selectedRating = parseInt(btn.dataset.star, 10);
        ratingInput.value = selectedRating;
        starBtns.forEach(b => {
          const val = parseInt(b.dataset.star, 10);
          b.classList.toggle('text-amber-400', val <= selectedRating);
          b.classList.toggle('text-gray-300', val > selectedRating);
        });
      });
    });

    const feedbackForm = document.getElementById('feedbackForm');
    if (feedbackForm) {
      feedbackForm.addEventListener('submit', (e) => {
        if (!ratingInput.value) {
          e.preventDefault();
          alert('Please choose a star rating before submitting.');
        }
      });
    }
  </script>

</body>
</html>