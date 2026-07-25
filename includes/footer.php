<?php
/**
 * includes/footer.php
 * ------------------------------------------------------------------
 * Shared site footer used by every customer-facing page.
 *
 * Same $baseUrl convention as includes/header.php:
 *   - Pages in the project root:      $baseUrl = '';
 *   - Pages one folder deep (e.g.
 *     /articles/*.php):               $baseUrl = '../';
 * ------------------------------------------------------------------
 */

if (!isset($baseUrl)) {
    $baseUrl = '';
}

// Logged-in customers only get my-profile.php and its related
// customer features — no footer while a customer session is active.
if (isset($_SESSION['user_id'])) {
    return;
}
?>
  <footer class="bg-white border-t border-[rgba(27,94,32,0.12)]">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
      <div class="md:col-span-2">
        <a href="<?= $baseUrl ?>index.php" class="flex items-center gap-3 mb-4">
          <img src="<?= $baseUrl ?>images/lettuce/logo-cropped.png" alt="Luntiang H.A.P.A.G." class="h-10 w-10 object-contain">
          <span class="leading-tight">
            <span class="block text-sm font-black text-[#1a2e1c] tracking-tight">LUNTIANG</span>
            <span class="block text-xs font-black text-[#17611f] tracking-widest -mt-0.5">H.A.P.A.G.</span>
          </span>
        </a>
        <p class="text-sm text-[#5a7a5c] leading-relaxed max-w-xs">Premium customer support from Luntiang H.A.P.A.G. We're here to help you enjoy our products and services for years to come.</p>
      </div>

      <div class="md:justify-self-end">
        <h4 class="font-black text-lg text-[#1a2e1c] mb-4">Help</h4>
        <ul class="space-y-3 text-sm text-[#5a7a5c]">
          <li><a href="<?= $baseUrl ?>faq.php" class="hover:text-[#17611f] transition-colors">FAQ</a></li>
          <li><a href="<?= $baseUrl ?>contact-support.php" class="hover:text-[#17611f] transition-colors">Contact Support</a></li>
        </ul>
      </div>
      <div class="md:justify-self-end">
        <h4 class="font-black text-lg text-[#1a2e1c] mb-4">Company</h4>
        <ul class="space-y-3 text-sm text-[#5a7a5c]">
          <li><a href="<?= $baseUrl ?>about.php" class="hover:text-[#17611f] transition-colors">About Us</a></li>
          <li><a href="<?= $baseUrl ?>privacy.php" class="hover:text-[#17611f] transition-colors">Privacy Policy</a></li>
          <li><a href="<?= $baseUrl ?>terms.php" class="hover:text-[#17611f] transition-colors">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-[rgba(27,94,32,0.12)]">
      <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-xs text-[#5a7a5c]">© 2006 Luntiang H.A.P.A.G. All rights reserved.</p>
        <?php if (basename($_SERVER['SCRIPT_NAME']) === 'index.php'): ?>
          <a href="<?= $baseUrl ?>admin/admin-login.php" class="px-4 py-2 rounded-2xl bg-[#17611f] text-white text-xs font-bold hover:opacity-90 transition-opacity">Login as Admin</a>
        <?php endif; ?>
      </div>
    </div>
  </footer>


<!-- ============================================================ -->
<!-- CHAT WIDGET - ONLY ON INDEX.PHP (Bottom Right)               -->
<!-- ============================================================ -->
<?php 
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if ($currentPage === 'index.php'): 
?>
<a href="live-chat.php" 
   id="chatWidget"
   class="fixed bottom-8 right-8 z-[9998] flex items-center gap-3 bg-[#17611f] text-white px-5 py-3 rounded-full shadow-lg hover:opacity-90 hover:scale-105 transition-all duration-300 group"
   aria-label="Chat with us">
    <!-- Chat Icon -->
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    <span class="hidden md:inline text-sm font-semibold whitespace-nowrap">Chat with us</span>
    <!-- Status Dot -->
    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-green-400 border-2 border-white rounded-full animate-pulse"></span>
</a>

<style>
    /* Chat widget pulse animation for status dot */
    .animate-pulse {
        animation: pulse-dot 2s ease-in-out infinite;
    }
    
    @keyframes pulse-dot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.7; }
    }
</style>

<script>
    // Hide chat widget when scrolling down, show when scrolling up
    document.addEventListener('DOMContentLoaded', function() {
        const chatWidget = document.getElementById('chatWidget');
        if (!chatWidget) return;
        
        let lastScrollY = window.scrollY || window.pageYOffset;
        let isVisible = true;
        
        window.addEventListener('scroll', function() {
            const scrollY = window.scrollY || window.pageYOffset;
            
            // Hide chat when scrolled down significantly (past 500px)
            if (scrollY > 500 && scrollY > lastScrollY) {
                // Scrolling down - hide
                if (isVisible) {
                    chatWidget.style.opacity = '0';
                    chatWidget.style.transform = 'translateY(20px) scale(0.9)';
                    chatWidget.style.pointerEvents = 'none';
                    isVisible = false;
                }
            } else if (scrollY < lastScrollY || scrollY < 500) {
                // Scrolling up or near top - show
                if (!isVisible) {
                    chatWidget.style.opacity = '1';
                    chatWidget.style.transform = 'translateY(0) scale(1)';
                    chatWidget.style.pointerEvents = 'auto';
                    isVisible = true;
                }
            }
            
            lastScrollY = scrollY;
        }, { passive: true });
    });
</script>
<?php endif; ?>
