<?php
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS brands (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        logo_url TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS vehicles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        brand_id INTEGER,
        model TEXT NOT NULL,
        year TEXT,
        price REAL,
        engine TEXT,
        power TEXT,
        drive TEXT,
        description TEXT,
        image_url TEXT,
        is_featured BOOLEAN DEFAULT 0,
        is_legacy BOOLEAN DEFAULT 0,
        is_inventory BOOLEAN DEFAULT 0,
        FOREIGN KEY (brand_id) REFERENCES brands(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS legacy_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vehicle_id INTEGER,
        generation_name TEXT,
        years TEXT,
        history_text TEXT,
        image_url TEXT,
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS analytics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type TEXT NOT NULL, -- 'view' or 'click'
        target_id INTEGER, -- vehicle_id or NULL
        page_url TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Database initialized successfully.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}