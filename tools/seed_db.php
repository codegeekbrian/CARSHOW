<?php
/**
 * Database Seeder
 * Populates brands, inventory vehicles, and legacy vehicles.
 */
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DELETE FROM legacy_details");
    $db->exec("DELETE FROM vehicles");
    $db->exec("DELETE FROM brands");

    $brands = [
        'Toyota'        => 'assets/images/products/toyota.jpg',
        'Nissan'        => 'assets/images/products/sky.jpg',
        'Mitsubishi'    => 'assets/images/products/subaru-logo.jpg',
        'Koenigsegg'    => 'assets/images/products/porche-Porsche.jpg',
        'Dodge'         => 'assets/images/products/ford-Ford.jpg',
        'Mazda'         => 'assets/images/products/mazda-Mazda.jpg',
        'Honda'         => 'assets/images/products/toyota.jpg',
        'Porsche'       => 'assets/images/products/porche-Porsche.jpg',
        'Ford'          => 'assets/images/products/ford-Ford.jpg',
        'Subaru'        => 'assets/images/products/subaru-logo.jpg',
        'Mercedes-Benz' => 'assets/images/products/sky.jpg', // Placeholder
        'BMW'           => 'assets/images/products/sky.jpg', // Placeholder
        'Audi'          => 'assets/images/products/sky.jpg', // Placeholder
        'Lexus'         => 'assets/images/products/toyota.jpg', // Placeholder
        'Land Rover'    => 'assets/images/products/toyota.jpg', // Placeholder
        'Volkswagen'    => 'assets/images/products/sky.jpg'  // Placeholder
    ];

    $brandIds = [];
    foreach ($brands as $name => $logo) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO brands (name, logo_url) VALUES (?, ?)");
        $stmt->execute([$name, $logo]);
        $res = $db->prepare("SELECT id FROM brands WHERE name = ?");
        $res->execute([$name]);
        $brandIds[$name] = $res->fetchColumn();
    }

    // Inventory vehicles (is_legacy=0, is_inventory=1)
    // [Brand, Model, Year, Price, Engine, Power, Drive, Description, Image, Featured]
    $inventory = [
        ['Toyota', 'Land Cruiser 300', '2025', 125000, '3.3L V6 Twin-Turbo Diesel', '304 HP', '4WD', 'The ultimate off-road king with luxury features.', 'assets/images/products/landcruiser.png', 1],
        ['Toyota', 'Prado', '2024', 85000, '2.8L I4 Turbo Diesel', '201 HP', '4WD', 'Versatile, reliable, and perfect for Kenyan roads.', 'assets/images/products/landcruiser.png', 0],
        ['Toyota', 'Corolla Cross', '2025', 35000, '1.8L I4 Hybrid', '121 HP', 'FWD', 'Efficient and stylish urban crossover.', 'assets/images/products/corolla.png', 0],
        ['Toyota', 'Hilux GR-S', '2025', 65000, '2.8L I4 Turbo Diesel', '221 HP', '4WD', 'Rugged performance with Gazoo Racing styling.', 'assets/images/products/ranger.webp', 1],
        
        ['Subaru', 'Outback XT', '2025', 48000, '2.4L Turbocharged Boxer', '260 HP', 'AWD', 'Adventure-ready with turbocharged power.', 'assets/images/products/outback.png', 1],
        ['Subaru', 'Forester', '2025', 38000, '2.5L Boxer', '182 HP', 'AWD', 'The perfect family companion for any terrain.', 'assets/images/products/forester.webp', 0],
        ['Subaru', 'Crosstrek', '2025', 32000, '2.0L Boxer Hybrid', '150 HP', 'AWD', 'Compact, capable, and highly efficient.', 'assets/images/products/forester.webp', 0],
        
        ['Mercedes-Benz', 'S-Class S580', '2025', 145000, '4.0L V8 Biturbo', '496 HP', 'AWD', 'The pinnacle of automotive luxury and technology.', 'assets/images/products/sky.jpg', 1],
        ['Mercedes-Benz', 'GLE 450', '2025', 92000, '3.0L I6 Turbo', '362 HP', 'AWD', 'Sophisticated SUV for families on the move.', 'assets/images/products/cayenne.webp', 0],
        ['Mercedes-Benz', 'C-Class C300', '2024', 58000, '2.0L I4 Turbo', '255 HP', 'RWD', 'Sporty, elegant, and packed with innovation.', 'assets/images/products/sky.jpg', 0],
        
        ['BMW', 'X5 xDrive40i', '2025', 88000, '3.0L I6 Turbo', '375 HP', 'AWD', 'The definitive sports activity vehicle.', 'assets/images/products/cayenne.webp', 0],
        ['BMW', 'M3 Competition', '2024', 95000, '3.0L I6 Twin-Turbo', '503 HP', 'AWD', 'Track-ready performance in a 4-door package.', 'assets/images/products/sky.jpg', 1],
        ['BMW', '330i', '2024', 55000, '2.0L I4 Turbo', '255 HP', 'RWD', 'The benchmark for sports sedans.', 'assets/images/products/sky.jpg', 0],
        
        ['Audi', 'Q7 55 TFSI', '2025', 82000, '3.0L V6 Turbo', '335 HP', 'AWD', 'Luxury 7-seater with legendary Quattro handling.', 'assets/images/products/cayenne.webp', 0],
        ['Audi', 'RS6 Avant', '2025', 135000, '4.0L V8 Twin-Turbo', '591 HP', 'AWD', 'High-performance wagon with supercar speed.', 'assets/images/products/sky.jpg', 1],
        
        ['Lexus', 'LX 600', '2025', 130000, '3.4L V6 Twin-Turbo', '409 HP', '4WD', 'Ultra-luxury off-roader with unparalleled comfort.', 'assets/images/products/landcruiser.png', 0],
        ['Lexus', 'RX 500h', '2025', 78000, '2.4L Turbo Hybrid', '366 HP', 'AWD', 'Dynamic performance meets hybrid efficiency.', 'assets/images/products/cayenne.webp', 0],
        
        ['Land Rover', 'Defender 110', '2025', 98000, '3.0L I6 MHEV', '395 HP', '4WD', 'Reimagined icon for 21st-century adventure.', 'assets/images/products/landcruiser.png', 1],
        ['Land Rover', 'Range Rover Sport', '2025', 115000, '3.0L I6 MHEV', '355 HP', '4WD', 'Dynamic luxury and exceptional capability.', 'assets/images/products/cayenne.webp', 0],
        
        ['Ford', 'Everest Titanium', '2025', 72000, '3.0L V6 Diesel', '247 HP', '4WD', 'Sophisticated SUV built for the toughest trails.', 'assets/images/products/everest.webp', 0],
        ['Ford', 'Ranger Raptor', '2025', 85000, '3.0L V6 Twin-Turbo', '392 HP', '4WD', 'High-performance desert racing pickup.', 'assets/images/products/ranger.webp', 1],
        
        ['Mazda', 'CX-60', '2025', 62000, '3.3L I6 Turbo', '280 HP', 'AWD', 'Premium styling and refined driving dynamics.', 'assets/images/products/cx5.jpg', 0],
        ['Mazda', 'Mazda3', '2024', 28000, '2.0L I4', '186 HP', 'FWD', 'Beautifully crafted with engaging performance.', 'assets/images/products/axela.webp', 0],
        
        ['Porsche', '911 Turbo S', '2025', 235000, '3.7L Flat-6 Twin-Turbo', '640 HP', 'AWD', 'The ultimate everyday supercar.', 'assets/images/products/carrera gts.webp', 1],
        ['Porsche', 'Macan GTS', '2025', 88000, '2.9L V6 Twin-Turbo', '434 HP', 'AWD', 'Compact SUV with true sports car DNA.', 'assets/images/products/cayenne.webp', 0],
    ];

    foreach ($inventory as $car) {
        $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, price, engine, power, drive, description, image_url, is_featured, is_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $brandIds[$car[0]], // brand_id
            $car[1],           // model
            $car[2],           // year
            $car[3],           // price
            $car[4],           // engine
            $car[5],           // power
            $car[6],           // drive
            $car[7],           // description
            $car[8],           // image_url
            $car[9]            // is_featured
        ]);
    }

    // Legacy vehicles (is_legacy=1, is_inventory=0)
    $legacy = [
        ['Nissan',     'GT-R',        '2024', 'VR38DETT V6 600+ hp',       'AWD', "Japan's greatest sports car.",     'assets/images/gtr/r35-nissan-gt-r-1709580348.jpg',       1, 0],
        ['Mitsubishi', 'Lancer Evo',  '2015', '4G63T 291 hp',              'AWD', 'Rally-bred perfection.',          'assets/images/lancer/evo 10.jpg',                         1, 0],
        ['Toyota',     'Supra',       '2024', '2JZ-GTE 320+ hp',            'RWD', 'Pure sports car soul.',          'assets/images/supra/2020-supra-7.jpg',                   1, 0],
        ['Dodge',      'Charger',     '2024', 'HEMI V8 485+ hp',            'RWD', 'American muscle icon.',           'assets/images/charger/1969_Dodge_Charger_green_I.jpg',  1, 0],
        ['Mazda',      'RX-7',        '2002', '13B Rotary 255 hp',           'RWD', 'The rotary legend.',              'assets/images/mazda/mazda-rx-7-fd.jpg',                 1, 0],
        ['Honda',      'NSX',         '2022', 'V6 Hybrid 573 hp',           'AWD', 'The Japanese supercar.',          'assets/images/nsx/HONDA_NSX-2016_main.jpg',             1, 0],
        ['Koenigsegg', 'Jesko',       '2024', 'V8 Twin-Turbo 1600 hp',     'RWD', 'Hypercar engineering.',           'assets/images/koenigsegg/Jesko.jpg',                    1, 0],
    ];

    foreach ($legacy as $car) {
        $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, engine, drive, description, image_url, is_legacy, is_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$brandIds[$car[0]], $car[1], $car[2], $car[3], $car[4], $car[5], $car[6], $car[7], $car[8]]);
    }

    echo "Database seeded successfully.\n";
} catch (PDOException $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
}