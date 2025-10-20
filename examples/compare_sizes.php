<?php
require __DIR__ . '/../vendor/autoload.php';
use Nishadil\ImageTinify\ImageTinify;

$src = __DIR__ . '/../tests/fixtures/test.png';
$out = __DIR__ . '/../tests/_output/manual-tinified.png';
@mkdir(dirname($out), 0775, true);

$tiny = new ImageTinify();
try {
    $ok = $tiny->compress($src, $out, ['quality' => 75, 'mode' => 'lossy']);
    echo 'OK: ' . ($ok ? 'yes' : 'no') . PHP_EOL;
    echo 'Original: ' . filesize($src) . ' bytes' . PHP_EOL;
    echo 'Output:   ' . (file_exists($out) ? filesize($out) : 'n/a') . ' bytes' . PHP_EOL;
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
