<?php

namespace Barberry\Plugin\Csv\Test;

class MainIntegrationTest extends TestCase
{
    public function testBarberryMainRequiresCsvPlugin(): void
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../barberry-main/composer.json'), true);

        self::assertSame('dev-master', $composer['require']['barberry/plugin-csv'] ?? null);
    }
}
