<?php
session_start();
unset($_SESSION['registered_phone']);
unset($_SESSION['user_name']);

header("Location: user_login.php");
exit();
