<?php
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Brands Table
    $db->exec("CREATE TABLE IF NOT EXISTS brands (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        logo_url TEXT
    )");

    // Create Vehicles Table
    $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        brand_id INTEGER,
        model TEXT NOT NULL,
        year TEXT,
        engine TEXT,
        power TEXT,
        drive TEXT,
        description TEXT,
        image_url TEXT,
        is_heritage BOOLEAN DEFAULT 0,
        is_inventory BOOLEAN DEFAULT 0,
        FOREIGN KEY (brand_id) REFERENCES brands(id)
    )");

    // Create Heritage Details Table (for the timeline/history blocks)
    $db->exec("CREATE TABLE IF NOT EXISTS heritage_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehicle_id INTEGER,
        generation_name TEXT,
        years TEXT,
        history_text TEXT,
        image_url TEXT,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    )");

    echo "Database initialized successfully.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
