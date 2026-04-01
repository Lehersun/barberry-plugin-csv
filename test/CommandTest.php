<?php

namespace Barberry\Plugin\Csv\Test;

use Barberry\Plugin\Csv\Command;

class CommandTest extends TestCase
{
    public function testEmptyCommandIsValid(): void
    {
        $command = (new Command())->configure('');

        self::assertTrue($command->conforms(''));
        self::assertSame('', (string) $command);
    }

    public function testRecognizesSingleCommands(): void
    {
        self::assertTrue((new Command())->configure('utf8')->conforms('utf8'));
        self::assertTrue((new Command())->configure('comma')->conforms('comma'));
    }

    public function testCombinedCommandsAreOrderIndependent(): void
    {
        $command = (new Command())->configure('utf8_comma');

        self::assertTrue($command->conforms('utf8_comma'));
        self::assertTrue($command->conforms('comma_utf8'));
        self::assertSame('utf8_comma', (string) $command);
    }

    public function testRejectsUnknownCommandTokens(): void
    {
        self::assertFalse((new Command())->configure('bogus')->conforms('bogus'));
    }

    public function testRejectsDuplicateTokens(): void
    {
        self::assertFalse((new Command())->configure('utf8_utf8')->conforms('utf8_utf8'));
    }

    public function testRejectsMalformedSeparators(): void
    {
        self::assertFalse((new Command())->configure('_utf8')->conforms('_utf8'));
        self::assertFalse((new Command())->configure('utf8_')->conforms('utf8_'));
        self::assertFalse((new Command())->configure('utf8__comma')->conforms('utf8__comma'));
    }
}
