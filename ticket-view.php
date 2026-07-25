<?php
session_start();
require 'config.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/form-helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$ticketId = (int)($_GET['id'] ?? 0);

if ($ticketId <= 0) {
    header("Location: my-profile.php");
    exit();
}

$message = "";
$messageType = "";

// Look up the ticket, scoped to the logged-in customer
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticketId, $uid]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header("Location: my-profile.php");
    exit();
}

$ticketNumber = 'WC-' . str_pad((string)$ticket['id'], 4, '0', STR_PAD_LEFT);
$nameStmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$nameStmt->execute([$uid]);
$nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
$customerName = $nameRow ? trim($nameRow['first_name'] . ' ' . $nameRow['last_name']) : 'A customer';

// Handle ticket actions (Close or Reopen)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_action'])) {

    $action = $_POST['ticket_action'];

    if ($ticket['status'] === 'resolved' && $action === 'close') {
        // Update ticket status to closed
        $update = $conn->prepare("UPDATE tickets SET status = 'closed' WHERE id = ? AND user_id = ? AND status = 'resolved'");
        $update->execute([$ticketId, $uid]);

        if ($update->rowCount() > 0) {
            // Create notification for admin
            createNotification(
                $conn,
                'ticket_closed',
                $ticketId,
                'Ticket Confirmed & Closed',
                "Customer confirmed Ticket #$ticketNumber has been resolved and closed the ticket.",
                $customerName
            );

            // Also log this as a customer reply in the thread
            $insertReply = $conn->prepare("
                INSERT INTO ticket_replies (ticket_id, sender_type, message)
                VALUES (?, 'customer', ?)
            ");
            $insertReply->execute([
                $ticketId,
                "✅ I confirm that this issue has been resolved. Closing this ticket. Thank you for your help!"
            ]);

            header("Location: ticket-view.php?id=" . $ticketId . "&closed=1");
            exit();
        } else {
            // Ticket was already updated by someone else
            header("Location: ticket-view.php?id=" . $ticketId);
            exit();
        }

    } elseif ($ticket['status'] === 'resolved' && $action === 'reopen') {
        $update = $conn->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ? AND user_id = ? AND status = 'resolved'");
        $update->execute([$ticketId, $uid]);

        if ($update->rowCount() > 0) {
            createNotification(
                $conn,
                'ticket_reopen',
                $ticketId,
                'Ticket Reopened',
                "Customer reopened Ticket #$ticketNumber.",
                $customerName
            );

            // Also log this as a customer reply
            $insertReply = $conn->prepare("
                INSERT INTO ticket_replies (ticket_id, sender_type, message)
                VALUES (?, 'customer', ?)
            ");
            $insertReply->execute([
                $ticketId,
                "❌ This issue is not fully resolved yet. Please continue assisting me."
            ]);

            header("Location: ticket-view.php?id=" . $ticketId . "&reopened=1");
            exit();
        } else {
            header("Location: ticket-view.php?id=" . $ticketId);
            exit();
        }

    } else {
        header("Location: ticket-view.php?id=" . $ticketId);
        exit();
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {

    $reply = trim($_POST['reply_message']);

    if ($reply === '') {
        $message = "Please write a message before sending.";
        $messageType = "error";
    } elseif (mb_strlen($reply) > 2000) {
        $message = "Your message is too long (2000 characters max).";
        $messageType = "error";
    } elseif ($ticket['status'] === 'closed') {
        $message = "This ticket is closed and can no longer receive new messages.";
        $messageType = "error";
    } else {
        try {
            $insertReply = $conn->prepare("
                INSERT INTO ticket_replies (ticket_id, sender_type, message)
                VALUES (?, 'customer', ?)
            ");
            $insertReply->execute([$ticketId, $reply]);

            // If ticket was resolved, set back to in_progress when customer replies
            if ($ticket['status'] === 'resolved') {
                $update = $conn->prepare("UPDATE tickets SET status = 'in_progress' WHERE id = ?");
                $update->execute([$ticketId]);
            }

            createNotification(
                $conn,
                'ticket_reply',
                $ticketId,
                'Customer Replied to Ticket',
                "Customer replied to Ticket #$ticketNumber:\n\n" . substr($reply, 0, 100) . (strlen($reply) > 100 ? '...' : ''),
                $customerName
            );

            header("Location: ticket-view.php?id=" . $ticketId . "&sent=1");
            exit();

        } catch (PDOException $e) {
            $message = "Something went wrong sending your message. Please try again.";
            $messageType = "error";
        }
    }
}

// Handle success messages
if (isset($_GET['sent'])) {
    $message = "Your message was sent successfully.";
    $messageType = "success";
} elseif (isset($_GET['closed'])) {
    $message = "✅ Thank you for confirming that your issue has been resolved. This ticket has been closed successfully.";
    $messageType = "success";
} elseif (isset($_GET['reopened'])) {
    $message = "✅ Your ticket has been reopened. Our support team has been notified and will continue assisting you.";
    $messageType = "success";
}

// Get all replies
$repliesStmt = $conn->prepare("SELECT * FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC, id ASC");
$repliesStmt->execute([$ticketId]);
$ticketReplies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);

function ticketStatusBadge(string $status): string
{
    $map = [
        'open' => ['blue', 'Open'],
        'in_progress' => ['amber', 'In Progress'],
        'resolved' => ['green', 'Resolved'],
        'closed' => ['gray', 'Closed'],
    ];
    [$color, $label] = $map[$status] ?? ['gray', ucfirst($status)];
    $colors = ['blue' => 'text-blue-600 bg-blue-50', 'amber' => 'text-amber-600 bg-amber-50', 'green' => 'text-green-600 bg-green-50', 'gray' => 'text-gray-500 bg-gray-100'];
    return "<span class=\"inline-flex items-center px-3 py-1 rounded-full text-[12px] font-medium {$colors[$color]}\">$label</span>";
}

$isClosed = $ticket['status'] === 'closed';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ticket #WC-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?> | WoodCraft Care</title>
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

  <!-- Main Content -->
  <main class="flex-1 max-w-3xl w-full mx-auto px-6 py-16">
    <a href="my-profile.php" class="inline-flex items-center gap-2 text-sm text-[#6B4226] hover:text-[#59341C] transition-colors mb-8">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
      Back to My Profile
    </a>

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-8 pb-5 border-b border-gray-100">
        <div class="flex items-start justify-between gap-3 mb-2">
          <div>
            <p class="text-[12px] text-gray-400 mb-1">Ticket #WC-<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?></p>
            <h1 class="font-serif text-2xl font-semibold text-gray-900"><?= htmlspecialchars($ticket['subject']) ?></h1>
          </div>
          <?= ticketStatusBadge($ticket['status']) ?>
        </div>
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-[13px] text-gray-500 mt-3">
          <span>Category: <span class="font-medium text-gray-700"><?= htmlspecialchars($ticket['category']) ?></span></span>
          <span>Priority: <span class="font-medium text-gray-700"><?= htmlspecialchars($ticket['priority'] ?? 'Medium') ?></span></span>
          <?php if (!empty($ticket['order_number'])): ?>
            <span>Order #: <span class="font-medium text-gray-700"><?= htmlspecialchars($ticket['order_number']) ?></span></span>
          <?php endif; ?>
          <span>Submitted: <span class="font-medium text-gray-700"><?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span></span>
        </div>
        <?php $ticketAttachments = decodeAttachmentPaths($ticket['attachment_path'] ?? null); ?>
        <?php if (!empty($ticketAttachments)): ?>
          <div class="flex flex-col gap-1 mt-3">
            <?php foreach ($ticketAttachments as $i => $attPath): ?>
              <a href="<?= htmlspecialchars($attPath) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[13px] font-medium text-[#6B4226] hover:underline">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                View Your Attachment<?= count($ticketAttachments) > 1 ? ' ' . ($i + 1) : '' ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($message): ?>
        <div class="mx-6 mt-5 rounded-xl px-4 py-3 text-sm <?= $messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' ?>">
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>

      <!-- Conversation thread -->
      <div class="p-6 space-y-4 max-h-[440px] overflow-y-auto bg-[#FBF9F4]" id="threadContainer">

        <!-- Original ticket message -->
        <div class="flex justify-end">
          <div class="max-w-[80%]">
            <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed bg-[#6B4226] text-white rounded-br-sm whitespace-pre-line"><?= htmlspecialchars($ticket['issue_description']) ?></div>
            <p class="text-[11px] text-gray-400 mt-1 text-right">
              You · <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
            </p>
          </div>
        </div>

        <?php foreach ($ticketReplies as $r):
            $isCustomer = $r['sender_type'] === 'customer';
            $isSystem = strpos($r['message'], '✅ I confirm') === 0 || strpos($r['message'], '❌ This issue') === 0;
        ?>
          <div class="flex <?= $isCustomer ? 'justify-end' : 'justify-start' ?>">
            <div class="max-w-[80%]">
              <div class="rounded-2xl px-4 py-2.5 text-[14px] leading-relaxed whitespace-pre-line 
                <?= $isCustomer 
                    ? ($isSystem ? 'bg-blue-50 border border-blue-200 text-blue-800 rounded-br-sm' : 'bg-[#6B4226] text-white rounded-br-sm') 
                    : 'bg-white border border-gray-200 text-gray-800 rounded-bl-sm' ?>">
                <?= htmlspecialchars($r['message']) ?>
              </div>
              <p class="text-[11px] text-gray-400 mt-1 <?= $isCustomer ? 'text-right' : 'text-left' ?>">
                <?= $isCustomer ? ($isSystem ? 'You (System)' : 'You') : 'WoodCraft Support' ?> · <?= date('M j, Y g:i A', strtotime($r['created_at'])) ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>

      </div>

      <!-- Resolution confirmation — shown only while the ticket is Resolved -->
      <?php if ($ticket['status'] === 'resolved'): ?>
        <div class="p-5 border-t border-gray-100 bg-green-50/60 space-y-3">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
              <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-800">✅ Our support team believes your issue has been resolved.</p>
              <p class="text-sm text-gray-600">Please let us know if everything has been resolved successfully.</p>
            </div>
          </div>
          <div class="flex flex-wrap gap-3 pt-1 pl-11">
            <form method="POST" onsubmit="return confirm('Are you sure you want to close this ticket?\n\nOnce closed, no additional replies can be added.');">
              <input type="hidden" name="ticket_action" value="close" />
              <button type="submit" class="px-5 py-2.5 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                Yes, Close Ticket
              </button>
            </form>
            <form method="POST" onsubmit="return confirm('We are sorry your issue has not been fully resolved.\n\nWould you like to reopen this ticket?');">
              <input type="hidden" name="ticket_action" value="reopen" />
              <button type="submit" class="px-5 py-2.5 rounded-full border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition-colors">
                No, I Still Need Help
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <!-- Send reply -->
      <?php if ($isClosed): ?>
        <div class="p-5 border-t border-gray-100 text-center">
          <div class="inline-flex items-center gap-2 text-sm text-gray-500 bg-gray-50 rounded-xl px-4 py-3">
            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            This ticket has been closed and no longer accepts new replies.
          </div>
          <p class="text-xs text-gray-400 mt-2">If you experience another issue, please <a href="submit-ticket.php" class="text-[#6B4226] font-medium hover:underline">submit a new support ticket</a>.</p>
        </div>
      <?php else: ?>
        <form method="POST" class="p-5 border-t border-gray-100">
          <div class="flex items-end gap-3">
            <div class="flex-1">
              <textarea name="reply_message" rows="2" maxlength="2000" required placeholder="Type your reply..."
                        class="w-full rounded-2xl border border-gray-200 px-5 py-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#6B4226]/40 focus:border-[#6B4226] transition-colors resize-none"></textarea>
              <p class="mt-1.5 text-[11px] text-gray-400 text-right max-w-[2000]">Max 2000 characters</p>
            </div>
            <button type="submit" class="px-6 py-3 rounded-full bg-[#6B4226] text-white text-sm font-medium hover:bg-[#59341C] transition-colors flex-shrink-0">Send</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

  <script>
    // Keep the thread scrolled to the latest message on load
    window.addEventListener('DOMContentLoaded', () => {
      const el = document.getElementById('threadContainer');
      if (el) el.scrollTop = el.scrollHeight;
    });
  </script>

</body>
</html>