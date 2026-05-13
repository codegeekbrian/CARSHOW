<?php
require_once __DIR__ . '/admin_db.php';

logout();
header('Location: login.php');
exit();