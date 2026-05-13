<?php
/**
 * Legacy Details Seeder
 * Populates generation/timeline data for legacy vehicles.
 */
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("DELETE FROM legacy_details");

    $details = [
        'GT-R' => [
            ['PGC10 — The First GT-R', '1969–1972',
                'The first-generation GT-R produced 160 hp from a 2.0L inline-six engine. It dominated Japanese touring car racing and laid the blueprint for every GT-R that followed.',
                'assets/images/gtr/hakosuka-the-first-nissan-gt-r-1709580348.jpg'],
            ['KPGC110 — The Second Generation', '1973',
                'The KPGC110 brought improved aerodynamics and refined performance, but the oil crisis halted production in 1973 after just 197 units.',
                'assets/images/gtr/c110-nissan-skyline-gt-r-1709580348.jpg'],
            ['R32 — Godzilla is Born', '1989–1994',
                'The R32 with RB26DETT 2.6L twin-turbo producing 276 hp, won 29 consecutive Group A races.',
                'assets/images/gtr/godzilla-is-born-the-r32-nissan-skyline-gt-r-1709580348.jpg'],
            ['R33 — The Underdog', '1995–1998',
                'The R33 improved aerodynamics with a stiffer chassis and set a sub-8-minute Nürburgring lap.',
                'assets/images/gtr/the-underloved-r33-skyline-gt-r-1709580348.jpg'],
            ['R34 — The Icon', '1999–2002',
                'The R34 is widely regarded as the pinnacle of the Skyline GT-R with 320+ hp.',
                'assets/images/gtr/r34-nissan-skyline-gt-r-1709580348.jpg'],
            ['R35 — The Modern Monster', '2007–Present',
                'The R35 introduced a 3.8L VR38DETT twin-turbo V6 producing 480–600+ hp.',
                'assets/images/gtr/r35-nissan-gt-r-1709580348.jpg'],
        ],
        'Lancer Evo' => [
            ['Evolution IV — The AWD Master', '1996–1998',
                'The Evo IV introduced Active Yaw Control (AYC) and became the dominant WRC machine of the late 90s, powered by the legendary 4G63T engine.',
                'assets/images/lancer/mitsubishi-lancer-evolution-iv.jpg'],
            ['Evolution VI — The Peak', '1999–2001',
                'Widely considered the most balanced of the series. The Tommi Mäkinen Edition (TME) remains the most sought-after collector model.',
                'assets/images/lancer/2000_Mitsubishi_Lancer_Evolution_VI_TME.jpg'],
            ['Evolution IX — The MIVEC Era', '2005–2007',
                'The first Evo to feature MIVEC variable valve timing, providing improved response and power across the entire RPM range.',
                'assets/images/lancer/mitsubishi-lancer-evolution-ix.jpg'],
            ['Evolution X — The Final Chapter', '2007–2015',
                'A complete redesign moving to the 4B11T aluminum engine, merging modern safety and tech with rally-bred performance.',
                'assets/images/lancer/evo 10.jpg'],
        ],
        'Supra' => [
            ['A70 — The Sophisticated GT', '1986–1993',
                'The A70 moved the Supra into its own chassis, introducing the powerful 7M-GTE turbocharged engine and a focus on grand touring comfort.',
                'assets/images/supra/-1983-supra-2-.jpg'],
            ['A80 — The 2JZ Legend', '1993–2002',
                'The most iconic Japanese sports car of the 90s. The bulletproof 2JZ-GTE engine made it a favorite for tuners, capable of 1,000+ hp.',
                'assets/images/supra/1998-supra-turbo-2.jpg'],
            ['GR Supra — The Rebirth', '2019–Present',
                'Co-developed with BMW, the GR Supra returns with a modern turbocharged inline-six and precision-tuned handling.',
                'assets/images/supra/2020-supra-7.jpg'],
        ],
        'Charger' => [
            ['1969 — The Muscle Icon', '1968–1970',
                'Defined by the "Coke Bottle" profile and hidden headlights. The 440 Magnum and 426 Hemi made it a street-racing legend.',
                'assets/images/charger/1969_Dodge_Charger_green_I.jpg'],
            ['2015+ — The Hellcat Revolution', '2015–2023',
                'Dodge redefined the modern muscle car with the 707 hp SRT Hellcat, proving that raw American power was still king.',
                'assets/images/charger/2015_Dodge_Charger_SRT_392_with_Scat_Pack.jpg'],
        ],
        'RX-7' => [
            ['SA22C — The Lightweight Pioneer', '1978–1985',
                'The original RX-7 proved that a small, light rotary engine could outperform larger, heavier V8 competitors.',
                'assets/images/mazda/mazda sa22c.jpg'],
            ['FC — The Turbo II Refinement', '1985–1991',
                'The second generation added the Turbo II variant, offering significant power gains and improved chassis dynamics over the SA22C.',
                'assets/images/mazda/mazda-rx-7-fc.jpg'],
            ['FD — The Sequential Masterpiece', '1992–2002',
                'Widely considered one of the most beautiful cars ever made. It featured the first-ever mass-produced sequential twin-turbo 13B rotary.',
                'assets/images/mazda/mazda-rx-7-fd.jpg'],
        ],
        'NSX' => [
            ['NA1 — The Supercar Disruptor', '1990–2005',
                'Developed with input from Ayrton Senna, the all-aluminum NSX proved that supercars could be reliable and easy to drive daily.',
                'assets/images/nsx/HONDA_NSX-1997_main.jpg'],
            ['NC1 — The Hybrid Successor', '2016–2022',
                'A total rethink of the supercar formula, utilizing three electric motors and a twin-turbo V6 for instantaneous performance.',
                'assets/images/nsx/HONDA_NSX-2016_main.jpg'],
        ],
        'Jesko' => [
            ['Agera R — The Speed Record Setter', '2011–2014',
                'The Agera R set several world records for acceleration and braking, pushing the boundaries of hypercar engineering.',
                'assets/images/koenigsegg/Koenigsegg-Agera-R-20-770x512.jpg'],
            ['Jesko — The Absolute Peak', '2020–Present',
                'Named after the founder\'s father, the Jesko features a 5.0L V8 producing 1,600 hp and the revolutionary Light Speed Transmission.',
                'assets/images/koenigsegg/Jesko.jpg'],
        ],
    ];

    foreach ($details as $model => $genList) {
        $stmt = $db->prepare("SELECT id FROM vehicles WHERE model = ? AND is_legacy = 1 LIMIT 1");
        $stmt->execute([$model]);
        $vehicleId = $stmt->fetchColumn();

        if ($vehicleId) {
            foreach ($genList as $gen) {
                $insert = $db->prepare("INSERT INTO legacy_details (vehicle_id, generation_name, years, history_text, image_url) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$vehicleId, $gen[0], $gen[1], $gen[2], $gen[3]]);
            }
            echo "Seeded {$model}: " . count($genList) . " generations\n";
        } else {
            echo "Vehicle '{$model}' not found (is_legacy=1)\n";
        }
    }

    echo "\nLegacy details seeded successfully.\n";
} catch (PDOException $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
}