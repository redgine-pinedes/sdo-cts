<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/includes/auth.php';

$auth = auth();
$auth->logout();

header('Location: /SDO-cts/admin/login.php');
exit;

