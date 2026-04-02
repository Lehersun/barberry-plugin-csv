<?php

namespace Barberry\Plugin\Csv;

use Barberry\ContentType;
use Barberry\Exception\ConversionNotPossible;
use Barberry\Plugin;

class Converter implements Plugin\InterfaceConverter
{
    private $tempPath;

    private $targetContentType;

    public function configure(ContentType $targetContentType, $tempPath)
    {
        $this->tempPath = $tempPath;
        $this->targetContentType = $targetContentType;
        return $this;
    }

    public function convert($bin, Plugin\InterfaceCommand $command = null)
    {
        if (!$command instanceof Command) {
            return $bin;
        }

        if (!$command->isValid()) {
            throw new ConversionNotPossible('Malformed CSV command.');
        }

        $transformer = new Transformer($command);
        $bin = $transformer->transform($bin);

        return $bin;
    }
}
