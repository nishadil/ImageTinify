<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Nishadil\ImageTinify\ImageTinify;

final class CompressorTest extends TestCase
{
    public function testCompressDoesNotThrow(): void
    {
        $src = __DIR__ . '/fixtures/test.jpg';
        $out = sys_get_temp_dir() . '/test-tinified.jpg';

        if (!file_exists($src)) {
            $this->markTestSkipped('Fixture not present: ' . $src);
        }

        $tin = new ImageTinify();
        $ok = $tin->compress($src, $out, ['quality' => 75, 'mode' => 'lossy']);

        $this->assertTrue($ok);
        $this->assertFileExists($out);
        @unlink($out);
    }
}
