<?php
/**
 * fe/pages/index.php
 *
 * Entry point. Delegates role-based redirect logic to the web router.
 * No business logic lives here.
 */

header('Location: ../../be/web.php?action=dispatch');
exit;
