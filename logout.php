<?php
session_start();
unset($_SESSION['portal_user_id'], $_SESSION['portal_role'], $_SESSION['portal_display_name'], $_SESSION['portal_login_time']);
header('Location: index.php');
exit;