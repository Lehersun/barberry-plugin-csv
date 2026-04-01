<?php

namespace Barberry\Plugin\Csv\Test;

use Barberry\ContentType;
use Barberry\Direction\Factory;
use Barberry\Plugin\Csv\Installer;

class InstallerTest extends TestCase
{
    public function testInstallerGeneratesDirectionResolvedByFactory(): void
    {
        $baseDir = sys_get_temp_dir() . '/barberry-plugin-csv-' . uniqid('', true);
        mkdir($baseDir, 0777, true);
        $directionDir = $baseDir . '/directions/';
        $monitorDir = $baseDir . '/monitors/';
        mkdir($directionDir, 0777, true);
        mkdir($monitorDir, 0777, true);

        $installer = new Installer();
        $installer->install(new FakeDirectionComposer($directionDir), new FakeMonitorComposer());

        require_once $directionDir . 'CsvToCsv.php';
        require_once __DIR__ . '/../../Barberry/library/Barberry/Direction/Factory.php';

        $direction = (new Factory())->direction(ContentType::csv(), ContentType::csv(), 'comma');

        self::assertInstanceOf('Barberry\\Direction\\DirectionCsvToCsv', $direction);
        self::assertSame("a,b\n", $direction->convert("a;b\r\n"));
    }
}

class FakeDirectionComposer implements \Barberry\Direction\ComposerInterface
{
    private string $directionDir;

    public function __construct(string $directionDir)
    {
        $this->directionDir = rtrim($directionDir, '/') . '/';
    }

    public function writeClassDeclaration($sourceContentType, $destinationContentType, $converter, $command)
    {
        $file = $this->directionDir . ucfirst($sourceContentType->standardExtension()) . 'To' . ucfirst($destinationContentType->standardExtension()) . '.php';
        $code = <<<'PHP'
<?php
namespace Barberry\Direction;

use Barberry\ContentType;
use Barberry\Exception\ConversionNotPossible;
use Barberry\Plugin;

class DirectionCsvToCsv implements Plugin\InterfaceConverter
{
    private $converter;
    private $command;

    public function __construct($commandPart = null)
    {
        $this->converter = (new Plugin\Csv\Converter())->configure(ContentType::csv(), sys_get_temp_dir());
        $this->command = (new Plugin\Csv\Command())->configure((string) $commandPart);
        if (!$this->command->conforms((string) $commandPart)) {
            throw new ConversionNotPossible('Malformed CSV command.');
        }
    }

    public function configure(ContentType $targetContentType, $tempPath)
    {
        $this->converter->configure($targetContentType, $tempPath);
        return $this;
    }

    public function convert($bin, ?Plugin\InterfaceCommand $command = null)
    {
        return $this->converter->convert($bin, $command ?: $this->command);
    }
}
PHP;
        file_put_contents($file, $code);
    }
}

class FakeMonitorComposer implements \Barberry\Monitor\ComposerInterface
{
    public function writeClassDeclaration($name)
    {
    }
}
