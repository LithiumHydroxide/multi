<?php
require_once '../../config.php';
SuperAdminMiddleware::stopImpersonation();
header('Location: /multi/views/super_admin/dashboard.php');
exit;
?>