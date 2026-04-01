<?php

namespace Barberry\Plugin\Csv\Test;

abstract class TestCase
{
    protected static function assertTrue($value, string $message = ''): void
    {
        if ($value !== true) {
            throw new \RuntimeException($message ?: 'Expected true.');
        }
    }

    protected static function assertFalse($value, string $message = ''): void
    {
        if ($value !== false) {
            throw new \RuntimeException($message ?: 'Expected false.');
        }
    }

    protected static function assertSame($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException($message ?: sprintf("Expected <%s>, got <%s>.", var_export($expected, true), var_export($actual, true)));
        }
    }

    protected static function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            throw new \RuntimeException($message ?: sprintf("Expected <%s>, got <%s>.", var_export($expected, true), var_export($actual, true)));
        }
    }

    protected static function assertNull($actual, string $message = ''): void
    {
        if (!is_null($actual)) {
            throw new \RuntimeException($message ?: 'Expected null.');
        }
    }

    protected static function assertInstanceOf(string $expectedClass, $actual, string $message = ''): void
    {
        if (!$actual instanceof $expectedClass) {
            throw new \RuntimeException($message ?: sprintf('Expected instance of %s.', $expectedClass));
        }
    }

    protected static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new \RuntimeException($message ?: sprintf('Expected "%s" to contain "%s".', $haystack, $needle));
        }
    }

    protected static function assertThrows(string $expectedClass, callable $callable): void
    {
        try {
            $callable();
        } catch (\Throwable $throwable) {
            if ($throwable instanceof $expectedClass) {
                return;
            }

            throw new \RuntimeException(sprintf('Expected %s, got %s.', $expectedClass, get_class($throwable)), 0, $throwable);
        }

        throw new \RuntimeException(sprintf('Expected %s to be thrown.', $expectedClass));
    }
}
