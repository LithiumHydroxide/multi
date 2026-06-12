<?php
require_once '../../config.php';
session_unset();
session_destroy();

// Redirect to login
header('Location: /multi/views/auth/login.php');
exit;
?>