<?php
/**
 * includes/restrict-customer.php
 * ------------------------------------------------------------------
 * Include this (after session_start() + config.php) on every page
 * that is NOT part of the logged-in customer's allowed feature set.
 *
 * Once a customer logs in, they are only allowed to access
 * my-profile.php and its related customer features:
 *   my-profile.php, edit-profile.php, change-password.php,
 *   submit-ticket.php, warranty-request.php, returns-refund.php,
 *   live-chat.php, feedback.php, logout.php
 *
 * Any other page (home, FAQ, contact support, about, register,
 * login, articles, etc.) redirects a logged-in customer straight
 * back to their profile. Logging out clears $_SESSION['user_id'],
 * so this guard stops applying and the site returns to normal.
 *
 * Same $baseUrl convention as includes/header.php / footer.php:
 *   - Pages in the project root:      $baseUrl = '';
 *   - Pages one folder deep (e.g.
 *     /articles/*.php):               $baseUrl = '../';
 * ------------------------------------------------------------------
 */

if (isset($_SESSION['user_id'])) {
    $redirectBase = isset($baseUrl) ? $baseUrl : '';
    header("Location: {$redirectBase}my-profile.php");
    exit();
}
