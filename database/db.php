<?php
/**
 * db.php
 * ------------------------------------------------------------------
 * WoodCraft Care — Automatic Database Initializer
 * ------------------------------------------------------------------
 * Visit this file directly in your browser (or run it once via CLI)
 * after importing the project into XAMPP:
 *
 *      http://localhost/site/db.php
 *
 * It will:
 *   1. Connect to MySQL using the credentials below.
 *   2. Create the project database if it doesn't already exist.
 *   3. Create every table this project needs (with the correct
 *      columns, keys, foreign keys, and default values) if they
 *      don't already exist.
 *   4. Report a summary of what it did.
 *
 * It is 100% safe to run multiple times — every statement uses
 * "IF NOT EXISTS", so nothing gets dropped or duplicated.
 *
 * This file is also loaded automatically by config.php on every
 * request, so the schema self-heals even if you forget to run it
 * manually. Running it directly just gives you a visual report.
 *
 * The schema below was reverse-engineered directly from the SQL
 * queries found in the project's PHP files (register.php, login.php,
 * submit-ticket.php, warranty-request.php, returns-refund.php,
 * feedback.php, my-profile.php) so it matches exactly what the
 * application expects — no guessed columns.
 * ------------------------------------------------------------------
 */

// ---------------------------------------------------------------
// 1. Connection settings
//    Change these if your MySQL credentials differ on this machine.
//    Every other file (config.php) reuses these same values, so you
//    only ever need to edit them in one place.
// ---------------------------------------------------------------
$host     = "localhost";
$dbname   = "luntiang-hapag";
$username = "root";
$password = ""; // Change this if your MySQL root account has a password
$charset  = "utf8mb4";

// Only produce a full HTML report when this file is opened directly
// in the browser. When included from config.php, stay silent.
$isDirectRequest = basename($_SERVER['SCRIPT_NAME']) === 'db.php';

$report = [];

