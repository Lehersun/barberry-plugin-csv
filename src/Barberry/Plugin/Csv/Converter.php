<?php

namespace Barberry\Plugin\Csv;

use Barberry\ContentType;
use Barberry\Exception\ConversionNotPossible;
use Barberry\Plugin;

class Converter implements Plugin\InterfaceConverter
{
    private const DELIMITER_CANDIDATES = array(',', ';', "\t", '|');

    private $tempPath;

    private $targetContentType;

    public function configure(ContentType $targetContentType, $tempPath)
    {
        $this->targetContentType = $targetContentType;
        $this->tempPath = (string) $tempPath;

        return $this;
    }

    public function convert($bin, ?Plugin\InterfaceCommand $command = null)
    {
        if (!$command instanceof Command) {
            return (string) $bin;
        }

        if (!$command->isValid()) {
            throw new ConversionNotPossible('Malformed CSV command.');
        }

        $source = (string) $bin;
        $encoding = $this->detectEncoding($source);
        $utf8 = $this->toUtf8($source, $encoding);

        if (!$command->comma()) {
            return $command->utf8() ? self::normalizeLineEndings($utf8) : self::normalizeLineEndings($source);
        }

        $delimiter = $this->detectDelimiter($utf8);
        $rows = $this->parseCsv($utf8, $delimiter);
        $output = $this->writeCsv($rows, ',');

        if ($command->utf8() || $encoding === 'UTF-8') {
            return $output;
        }

        $converted = iconv('UTF-8', $encoding . '//IGNORE', $output);
        if ($converted === false) {
            throw new ConversionNotPossible('Unable to encode CSV output.');
        }

        return $converted;
    }

    private function detectEncoding($bin)
    {
        if (strncmp($bin, "\xEF\xBB\xBF", 3) === 0) {
            return 'UTF-8';
        }

        if ($bin === '' || !preg_match('/[\x80-\xFF]/', $bin)) {
            return 'UTF-8';
        }

        if (mb_check_encoding($bin, 'UTF-8')) {
            return 'UTF-8';
        }

        if (preg_match('/[\x80-\x9F]/', $bin)) {
            return 'Windows-1252';
        }

        throw new ConversionNotPossible('Ambiguous CSV encoding.');
    }

    private function toUtf8($bin, $encoding)
    {
        if ($encoding === 'UTF-8') {
            return strncmp($bin, "\xEF\xBB\xBF", 3) === 0 ? substr($bin, 3) : $bin;
        }

        $converted = iconv($encoding, 'UTF-8//IGNORE', $bin);
        if ($converted === false) {
            throw new ConversionNotPossible('Unable to decode CSV input.');
        }

        return $converted;
    }

    private function detectDelimiter($utf8)
    {
        $matches = array();
        foreach (self::DELIMITER_CANDIDATES as $delimiter) {
            try {
                $rows = $this->parseCsv($utf8, $delimiter);
            } catch (ConversionNotPossible $exception) {
                continue;
            }

            if (!$rows) {
                continue;
            }

            $widths = array_values(array_unique(array_map('count', $rows)));
            if (count($widths) !== 1 || $widths[0] < 2) {
                continue;
            }

            $matches[] = $delimiter;
        }

        if (count($matches) !== 1) {
            throw new ConversionNotPossible('Ambiguous CSV delimiter.');
        }

        return $matches[0];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv($input, $delimiter)
    {
        $rows = array();
        $row = array();
        $field = '';
        $inQuotes = false;
        $recordEnded = false;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($inQuotes) {
                if ($char === '"') {
                    if (($i + 1) < $length && $input[$i + 1] === '"') {
                        $field .= '"';
                        $i++;
                        continue;
                    }

                    $inQuotes = false;
                    continue;
                }

                if ($char === "\r") {
                    if (($i + 1) < $length && $input[$i + 1] === "\n") {
                        $i++;
                    }
                    $field .= "\n";
                    continue;
                }

                $field .= $char === "\n" ? "\n" : $char;
                continue;
            }

            if ($char === '"') {
                if ($field !== '') {
                    throw new ConversionNotPossible('Malformed quoted CSV field.');
                }
                $inQuotes = true;
                $recordEnded = false;
                continue;
            }

            if ($char === $delimiter) {
                $row[] = $field;
                $field = '';
                $recordEnded = false;
                continue;
            }

            if ($char === "\r" || $char === "\n") {
                if ($char === "\r" && ($i + 1) < $length && $input[$i + 1] === "\n") {
                    $i++;
                }
                $row[] = $field;
                $rows[] = $row;
                $row = array();
                $field = '';
                $recordEnded = true;
                continue;
            }

            $field .= $char;
            $recordEnded = false;
        }

        if ($inQuotes) {
            throw new ConversionNotPossible('Unclosed quoted CSV field.');
        }

        if ($length === 0) {
            return array();
        }

        if (!$recordEnded || $field !== '' || $row !== array()) {
            $row[] = $field;
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function writeCsv(array $rows, $delimiter)
    {
        $lines = array();
        foreach ($rows as $row) {
            $fields = array();
            foreach ($row as $field) {
                $normalized = str_replace("\r\n", "\n", str_replace("\r", "\n", $field));
                $mustQuote = $normalized === ''
                    || strpos($normalized, $delimiter) !== false
                    || strpos($normalized, '"') !== false
                    || strpos($normalized, "\n") !== false
                    || preg_match('/^\s|\s$/u', $normalized);

                if ($mustQuote) {
                    $normalized = '"' . str_replace('"', '""', $normalized) . '"';
                }

                $fields[] = $normalized;
            }
            $lines[] = implode($delimiter, $fields);
        }

        return implode("\n", $lines) . ($lines ? "\n" : '');
    }

    private static function normalizeLineEndings($value)
    {
        return str_replace(array("\r\n", "\r"), "\n", $value);
    }
}
