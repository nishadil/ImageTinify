<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Nishadil\ImageTinify\ImageTinify;

final class CompressorTest extends TestCase
{
    public function testCompressDoesNotThrow(): void
    {
        $src = __DIR__ . '/fixtures/test.jpg';
        $outDir = __DIR__ . '/_output';
        if (!is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }
        $out = $outDir . '/test-tinified.jpg';

        if (!file_exists($src)) {
            $this->markTestSkipped('Fixture not present: ' . $src);
        }

        $tin = new ImageTinify();
        $ok = $tin->compress($src, $out, ['quality' => 75, 'mode' => 'lossy']);

        $this->assertTrue($ok, 'ImageTinify->compress returned false');
        $this->assertFileExists($out, 'Output file was not created: ' . $out);

        // Do NOT delete $out so you can inspect it
        // Optionally print the path so it's visible when running tests
        fwrite(STDOUT, PHP_EOL . 'Tinified image path: ' . realpath($out) . PHP_EOL);
    }
}
