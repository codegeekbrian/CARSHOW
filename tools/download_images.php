<?php
/**
 * Image Downloader Utility
 */
$images = [
    's580.jpg'        => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=1000&auto=format&fit=crop',
    'm3.jpg'          => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?q=80&w=1000&auto=format&fit=crop',
    'rs6.jpg'         => 'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?q=80&w=1000&auto=format&fit=crop',
    'defender110.jpg' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?q=80&w=1000&auto=format&fit=crop',
    'lx600.jpg'       => 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?q=80&w=1000&auto=format&fit=crop',
    'x5.jpg'          => 'https://images.unsplash.com/photo-1556189250-72ba954cfc2b?q=80&w=1000&auto=format&fit=crop',
    'gle450.jpg'      => 'https://images.unsplash.com/photo-1583121274602-3e2820c69888?q=80&w=1000&auto=format&fit=crop',
    'q7.jpg'          => 'https://images.unsplash.com/photo-1614162692292-7ac56d7f7f1e?q=80&w=1000&auto=format&fit=crop',
    'rx500h.jpg'      => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?q=80&w=1000&auto=format&fit=crop',
    'rrsport.jpg'     => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?q=80&w=1000&auto=format&fit=crop',
    'prado.jpg'       => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?q=80&w=1000&auto=format&fit=crop', // generic SUV
    'hilux.jpg'       => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?q=80&w=1000&auto=format&fit=crop', // generic pickup
];

$dir = __DIR__ . '/../assets/images/products/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

foreach ($images as $filename => $url) {
    echo "Downloading $filename... ";
    $content = @file_get_contents($url);
    if ($content) {
        file_put_contents($dir . $filename, $content);
        echo "Done.\n";
    } else {
        echo "Failed.\n";
    }
}
