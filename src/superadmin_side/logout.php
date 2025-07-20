<?php
session_start();
unset($_SESSION['superadmin_id']);
unset($_SESSION['superadmin_user_name']);
unset($_SESSION['admin_role']);

header("Location: login.php");
exit();
