<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/session_helpers.php';

destroy_session_fully();

header('Location: ' . url(''));
exit;
