<?php
session_start();
session_unset();
session_destroy();
header("Location: signin_admin.php");
exit();
