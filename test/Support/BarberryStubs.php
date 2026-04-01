<?php

namespace Barberry {
    class ContentType
    {
        private string $extension;

        private function __construct(string $extension)
        {
            $this->extension = $extension;
        }

        public static function csv(): self
        {
            return new self('csv');
        }

        public static function byExtention(string $extension): self
        {
            return new self($extension);
        }

        public function standardExtension(): string
        {
            return $this->extension;
        }

        public function __toString(): string
        {
            return $this->extension;
        }
    }
}

namespace Barberry\Exception {
    class ConversionNotPossible extends \RuntimeException
    {
    }
}

namespace Barberry\Plugin {
    interface InterfaceCommand
    {
        public function configure($commandString);

        public function conforms($commandString);
    }

    interface InterfaceConverter
    {
        public function configure(\Barberry\ContentType $targetContentType, $tempPath);

        public function convert($bin, ?InterfaceCommand $command = null);
    }

    interface InterfaceInstaller
    {
        public function install(
            \Barberry\Direction\ComposerInterface $directionComposer,
            \Barberry\Monitor\ComposerInterface $monitorComposer,
            $pluginParams = array()
        );
    }

    class NotAvailableException extends \RuntimeException
    {
    }

    class NullPlugin implements InterfaceConverter
    {
        public function configure(\Barberry\ContentType $targetContentType, $tempPath)
        {
            return $this;
        }

        public function convert($bin, ?InterfaceCommand $command = null)
        {
            return $bin;
        }
    }
}

namespace Barberry\Direction {
    interface ComposerInterface
    {
        public function writeClassDeclaration($sourceContentType, $destinationContentType, $converter, $command);
    }
}

namespace Barberry\Monitor {
    interface ComposerInterface
    {
        public function writeClassDeclaration($name);
    }
}
