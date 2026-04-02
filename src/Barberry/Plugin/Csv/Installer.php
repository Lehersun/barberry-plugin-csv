<?php

namespace Barberry\Plugin\Csv;

use Barberry\ContentType;
use Barberry\Direction;
use Barberry\Monitor;
use Barberry\Plugin;

class Installer implements Plugin\InterfaceInstaller
{
    public function install(
        Direction\ComposerInterface $directionComposer,
        Monitor\ComposerInterface $monitorComposer,
        $pluginParams = array()
    ) {
        $directionComposer->writeClassDeclaration(
            ContentType::csv(),
            ContentType::csv(),
            'new Plugin\\Csv\\Converter',
            'new Plugin\\Csv\\Command'
        );
    }
}
