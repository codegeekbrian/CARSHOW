<?php
try {
    $db = new PDO('sqlite:data/carshow.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $details = [
        'GT-R' => [
            ['PGC10 — The First GT-R', '1969–1972', 'The first-generation GT-R produced 160 hp from a 2.0L inline-six engine. It dominated Japanese touring car racing and laid the blueprint for every GT-R that followed.', 'assets/images/gtr/hakosuka-the-first-nissan-gt-r-1709580348.jpg'],
            ['KPGC110 — The Second Generation', '1973', 'The KPGC110 brought improved aerodynamics and refined performance, but the oil crisis halted production in 1973 after just 197 units.', 'assets/images/gtr/c110-nissan-skyline-gt-r-1709580348.jpg'],
            ['R32 — Godzilla is Born', '1989–1994', 'The R32 with RB26DETT 2.6L twin-turbo producing 276 hp, won 29 consecutive Group A races.', 'assets/images/gtr/godzilla-is-born-the-r32-nissan-skyline-gt-r-1709580348.jpg'],
            ['R33 — The Underdog', '1995–1998', 'The R33 improved aerodynamics with a stiffer chassis and set a sub-8-minute Nürburgring lap.', 'assets/images/gtr/the-underloved-r33-skyline-gt-r-1709580348.jpg'],
            ['R34 — The Icon', '1999–2002', 'The R34 is widely regarded as the pinnacle of the Skyline GT-R with 320+ hp.', 'assets/images/gtr/r34-nissan-skyline-gt-r-1709580348.jpg'],
            ['R35 — The Modern Monster', '2007–Present', 'The R35 introduced a 3.8L VR38DETT twin-turbo V6 producing 480–600+ hp.', 'assets/images/gtr/r35-nissan-gt-r-1709580348.jpg'],
        ],
        'Lancer Evo' => [
            ['Evolution III — The Rally Legend', '1996–2000', 'The Evolution III brought WRC dominance to the streets with an aggressive aero kit.', 'assets/images/lancer/evo-iii.jpg'],
            ['Evolution VI — The Peak', '1999–2001', 'Widely considered the most balanced of the original Evo series.', 'assets/images/lancer/evo-vi.jpg'],
            ['Evolution X — The Modern Era', '2007–2015', 'A complete redesign moving to a 4B11T aluminum engine.', 'assets/images/lancer/evo 10.jpg'],
        ],
        'Supra' => [
            ['A70 — The Grand Tourer', '1986–1993', 'A shift towards luxury and style, introducing the Supra as a sophisticated GT.', 'assets/images/supra/a70.jpg'],
            ['A80 — The 2JZ Legend', '1993–2002', 'The most iconic Supra ever, featuring the legendary 2JZ-GTE engine.', 'assets/images/supra/a80.jpg'],
            ['GR Supra — The Return', '2019–Present', 'Born from a partnership with BMW, the GR Supra returns with a turbocharged inline-six.', 'assets/images/supra/2020-supra-7.jpg'],
        ],
        'Charger' => [
            ['1969 — The Muscle King', '1968–1970', 'The definitive muscle car, defined by its coke-bottle styling.', 'assets/images/charger/1969_Dodge_Charger_green_I.jpg'],
            ['Modern Era — The 4-Door Beast', '2015–Present', 'Merging American muscle with four-door practicality.', 'assets/images/charger/modern-charger.jpg'],
        ],
        'RX-7' => [
            ['SA22C — The Beginning', '1978–1985', 'Introducing the lightweight rotary layout that defined Mazda\'s sports car DNA.', 'assets/images/mazda/sa22c.jpg'],
            ['FD — The Masterpiece', '1992–2002', 'Regarded as one of the most beautiful cars ever made with a sequential twin-turbo 13B rotary.', 'assets/images/mazda/mazda-rx-7-fd.jpg'],
        ],
        'NSX' => [
            ['NA1 — The Ferrari Killer', '1990–2005', 'Developed with input from Ayrton Senna, proved a supercar could be reliable.', 'assets/images/nsx/na1.jpg'],
            ['NC1 — The Hybrid Future', '2016–2022', 'A total rethink using three electric motors and a twin-turbo V6.', 'assets/images/nsx/HONDA_NSX-2016_main.jpg'],
        ],
        'Jesko' => [
            ['Jesko — The Absolute', '2020–Present', 'An engineering marvel with a 5.0L V8 and a 9-speed multi-clutch transmission.', 'assets/images/koenigsegg/Jesko.jpg'],
        ],
    ];

    foreach ($details as $model => $genList) {
        $stmt = $db->prepare("SELECT id FROM vehicles WHERE model = ? AND is_heritage = 1 LIMIT 1");
        $stmt->execute([$model]);
        $vehicleId = $stmt->fetchColumn();

        if ($vehicleId) {
            foreach ($genList as $gen) {
                $insert = $db->prepare("INSERT INTO heritage_details (vehicle_id, generation_name, years, history_text, image_url) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$vehicleId, $gen[0], $gen[1], $gen[2], $gen[3]]);
            }
        }
    }

    echo "Heritage details seeded successfully.\n";
} catch (PDOException $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
}
?>
