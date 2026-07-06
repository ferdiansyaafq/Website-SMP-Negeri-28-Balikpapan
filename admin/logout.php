<?php
require_once '../includes/admin_auth.php';

logoutAdmin();
header('Location: login.php');
exit;
