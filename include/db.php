<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>