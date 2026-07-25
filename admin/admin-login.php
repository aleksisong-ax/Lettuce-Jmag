<?php
session_start();
require_once __DIR__ . '/../config.php';

// Already logged in? Skip straight to the dashboard.
if (isset($_SESSION['admin_id'])) {
    header("Location: admin-dashboard.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $message = "Please enter both your email and password.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {

            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];

            header("Location: admin-dashboard.php");
            exit();

        } else {

            $message = "Incorrect email or password.";
            $messageType = "error";

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | WoodCraft Care</title>
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
  <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 h-20 flex items-center justify-between">
      <a href="../index.php" class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-[#2E1D14] flex items-center justify-center">
          <span class="text-[#D8B98A] font-serif text-lg tracking-tight">W</span>
        </div>
        <span class="text-lg font-semibold text-gray-900">WoodCraft Care</span>
      </a>
      <nav class="hidden md:flex items-center gap-10 text-[15px] text-gray-700">
        <a href="../index.php" class="hover:text-gray-900 transition-colors">Home</a>
        <a href="../faq.php" class="hover:text-gray-900 transition-colors">FAQ</a>
        <a href="../contact-support.php" class="hover:text-gray-900 transition-colors">Contact Support</a>
      </nav>
      <div class="flex items-center gap-3">
        <a href="../login.php" class="hidden sm:inline-block px-5 py-2.5 rounded-full border border-gray-300 text-sm font-medium text-gray-800 hover:bg-gray-50 transition-colors">Login</a>
        <a href="../register.php" class="px-5 py-2.5 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors">Register</a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-10">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#B5702E] bg-orange-50 rounded-full px-3 py-1 mb-5">ADMIN</span>
      <h1 class="font-serif text-3xl font-semibold text-gray-900 mb-4">Admin Login</h1>
      <div class="text-gray-600 text-[15px] leading-relaxed space-y-4">
        <p>Sign in with your administrator credentials to manage tickets, warranty claims, and customer accounts.</p>
      </div>

      <?php if ($message): ?>
        <div class="mt-6 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <form class="space-y-5 mt-6" method="POST">
          <div>
            <label class="block text-sm font-medium text-gray-800 mb-2">Admin Email</label>
            <input type="email" name="email" required placeholder="admin@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/40 focus:border-[#6B4226] transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-800 mb-2">Password</label>
            <div class="relative">
              <input type="password" id="password" name="password" required placeholder="••••••••••••" class="w-full rounded-xl border border-gray-200 px-4 py-3 pr-11 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/40 focus:border-[#6B4226] transition-colors" />
              <button type="button" class="password-toggle-btn absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" data-target="password" aria-label="Show password">
                <svg class="w-4 h-4 icon-eye" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg class="w-4 h-4 icon-eye-off hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 012.132-3.532m3.32-2.454A9.958 9.958 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.411M14.121 14.121A3 3 0 019.88 9.88M9.879 9.879l4.242 4.242M9.879 9.879L3 3m6.879 6.879L21 21"/></svg>
              </button>
            </div>
          </div>
          <button type="submit" class="w-full rounded-full bg-[#6B4226] text-white text-sm font-medium py-3.5 hover:bg-[#59341C] transition-colors">Sign In as Admin</button>
      </form>

      <p class="text-xs text-gray-400 mt-6">First time here? The default account is <strong>admin@woodcraftcare.com</strong> / <strong>Admin@123</strong> </p>
  </main>

  <script>
    // Show/hide toggle for the password field
    document.querySelectorAll('.password-toggle-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const eyeIcon = btn.querySelector('.icon-eye');
        const eyeOffIcon = btn.querySelector('.icon-eye-off');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        eyeIcon.classList.toggle('hidden', isHidden);
        eyeOffIcon.classList.toggle('hidden', !isHidden);
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      });
    });
  </script>

</body>
</html>