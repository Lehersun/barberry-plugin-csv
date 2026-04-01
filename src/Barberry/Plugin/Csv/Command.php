<?php

namespace Barberry\Plugin\Csv;

use Barberry\Plugin\InterfaceCommand;

class Command implements InterfaceCommand
{
    private const TOKEN_UTF8 = 'utf8';
    private const TOKEN_COMMA = 'comma';
    private const CANONICAL_ORDER = array(self::TOKEN_UTF8, self::TOKEN_COMMA);

    private $valid = true;

    /**
     * @var array<string, bool>
     */
    private $tokens = array();

    public function configure($commandString)
    {
        $parsed = self::parse((string) $commandString);
        $this->valid = $parsed !== null;
        $this->tokens = $parsed ?? array();

        return $this;
    }

    public function conforms($commandString)
    {
        $parsed = self::parse((string) $commandString);
        if (!$this->valid || $parsed === null) {
            return false;
        }

        return self::canonicalString($parsed) === self::canonicalString($this->tokens);
    }

    public function isValid()
    {
        return $this->valid;
    }

    public function utf8()
    {
        return isset($this->tokens[self::TOKEN_UTF8]);
    }

    public function comma()
    {
        return isset($this->tokens[self::TOKEN_COMMA]);
    }

    public function __toString(): string
    {
        if (!$this->valid) {
            return '';
        }

        $tokens = array();
        foreach (self::CANONICAL_ORDER as $token) {
            if (isset($this->tokens[$token])) {
                $tokens[] = $token;
            }
        }

        return implode('_', $tokens);
    }

    /**
     * @return array<string, bool>|null
     */
    private static function parse($commandString)
    {
        if ($commandString === '') {
            return array();
        }

        if ($commandString[0] === '_' || substr($commandString, -1) === '_' || strpos($commandString, '__') !== false) {
            return null;
        }

        $tokens = explode('_', $commandString);
        $parsed = array();
        foreach ($tokens as $token) {
            if (!in_array($token, self::CANONICAL_ORDER, true) || isset($parsed[$token])) {
                return null;
            }
            $parsed[$token] = true;
        }

        return $parsed;
    }

    /**
     * @param array<string, bool> $tokens
     */
    private static function canonicalString(array $tokens)
    {
        $normalized = array();
        foreach (self::CANONICAL_ORDER as $token) {
            if (isset($tokens[$token])) {
                $normalized[] = $token;
            }
        }

        return implode('_', $normalized);
    }
}
