<?php
require_once 'db_admin.php';

logoutAdmin();
header('Location: login.php');
exit();
?>