<?php
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $brands = [
        'Toyota' => 'assets/images/products/toyota.jpg',
        'Nissan' => 'assets/images/products/mazda-Mazda.jpg', // Temporary logo
        'Mitsubishi' => 'assets/images/products/subaru-logo.jpg', // Temporary logo
        'Koenigsegg' => 'assets/images/products/porche-Porsche.jpg', // Temporary logo
        'Dodge' => 'assets/images/products/ford-Ford.jpg', // Temporary logo
        'Mazda' => 'assets/images/products/mazda-Mazda.jpg',
        'Honda' => 'assets/images/products/toyota.jpg', // Temporary logo
        'Porsche' => 'assets/images/products/porche-Porsche.jpg',
        'Ford' => 'assets/images/products/ford-Ford.jpg',
        'Subaru' => 'assets/images/products/subaru-logo.jpg'
    ];

    $brandIds = [];
    foreach ($brands as $name => $logo) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO brands (name, logo_url) VALUES (?, ?)");
        $stmt->execute([$name, $logo]);
        $res = $db->prepare("SELECT id FROM brands WHERE name = ?");
        $res->execute([$name]);
        $brandIds[$name] = $res->fetchColumn();
    }

// Inventory Vehicles
    $inventory = [
        ['Toyota', 'Land Cruiser', '2025', 'Hybrid turbocharged 326 HP', '4WD', 'Robust off-road capability paired with premium comfort.', 'assets/images/products/landcruiser.png', 0, 1],
        ['Toyota', 'Corolla', '2025', '2.0L I4 Hybrid 169 HP', 'FWD', 'Efficient hybrid for dependable daily driving.', 'assets/images/products/corolla.png', 0, 1],
        ['Subaru', 'Outback', '2025', '2.5L Flat-4 182 HP', 'AWD', 'Rugged cargo capacity for active lifestyles.', 'assets/images/products/outback.png', 0, 1],
        ['Subaru', 'Forester', '2025', '2.5L Boxer Hybrid 180 HP', 'AWD', 'Responsive performance with all-terrain capability.', 'assets/images/products/forester.webp', 0, 1],
        ['Ford', 'Ranger', '2025', '2.3L EcoBoost 270 HP', '4WD', 'Built for work and light off-road use.', 'assets/images/products/ranger.webp', 0, 1],
        ['Ford', 'Everest', '2025', '3.0L V6 Turbodiesel 300 HP', '4WD', 'Capable family SUV with serious towing.', 'assets/images/products/everest.webp', 0, 1],
        ['Mazda', 'Axela', '2025', '2.0L I4 Turbodiesel 155 HP', 'FWD', 'Balanced handling and efficient performance.', 'assets/images/products/axela.webp', 0, 1],
        ['Mazda', 'CX-5', '2025', '2.5L I4 Turbo 227 HP', 'AWD', 'Comfortable interior with strong safety.', 'assets/images/products/cx5.jpg', 0, 1],
        ['Porsche', '911 Carrera GTS', '2025', '3.6L Flat-6 Turbo 478 HP', 'AWD', 'The definitive high-performance sports car.', 'assets/images/products/carrera gts.webp', 0, 1],
        ['Porsche', 'Cayenne', '2025', '4.0L V8 Twin-Turbo 493 HP', 'AWD', 'Porsche performance SUV.', 'assets/images/products/cayenne.webp', 0, 1],
    ];

    foreach ($inventory as $car) {
        $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, engine, drive, description, image_url, is_heritage, is_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$brandIds[$car[0]], $car[1], $car[2], $car[3], $car[4], $car[5], $car[6], $car[7], $car[8]]);
    }

    // Heritage vehicles
    $heritage = [
        ['Nissan', 'GT-R', '2024', 'VR38DETT V6 600+ hp', 'AWD', 'Japan\'s greatest sports car.', 'assets/images/gtr/r35-nissan-gt-r-1709580348.jpg', 1, 0],
        ['Mitsubishi', 'Lancer Evo', '2015', '4G63T 291 hp', 'AWD', 'Rally-bred perfection.', 'assets/images/lancer/evo 10.jpg', 1, 0],
        ['Toyota', 'Supra', '2024', '2JZ-GTE 320+ hp', 'RWD', 'Pure sports car soul.', 'assets/images/supra/2020-supra-7.jpg', 1, 0],
        ['Dodge', 'Charger', '2024', 'HEMI V8 485+ hp', 'RWD', 'American muscle icon.', 'assets/images/charger/1969_Dodge_Charger_green_I.jpg', 1, 0],
        ['Mazda', 'RX-7', '2002', '13B Rotary 255 hp', 'RWD', 'The rotary legend.', 'assets/images/mazda/mazda-rx-7-fd.jpg', 1, 0],
        ['Honda', 'NSX', '2022', 'V6 Hybrid 573 hp', 'AWD', 'The Japanese supercar.', 'assets/images/nsx/HONDA_NSX-2016_main.jpg', 1, 0],
        ['Koenigsegg', 'Jesko', '2024', 'V8 Twin-Turbo 1600 hp', 'RWD', 'Hypercar engineering.', 'assets/images/koenigsegg/Jesko.jpg', 1, 0],
];

    foreach ($heritage as $car) {
        $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, engine, drive, description, image_url, is_heritage, is_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$brandIds[$car[0]], $car[1], $car[2], $car[3], $car[4], $car[5], $car[6], $car[7], $car[8]]);
    }

    echo "Database seeded successfully.\n";
} catch (PDOException $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
}
?>
