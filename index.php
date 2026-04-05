<?php
declare(strict_types=1);

/**
 * Entry point — sends visitors to the customer homepage.
 */
header('Location: customer/index.php', true, 302);
exit;
