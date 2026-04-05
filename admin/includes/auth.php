<?php
declare(strict_types=1);

/**
 * Admin session guard — include after db.php on protected admin pages.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php', true, 302);
    exit;
}
