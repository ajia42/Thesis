<?php
session_start();
unset($_SESSION['admin_id']);
unset($_SESSION['admin_user_name']);

header("Location: signin_admin.php");
exit();
