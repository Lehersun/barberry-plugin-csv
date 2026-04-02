<?php

namespace Barberry\Plugin\Csv\Test;

use Barberry\ContentType;
use Barberry\Exception\ConversionNotPossible;
use Barberry\Plugin\Csv\Command;
use Barberry\Plugin\Csv\Converter;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    public function testUtf8CommandConvertsUnambiguousLegacyEncoding(): void
    {
        $source = $this->cp1252("name;note;amount\r\n\"José\";\"Line 1\r\nLine 2; still quoted\";\"€ 10\"\r\n");

        $converted = $this->converter()->convert($source, $this->command('utf8'));

        self::assertSame("name;note;amount\n\"José\";\"Line 1\nLine 2; still quoted\";\"€ 10\"\n", $converted);
    }

    public function testCommaCommandDetectsDelimiterAndNormalizesOutput(): void
    {
        $source = "name\tcomment\tvalue\r\n\"Smith, Jr.\"\t\"Line 1\r\nLine 2\"\t42\r\n";

        $converted = $this->converter()->convert($source, $this->command('comma'));

        self::assertSame("name,comment,value\n\"Smith, Jr.\",\"Line 1\nLine 2\",42\n", $converted);
    }

    public function testCombinedCommandsAreOrderIndependent(): void
    {
        $source = $this->cp1252("name|note\r\n\"José\"|\"€ 10\"\r\n");

        $left = $this->converter()->convert($source, $this->command('utf8_comma'));
        $right = $this->converter()->convert($source, $this->command('comma_utf8'));

        self::assertSame($left, $right);
        self::assertSame("name,note\nJosé,€ 10\n", $left);
    }

    public function testCommaCommandPreservesEmptyFieldsAndEscapedQuotes(): void
    {
        $source = "name;note;empty\r\n\"a\"\"b\";\" spaced \";\r\n";

        $converted = $this->converter()->convert($source, $this->command('comma'));

        self::assertSame("name,note,empty\n\"a\"\"b\",\" spaced \",\"\"\n", $converted);
    }

    public function testFailsOnAmbiguousDelimiter(): void
    {
        $source = "alpha\nbeta\n";

        $this->expectException(ConversionNotPossible::class);
        $this->converter()->convert($source, $this->command('comma'));
    }

    public function testFailsOnAmbiguousEncodingWhenUtf8Requested(): void
    {
        $source = iconv('UTF-8', 'ISO-8859-1//IGNORE', "name;note\r\ncafé;ok\r\n");

        $this->expectException(ConversionNotPossible::class);
        $this->converter()->convert($source, $this->command('utf8'));
    }

    private function converter(): Converter
    {
        return (new Converter())->configure(ContentType::csv(), sys_get_temp_dir());
    }

    private function command(string $command): Command
    {
        return (new Command())->configure($command);
    }

    private function cp1252(string $value): string
    {
        return iconv('UTF-8', 'Windows-1252//IGNORE', $value);
    }
}
