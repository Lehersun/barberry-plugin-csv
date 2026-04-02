<?php

namespace Barberry\Plugin\Csv\Test;

use Barberry\ContentType;
use Barberry\Direction\Composer;
use Barberry\Exception\AmbiguousPluginCommand;
use Barberry\Monitor\Composer as MonitorComposer;
use Barberry\Plugin\Csv\Installer;
use PHPUnit\Framework\TestCase;

class InstallerTest extends TestCase
{
    private static $directionDir;

    public static function setUpBeforeClass(): void
    {
        $baseDir = sys_get_temp_dir() . '/barberry-plugin-csv-' . uniqid('', true);
        mkdir($baseDir, 0777, true);
        self::$directionDir = $baseDir . '/directions/';
        $monitorDir = $baseDir . '/monitors/';
        mkdir(self::$directionDir, 0777, true);
        mkdir($monitorDir, 0777, true);

        $installer = new Installer();
        $installer->install(new Composer(self::$directionDir, sys_get_temp_dir()), new MonitorComposer($monitorDir, sys_get_temp_dir()));

        require_once self::$directionDir . 'CsvToCsv.php';
    }

    public function testInstallerGeneratesDirectionResolvedByFactory(): void
    {
        $direction = new \Barberry\Direction\DirectionCsvToCsv('comma');

        self::assertInstanceOf('Barberry\\Direction\\DirectionCsvToCsv', $direction);
        self::assertSame("a,b\n", $direction->convert("a;b\r\n"));
    }

    public function testGeneratedDirectionRejectsMalformedCommand(): void
    {
        $this->expectException(AmbiguousPluginCommand::class);
        new \Barberry\Direction\DirectionCsvToCsv('bad_command');
    }
}
