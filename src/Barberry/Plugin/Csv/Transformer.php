<?php

namespace Barberry\Plugin\Csv;

use Barberry\Exception\ConversionNotPossible;

class Transformer
{
    const DELIMITER_CANDIDATES = array(',', ';', "\t", '|', ':');

    /**
     * @var Command
     */
    private $command;

    /**
     * @param Command $command
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function transform($bin)
    {
        $source = (string) $bin;
        $encoding = $this->detectEncoding($source);
        $utf8 = $this->toUtf8($source, $encoding);

        if (!$this->command->comma()) {
            return $this->finalizeWithoutDelimiterChange($source, $utf8, $encoding);
        }

        $delimiter = $this->detectDelimiter($utf8);
        $rows = $this->parseCsv($utf8, $delimiter);
        $output = $this->writeCsv($rows, ',');

        return $this->finalizeOutput($output, $encoding);
    }

    private function finalizeWithoutDelimiterChange($source, $utf8, $encoding)
    {
        if ($this->command->utf8()) {
            return self::normalizeLineEndings($utf8);
        }

        if ($encoding === 'UTF-8') {
            return self::normalizeLineEndings($utf8);
        }

        return self::normalizeLineEndings($source);
    }

    private function finalizeOutput($output, $encoding)
    {
        if ($this->command->utf8() || $encoding === 'UTF-8') {
            return $output;
        }

        $converted = iconv('UTF-8', $encoding, $output);
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

        $detected = mb_detect_encoding($bin, array('UTF-8', 'Windows-1252', 'ISO-8859-1'), true);
        if ($detected === 'UTF-8' && mb_check_encoding($bin, 'UTF-8')) {
            return 'UTF-8';
        }

        if (($detected === 'Windows-1252' || $detected === 'ISO-8859-1') && preg_match('/[\x80-\x9F]/', $bin)) {
            return 'Windows-1252';
        }

        throw new ConversionNotPossible('Ambiguous CSV encoding.');
    }

    private function toUtf8($bin, $encoding)
    {
        if ($encoding === 'UTF-8') {
            return strncmp($bin, "\xEF\xBB\xBF", 3) === 0 ? substr($bin, 3) : $bin;
        }

        $converted = iconv($encoding, 'UTF-8', $bin);
        if ($converted === false) {
            throw new ConversionNotPossible('Unable to decode CSV input.');
        }

        return $converted;
    }

    private function detectDelimiter($utf8)
    {
        $matches = array();
        foreach (self::DELIMITER_CANDIDATES as $delimiter) {
            $rows = $this->parseCsv($utf8, $delimiter);

            if (!$rows) {
                continue;
            }

            $rowsForDetection = $this->rowsWithContent($rows);
            if (!$rowsForDetection) {
                $rowsForDetection = $rows;
            }

            $widths = array_values(array_unique(array_map('count', $rowsForDetection)));
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
     * @return array
     */
    private function parseCsv($input, $delimiter)
    {
        $stream = $this->createStream($input);
        $rows = array();
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            if ($row === array(null)) {
                continue;
            }

            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * @param array $rows
     */
    private function writeCsv(array $rows, $delimiter)
    {
        $lines = array();
        foreach ($rows as $row) {
            $fields = array();
            foreach ($row as $field) {
                $normalized = self::normalizeLineEndings($field);
                $mustQuote = strpos($normalized, $delimiter) !== false
                    || strpos($normalized, '"') !== false
                    || strpos($normalized, "\n") !== false;

                if ($mustQuote) {
                    $normalized = '"' . str_replace('"', '""', $normalized) . '"';
                }

                $fields[] = $normalized;
            }
            $lines[] = implode($delimiter, $fields);
        }

        return implode("\n", $lines) . ($lines ? "\n" : '');
    }

    private function rowsWithContent(array $rows)
    {
        $filtered = array();
        foreach ($rows as $row) {
            foreach ($row as $field) {
                if ($field !== '') {
                    $filtered[] = $row;
                    break;
                }
            }
        }

        return $filtered;
    }

    private static function normalizeLineEndings($value)
    {
        return str_replace(array("\r\n", "\r"), "\n", $value);
    }

    private function createStream($contents = '')
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new ConversionNotPossible('Unable to create CSV temp stream.');
        }

        if ($contents !== '') {
            fwrite($stream, $contents);
            rewind($stream);
        }

        return $stream;
    }
}
