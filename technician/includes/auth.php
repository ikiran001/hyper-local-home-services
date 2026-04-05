<?php
declare(strict_types=1);

/**
 * Technician session guard — phone login, no password (MVP).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['technician_id'])) {
    header('Location: login.php', true, 302);
    exit;
}