try {
    // -----------------------------------------------------------
    // 2. Connect to the MySQL server WITHOUT selecting a database
    //    yet, because the database itself might not exist.
    // -----------------------------------------------------------
    $serverConn = new PDO(
        "mysql:host=$host;charset=$charset",
        $username,
        $password
    );
    $serverConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------------------------------------
    // 3. Create the database if it does not already exist.
    // -----------------------------------------------------------
    $serverConn->exec(
        "CREATE DATABASE IF NOT EXISTS `$dbname`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $report[] = "Database `$dbname` is ready.";

    // -----------------------------------------------------------
    // 4. Reconnect, this time selecting the database, so all
    //    further statements (and the app itself) run against it.
    // -----------------------------------------------------------
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // -----------------------------------------------------------
    // 5b. Small helper: safely add a column to a table only if it
    //     doesn't already exist yet. MySQL/MariaDB on XAMPP can be
    //     old enough not to support "ADD COLUMN IF NOT EXISTS", so
    //     we check information_schema first. This lets us evolve
    //     tables that were created by an earlier version of this
    //     file without ever dropping existing data.
    // -----------------------------------------------------------
    function addColumnIfMissing(PDO $conn, string $dbname, string $table, string $column, string $definition): void
    {
        $check = $conn->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $check->execute([$dbname, $table, $column]);
        if ((int)$check->fetchColumn() === 0) {
            $conn->exec("ALTER TABLE `$table` ADD COLUMN $definition");
        }
    }

    // -----------------------------------------------------------
    // 5c. Small helper: widen a column to TEXT if it isn't already.
    //     The attachment path columns below used to hold a single
    //     VARCHAR(255) path; now that customers can attach multiple
    //     files per field, they hold a JSON-encoded array of paths
    //     instead, which can outgrow 255 characters. Safe to run on
    //     every request — it's a no-op once the column is TEXT.
    // -----------------------------------------------------------
    function widenColumnToText(PDO $conn, string $dbname, string $table, string $column): void
    {
        $check = $conn->prepare("
            SELECT DATA_TYPE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $check->execute([$dbname, $table, $column]);
        $currentType = $check->fetchColumn();
        if ($currentType !== false && strtolower((string)$currentType) !== 'text') {
            $conn->exec("ALTER TABLE `$table` MODIFY `$column` TEXT NULL");
        }
    }

    // -----------------------------------------------------------
    // 5. TABLE: users
    //    Required by: register.php, login.php, my-profile.php
    //    - email must be unique (register.php checks for duplicates)
    //    - password stores a password_hash() value
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            address VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    addColumnIfMissing($conn, $dbname, 'users', 'reset_token', "reset_token VARCHAR(64) NULL AFTER password");
    addColumnIfMissing($conn, $dbname, 'users', 'reset_token_expires', "reset_token_expires DATETIME NULL AFTER reset_token");
    $report[] = "Table `users` is ready.";

    // -----------------------------------------------------------
    // 6. TABLE: admins
    //    Required by: admin/admin-login.php and every other admin/*
    //    page. Holds real admin accounts (instead of the old
    //    no-login mockup) authenticated with password_verify().
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'Admin',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_admins_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `admins` is ready.";

    // -----------------------------------------------------------
    // 7. TABLE: tickets
    //    Required by: submit-ticket.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-tickets.php + admin-ticket-detail.php
    //    (SELECT/UPDATE). subject/category/admin_reply/replied_at
    //    were added to support the admin reply workflow.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) DEFAULT NULL,
            issue_description TEXT NOT NULL,
            status ENUM('open', 'in_progress', 'resolved', 'closed')
                NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_tickets_user_id (user_id),
            CONSTRAINT fk_tickets_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    addColumnIfMissing($conn, $dbname, 'tickets', 'subject', "subject VARCHAR(150) NOT NULL DEFAULT 'General Inquiry' AFTER user_id");
    addColumnIfMissing($conn, $dbname, 'tickets', 'category', "category VARCHAR(50) NOT NULL DEFAULT 'General' AFTER subject");
    // priority and attachment_path support the Submit a Ticket form's
    // Priority dropdown and optional Attachment upload. Nullable/defaulted
    // so existing rows keep working without errors.
    addColumnIfMissing($conn, $dbname, 'tickets', 'priority', "priority ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium' AFTER category");
    addColumnIfMissing($conn, $dbname, 'tickets', 'attachment_path', "attachment_path VARCHAR(255) NULL AFTER issue_description");
    widenColumnToText($conn, $dbname, 'tickets', 'attachment_path');
    addColumnIfMissing($conn, $dbname, 'tickets', 'admin_reply', "admin_reply TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'tickets', 'replied_at', "replied_at TIMESTAMP NULL DEFAULT NULL AFTER admin_reply");
    $report[] = "Table `tickets` is ready.";

    // -----------------------------------------------------------
    // 7b. TABLE: ticket_replies
    //     Required by: admin/admin-ticket-detail.php (INSERT/SELECT)
    //     and ticket-view.php (INSERT/SELECT). Holds the full,
    //     ordered two-way conversation for a ticket — both customer
    //     follow-ups and admin replies — instead of the single
    //     admin_reply column tickets used to have.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            sender_type ENUM('customer', 'admin') NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ticket_replies_ticket_id (ticket_id),
            CONSTRAINT fk_ticket_replies_ticket
                FOREIGN KEY (ticket_id) REFERENCES tickets(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `ticket_replies` is ready.";

    // One-time migration: any older ticket that still has its reply
    // sitting in tickets.admin_reply (from before ticket_replies
    // existed) gets that reply copied into the thread, so nothing
    // written under the old system is lost. Safe to run every
    // request — the NOT IN subquery means an already-migrated
    // ticket is never duplicated.
    $conn->exec("
        INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at)
        SELECT id, 'admin', admin_reply, COALESCE(replied_at, created_at)
        FROM tickets
        WHERE admin_reply IS NOT NULL
          AND admin_reply <> ''
          AND id NOT IN (SELECT ticket_id FROM ticket_replies WHERE sender_type = 'admin')
    ");

    // -----------------------------------------------------------
    // 8. TABLE: warranty_requests
    //    Required by: warranty-request.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-warranty.php (SELECT/UPDATE)
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS warranty_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_name VARCHAR(150) NOT NULL,
            defect_description TEXT NOT NULL,
            status ENUM('pending', 'approved', 'denied')
                NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_warranty_user_id (user_id),
            CONSTRAINT fk_warranty_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // purchase_date captures when the customer bought the product, used
    // by the admin side to judge warranty eligibility against the 5-year
    // policy. Nullable so existing rows created before this column
    // existed keep working without errors; new submissions always set it
    // (warranty-request.php requires it before allowing submission).
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'purchase_date', "purchase_date DATE NULL AFTER product_name");
    // order_number and warranty_issue support the Warranty Request form's
    // Order Number field and Warranty Issue dropdown. proof_of_purchase_path
    // and damage_photo_path store the uploaded supporting files. All are
    // nullable so existing rows keep working without errors; the form
    // itself requires order_number/warranty_issue/proof of purchase before
    // allowing submission.
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'order_number', "order_number VARCHAR(50) NULL AFTER product_name");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'warranty_issue', "warranty_issue VARCHAR(100) NULL AFTER purchase_date");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'proof_of_purchase_path', "proof_of_purchase_path VARCHAR(255) NULL AFTER defect_description");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'damage_photo_path', "damage_photo_path VARCHAR(255) NULL AFTER proof_of_purchase_path");
    widenColumnToText($conn, $dbname, 'warranty_requests', 'proof_of_purchase_path');
    widenColumnToText($conn, $dbname, 'warranty_requests', 'damage_photo_path');
    // admin_note lets an admin leave a (multi-line) update/instruction for
    // the customer without opening a full conversation thread. updated_at
    // tracks the last time the request's status or note changed, so the
    // customer's "Last Updated" display always reflects the latest admin
    // action, not just the original submission date.
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'admin_note', "admin_note TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'warranty_requests', 'updated_at', "updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    $report[] = "Table `warranty_requests` is ready.";

    // -----------------------------------------------------------
    // 9. TABLE: return_requests
    //    Required by: returns-refund.php (INSERT), my-profile.php
    //    (SELECT), admin/admin-returns.php (SELECT/UPDATE)
    //    order_number and reason are always supplied by the form,
    //    so both are NOT NULL here.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS return_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            order_number VARCHAR(50) NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'approved', 'denied', 'completed')
                NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_returns_user_id (user_id),
            CONSTRAINT fk_returns_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // product_name, purchase_date, reason_category, and product_condition
    // support the Return & Refund form's added fields. `reason` (already
    // NOT NULL above) continues to hold the Detailed Explanation textarea,
    // while reason_category holds the short dropdown selection. The two
    // path columns store the uploaded supporting files. All are nullable
    // so existing rows keep working without errors; the form itself
    // requires them before allowing submission.
    addColumnIfMissing($conn, $dbname, 'return_requests', 'product_name', "product_name VARCHAR(150) NULL AFTER order_number");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'purchase_date', "purchase_date DATE NULL AFTER product_name");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'reason_category', "reason_category VARCHAR(50) NULL AFTER purchase_date");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'product_condition', "product_condition VARCHAR(20) NULL AFTER reason");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'proof_of_purchase_path', "proof_of_purchase_path VARCHAR(255) NULL AFTER product_condition");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'damage_photo_path', "damage_photo_path VARCHAR(255) NULL AFTER proof_of_purchase_path");
    widenColumnToText($conn, $dbname, 'return_requests', 'proof_of_purchase_path');
    widenColumnToText($conn, $dbname, 'return_requests', 'damage_photo_path');
    // Same Admin Note / Last Updated pattern used on warranty_requests —
    // see the comment there for why these two columns exist.
    addColumnIfMissing($conn, $dbname, 'return_requests', 'admin_note', "admin_note TEXT NULL AFTER status");
    addColumnIfMissing($conn, $dbname, 'return_requests', 'updated_at', "updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
    $report[] = "Table `return_requests` is ready.";

    // -----------------------------------------------------------
    // 9b. TABLE: notifications
    //     Required by: includes/notifications.php (INSERT helper used
    //     by submit-ticket.php, ticket-view.php, warranty-request.php,
    //     returns-refund.php, admin/admin-warranty.php,
    //     admin/admin-returns.php) and admin/notifications.php +
    //     admin/includes/admin-topbar.php (SELECT/UPDATE). Notifies
    //     admins of important customer activity (new ticket, ticket
    //     reply/reopen/close, new warranty/return requests, status
    //     changes). Global (not tied to a single admin account) since
    //     any admin should be able to see and act on them.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            related_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            customer_name VARCHAR(150) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notifications_is_read (is_read),
            KEY idx_notifications_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `notifications` is ready.";

    // -----------------------------------------------------------
    // 10. TABLE: feedback
    //     Required by: feedback.php (INSERT, logged-in),
    //     contact-support.php (INSERT, guests allowed too),
    //     my-profile.php (SELECT), admin/admin-feedback.php
    //     (SELECT/DELETE).
    //     user_id is nullable so guests (not logged in) can leave
    //     feedback from the Contact Support page; guest_name/
    //     guest_email capture who they are in that case.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comments TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_feedback_user_id (user_id),
            CONSTRAINT fk_feedback_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Allow guest feedback: user_id must be nullable (older installs
    // may have created it as NOT NULL before this update).
    $conn->exec("ALTER TABLE feedback MODIFY user_id INT NULL");
    addColumnIfMissing($conn, $dbname, 'feedback', 'guest_name', "guest_name VARCHAR(150) NULL AFTER user_id");
    addColumnIfMissing($conn, $dbname, 'feedback', 'guest_email', "guest_email VARCHAR(150) NULL AFTER guest_name");
    addColumnIfMissing($conn, $dbname, 'feedback', 'subject', "subject VARCHAR(150) NULL AFTER guest_email");
    $report[] = "Table `feedback` is ready.";

    // -----------------------------------------------------------
    // 11. TABLE: live_chat_messages
    //     Required by: live-chat.php (customer widget) and
    //     admin/admin-live-chat.php. Conversations are grouped by
    //     `chat_key` (the customer's PHP session ID) so both logged
    //     in users and guests can chat; customer_name is stored
    //     per-message so admin sees a readable name even for guests.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS live_chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_key VARCHAR(64) NOT NULL,
            user_id INT NULL,
            customer_name VARCHAR(150) NOT NULL,
            sender ENUM('customer', 'admin') NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_chat_key (chat_key),
            KEY idx_chat_user_id (user_id),
            CONSTRAINT fk_chat_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Widen the sender enum to add 'bot', for the AI assistant that now
    // greets and triages every conversation before a human joins in.
    // Existing 'customer'/'admin' rows and every query that already
    // compares sender = 'admin' / sender = 'customer' are unaffected.
    $conn->exec("ALTER TABLE live_chat_messages MODIFY sender ENUM('customer', 'admin', 'bot') NOT NULL");
    $report[] = "Table `live_chat_messages` is ready.";

    // -----------------------------------------------------------
    // 11b. TABLE: chat_bot_state
    //      One row per chat_key. Tracks whether the AI assistant is
    //      still handling this conversation (bot_active) and any
    //      pending follow-up question it's mid-way through asking
    //      (pending_intent / pending_context), so a multi-turn
    //      clarification ("was the crack there on delivery, or did
    //      it happen after use?") survives across separate
    //      chat-send.php requests. Required by: includes/
    //      chatbot-engine.php, chat-send.php.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS chat_bot_state (
            chat_key VARCHAR(64) NOT NULL PRIMARY KEY,
            bot_active TINYINT(1) NOT NULL DEFAULT 1,
            pending_intent VARCHAR(50) NULL,
            pending_context TEXT NULL,
            last_topic VARCHAR(100) NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    // Add last_topic to installs created before this column existed.
    $lastTopicCol = $conn->query("SHOW COLUMNS FROM chat_bot_state LIKE 'last_topic'")->fetch();
    if (!$lastTopicCol) {
        $conn->exec("ALTER TABLE chat_bot_state ADD COLUMN last_topic VARCHAR(100) NULL AFTER pending_context");
    }
    $report[] = "Table `chat_bot_state` is ready.";

    // -----------------------------------------------------------
    // 13. TABLE: faqs
    //     Required by: admin/admin-faq.php (full CRUD) and could be
    //     used to drive the public faq.php page in the future.
    // -----------------------------------------------------------
    $conn->exec("
        CREATE TABLE IF NOT EXISTS faqs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(255) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'General',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $report[] = "Table `faqs` is ready.";

    $faqCount = (int)$conn->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
    if ($faqCount === 0) {
        $seedFaq = $conn->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
        
        // Original starter FAQs
        $seedFaq->execute(["How long does WoodCraft's warranty last?", "All WoodCraft furniture is backed by a 5-year limited warranty that covers manufacturing defects in materials and craftsmanship, starting from your original date of purchase. If a covered issue comes up, we'll repair, replace the part, or replace the item at no extra cost.", "Warranty"]);
        $seedFaq->execute(["What is WoodCraft's return policy?", "You can return most items within 30 days of delivery for a full refund, as long as they're unused and in their original packaging. Custom and made-to-order pieces are final sale and aren't eligible for return unless they arrive damaged or defective.", "Returns"]);
        $seedFaq->execute(["How do I care for my solid oak furniture?", "Dust regularly with a soft, dry microfiber cloth and keep your piece out of direct sunlight and away from heat sources, which can dry out and crack the wood. Every few months, apply a food-safe wood conditioner to nourish the grain, and avoid harsh chemical cleaners or abrasive pads.", "Care"]);
        $seedFaq->execute(["My furniture arrived damaged. What should I do?", "Please contact our support team within 48 hours of delivery and include a few photos of the damage along with your order number. We'll get a replacement part, repair, or full replacement shipped out to you at no additional cost.", "Damaged"]);
        $seedFaq->execute(["How long does delivery take?", "Most in-stock orders arrive within 5-10 business days. Custom or made-to-order pieces typically take 3-6 weeks to craft and ship, depending on the item. You'll receive tracking information by email as soon as your order leaves our workshop.", "Shipping"]);
        
        // NEW: Technical Support & Account FAQs
        $seedFaq->execute(["How do I submit a support ticket?", "To submit a support ticket:\n\n1. Log into your WoodCraft Care account\n2. Go to the Submit a Ticket page\n3. Fill in the Subject, Category, and a clear description\n4. Add your Order Number (if applicable)\n5. Attach any relevant photos or files\n6. Click Submit\n\nYou can track your ticket's status in My Support Tickets.", "Technical Support"]);
        $seedFaq->execute(["How do I submit a warranty request?", "To submit a warranty request:\n\n1. Log into your WoodCraft Care account\n2. Go to Warranty Request\n3. Provide:\n   • Product Name\n   • Order Number\n   • Purchase Date\n   • Warranty Issue (category)\n   • Description of the defect\n   • Proof of Purchase (required)\n   • Damage Photo (optional)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Warranty"]);
        $seedFaq->execute(["How do I request a return or refund?", "To request a return or refund:\n\n1. Log into your WoodCraft Care account\n2. Go to Return Request\n3. Provide:\n   • Order Number\n   • Product Name\n   • Purchase Date\n   • Reason for Return\n   • Detailed Explanation\n   • Product Condition\n   • Proof of Purchase (required)\n   • Damage Photo (optional)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Returns"]);
        $seedFaq->execute(["How do I create an account?", "Creating an account is quick:\n\n1. Go to the Register page\n2. Enter your full name, email address, and a password\n3. Confirm your password and submit the form\n4. Check your inbox for a verification link (if required) and click it to activate your account\n\nOnce that's done, you can log in and start tracking orders right away.", "Account"]);
        $seedFaq->execute(["What does the warranty cover?", "Our warranty covers manufacturing defects including:\n\n• Structural issues\n• Material flaws\n• Broken parts during normal use\n\nIt does NOT cover:\n• Accidental damage\n• Misuse or improper assembly\n• Normal wear and tear\n• Damage from moving or transporting\n\nFor a detailed breakdown of the warranty process, visit our warranty page or contact support.", "Warranty"]);
        $seedFaq->execute(["How long is the delivery time?", "Delivery times vary by location and item:\n\n• Metro areas: 5-7 business days\n• Regional areas: 7-10 business days\n• Remote areas: 10-14 business days\n• Large/bulky items: add 2-3 extra days for freight scheduling\n• Custom/made-to-order: 3-6 weeks\n\nYou'll receive a tracking number via email once your order ships.", "Shipping"]);
        
        $report[] = "Seeded 11 FAQ entries (5 original + 6 new technical support/account FAQs).";
    } else {
        // Check if new FAQs exist, if not add them
        $newFaqs = [
            ["How do I submit a support ticket?", "To submit a support ticket:\n\n1. Log into your WoodCraft Care account\n2. Go to the Submit a Ticket page\n3. Fill in the Subject, Category, and a clear description\n4. Add your Order Number (if applicable)\n5. Attach any relevant photos or files\n6. Click Submit\n\nYou can track your ticket's status in My Support Tickets.", "Technical Support"],
            ["How do I submit a warranty request?", "To submit a warranty request:\n\n1. Log into your WoodCraft Care account\n2. Go to Warranty Request\n3. Provide:\n   • Product Name\n   • Order Number\n   • Purchase Date\n   • Warranty Issue (category)\n   • Description of the defect\n   • Proof of Purchase (required)\n   • Damage Photo (optional)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Warranty"],
            ["How do I request a return or refund?", "To request a return or refund:\n\n1. Log into your WoodCraft Care account\n2. Go to Return Request\n3. Provide:\n   • Order Number\n   • Product Name\n   • Purchase Date\n   • Reason for Return\n   • Detailed Explanation\n   • Product Condition\n   • Proof of Purchase (required)\n   • Damage Photo (optional)\n4. Click Submit\n\nOur team will review your request within 1-2 business days.", "Returns"],
            ["How do I create an account?", "Creating an account is quick:\n\n1. Go to the Register page\n2. Enter your full name, email address, and a password\n3. Confirm your password and submit the form\n4. Check your inbox for a verification link (if required) and click it to activate your account\n\nOnce that's done, you can log in and start tracking orders right away.", "Account"],
            ["What does the warranty cover?", "Our warranty covers manufacturing defects including:\n\n• Structural issues\n• Material flaws\n• Broken parts during normal use\n\nIt does NOT cover:\n• Accidental damage\n• Misuse or improper assembly\n• Normal wear and tear\n• Damage from moving or transporting\n\nFor a detailed breakdown of the warranty process, visit our warranty page or contact support.", "Warranty"],
            ["How long is the delivery time?", "Delivery times vary by location and item:\n\n• Metro areas: 5-7 business days\n• Regional areas: 7-10 business days\n• Remote areas: 10-14 business days\n• Large/bulky items: add 2-3 extra days for freight scheduling\n• Custom/made-to-order: 3-6 weeks\n\nYou'll receive a tracking number via email once your order ships.", "Shipping"],
        ];
        
        $added = 0;
        foreach ($newFaqs as $newFaq) {
            $check = $conn->prepare("SELECT COUNT(*) FROM faqs WHERE question = ?");
            $check->execute([$newFaq[0]]);
            if ((int)$check->fetchColumn() === 0) {
                $seedFaq = $conn->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
                $seedFaq->execute($newFaq);
                $added++;
            }
        }
        if ($added > 0) {
            $report[] = "Added $added new technical support/account FAQs to existing set.";
        } else {
            $report[] = "All FAQs already exist.";
        }
    }

    // -----------------------------------------------------------
    // 14. Seed / default data
    //     A default admin account is created only if the `admins`
    //     table is completely empty, so this is safe to run on
    //     every request and won't reset a password you've changed.
    //
    //     Default login (change the password after first login):
    //       Email:    admin@woodcraftcare.com
    //       Password: Admin@123
    // -----------------------------------------------------------
    $adminCount = (int)$conn->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminCount === 0) {
        $defaultHash = password_hash("Admin@123", PASSWORD_DEFAULT);
        $seed = $conn->prepare("
            INSERT INTO admins (name, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $seed->execute(["WoodCraft Admin", "admin@woodcraftcare.com", $defaultHash, "Super Admin"]);
        $report[] = "Default admin account created (admin@woodcraftcare.com / Admin@123 — please change this password after logging in).";
    } else {
        $report[] = "Admin account(s) already exist — no default account needed.";
    }

} catch (PDOException $e) {
    $error = "Database initialization failed: " . $e->getMessage();

    if ($isDirectRequest) {
        http_response_code(500);
        echo "<h1>Database Setup Error</h1><p>" . htmlspecialchars($error) . "</p>";
        exit();
    }

    // When included by config.php, fail loudly too — the app can't
    // run without a working database connection.
    die($error);
}

// ---------------------------------------------------------------
// 11. Visual confirmation when this file is run directly.
//     When included by config.php, $conn is simply handed back to
//     the caller and nothing is printed.
// ---------------------------------------------------------------
if ($isDirectRequest) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Database Setup | WoodCraft Care</title>
        <style>
            body { font-family: system-ui, sans-serif; background: #F3F0E4; color: #2E1D14; padding: 40px; }
            .card { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
            h1 { font-size: 22px; margin-bottom: 4px; }
            p.sub { color: #6b7280; margin-top: 0; margin-bottom: 24px; }
            ul { list-style: none; padding: 0; margin: 0; }
            li { padding: 10px 14px; margin-bottom: 8px; background: #F8F6F2; border-radius: 10px; display: flex; align-items: center; gap: 10px; }
            li:before { content: "✓"; color: #2E7D32; font-weight: bold; }
            a.btn { display: inline-block; margin-top: 24px; padding: 10px 20px; background: #6B4226; color: #fff; text-decoration: none; border-radius: 999px; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Database setup complete</h1>
            <p class="sub">Database: <strong><?= htmlspecialchars($dbname) ?></strong> on <strong><?= htmlspecialchars($host) ?></strong></p>
            <ul>
                <?php foreach ($report as $line): ?>
                    <li><?= htmlspecialchars($line) ?></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn" href="../index.php">Go to the site →</a>
        </div>
    </body>
    </html>
    <?php
}
?>