<?php
/**
 * includes/header.php
 * ------------------------------------------------------------------
 * Single, shared site header used by every customer-facing page.
 *
 * It always reflects the real PHP session state:
 *   - Logged in  -> shows the user's name (profile link) + Logout
 *   - Logged out -> shows Login + Register
 *
 * This replaces the old split between "nav-guest.php" (which never
 * checked the session at all) and "nav-session.php" (which assumed
 * the user was always logged in). Using one file for both states
 * means every page now shows the correct header automatically,
 * with no risk of the two versions drifting apart again.
 *
 * USAGE
 * -----
 * Before including this file, set $baseUrl to the relative path
 * back to the project root:
 *   - Pages in the project root:      $baseUrl = '';
 *   - Pages one folder deep (e.g.
 *     /articles/*.php):               $baseUrl = '../';
 *
 * If $baseUrl isn't set, it defaults to '' (root-level page).
 *
 * The including page must already have called session_start()
 * (normally via config.php) before this file is included, since it
 * reads $_SESSION directly.
 * ------------------------------------------------------------------
 * VISUAL NOTE: markup/classes below intentionally use literal hex
 * (arbitrary Tailwind values) rather than theme tokens, since this
 * header is shared by pages that each define their own <head> /
 * Tailwind config. That keeps the look consistent everywhere without
 * requiring every page's <head> to be touched.
 * ------------------------------------------------------------------
 */

if (!isset($baseUrl)) {
    $baseUrl = '';
}

// The floating chat launcher below appears on every page that includes
// this header — except live-chat.php itself, where the customer is
// already in the conversation and a second "chat with us" button would
// just be clutter.
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
  <!-- Top Announcement Bar -->
  <div class="bg-[#17611f] px-4 py-[7px] text-center text-xs font-bold text-white">
    🌿&nbsp; Free delivery for all orders above ₱500 &nbsp;|&nbsp; Fresh harvest <strong>Everyday</strong> &nbsp;|&nbsp; Luntiang H.A.P.A.G. · Health Awareness and Professional Advisory Group
  </div>

  <!-- Header -->
  <header class="bg-white border-b border-[rgba(27,94,32,0.12)] sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-[86px] flex items-center gap-4">
      <a href="<?= $baseUrl ?><?= isset($_SESSION['user_id']) ? 'my-profile.php' : 'index.php' ?>" class="flex shrink-0 items-center gap-3">
        <img src="<?= $baseUrl ?>images/lettuce/logo-cropped.png" alt="Luntiang H.A.P.A.G." class="h-14 w-14 object-contain">
        <span class="leading-tight">
          <span class="block text-lg font-black text-[#1a2e1c] tracking-tight">LUNTIANG</span>
          <span class="block text-sm font-black text-[#17611f] tracking-widest -mt-1">H.A.P.A.G.</span>
        </span>
      </a>
      <span class="hidden h-5 border-l border-[rgba(27,94,32,0.12)] lg:block"></span>
      <span class="hidden shrink-0 text-sm font-semibold text-[#5a7a5c] lg:block">100% Hydroponic Lettuce Farm</span>

      <?php if (!isset($_SESSION['user_id'])): ?>
      <nav class="hidden md:flex items-center gap-8 text-[15px] font-semibold text-[#1a2e1c] ml-auto mr-2">
        <a href="<?= $baseUrl ?>index.php" class="hover:text-[#17611f] transition-colors">Home</a>
        <a href="<?= $baseUrl ?>faq.php" class="hover:text-[#17611f] transition-colors">FAQ</a>
        <a href="<?= $baseUrl ?>contact-support.php" class="hover:text-[#17611f] transition-colors">Contact Support</a>
      </nav>
      <?php endif; ?>

      <div class="flex items-center gap-3 <?= isset($_SESSION['user_id']) ? 'ml-auto' : '' ?>">

        <?php if (isset($_SESSION['user_id'])): ?>

          <a href="<?= $baseUrl ?>my-profile.php"
             class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
            <?= htmlspecialchars($_SESSION['first_name']) ?>
          </a>
          <a href="<?= $baseUrl ?>logout.php"
             class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">
            Logout
          </a>

        <?php else: ?>

          <a href="<?= $baseUrl ?>login.php"
             class="hidden sm:inline-block px-5 py-2.5 rounded-2xl border border-[rgba(27,94,32,0.12)] text-sm font-bold text-[#1a2e1c] hover:bg-[#e8f5e9] transition-colors">
            Login
          </a>
          <a href="<?= $baseUrl ?>register.php"
             class="px-5 py-2.5 rounded-2xl bg-[#17611f] text-white text-sm font-bold hover:opacity-90 transition-opacity">
            Register
          </a>

        <?php endif; ?>

      </div>
    </div>
  </header>

  <!-- OLD CHAT WIDGET REMOVED - Now only in footer.php -->
