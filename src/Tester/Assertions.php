<?php

namespace Daniel\Origins\Tester;

use Countable;
use Throwable;

/**
 * Conjunto de asserções estáticas estilo JUnit.
 *
 * Cada método lança {@see AssertionFailedException} quando a verificação falha.
 */
final class Assertions
{
    public static function assertTrue(bool $condition, ?string $message = null): void
    {
        if ($condition !== true) {
            self::fail($message ?? "Esperava true, obteve false.");
        }
    }

    public static function assertFalse(bool $condition, ?string $message = null): void
    {
        if ($condition !== false) {
            self::fail($message ?? "Esperava false, obteve true.");
        }
    }

    public static function assertEquals(mixed $expected, mixed $actual, ?string $message = null): void
    {
        if ($expected != $actual) {
            self::fail($message ?? sprintf(
                "Esperava %s, obteve %s.",
                self::stringify($expected),
                self::stringify($actual)
            ));
        }
    }

    public static function assertNotEquals(mixed $expected, mixed $actual, ?string $message = null): void
    {
        if ($expected == $actual) {
            self::fail($message ?? sprintf(
                "Esperava algo diferente de %s.",
                self::stringify($expected)
            ));
        }
    }

    public static function assertSame(mixed $expected, mixed $actual, ?string $message = null): void
    {
        if ($expected !== $actual) {
            self::fail($message ?? sprintf(
                "Esperava (identidade) %s, obteve %s.",
                self::stringify($expected),
                self::stringify($actual)
            ));
        }
    }

    public static function assertNotSame(mixed $expected, mixed $actual, ?string $message = null): void
    {
        if ($expected === $actual) {
            self::fail($message ?? sprintf(
                "Esperava algo não idêntico a %s.",
                self::stringify($expected)
            ));
        }
    }

    public static function assertNull(mixed $actual, ?string $message = null): void
    {
        if ($actual !== null) {
            self::fail($message ?? sprintf("Esperava null, obteve %s.", self::stringify($actual)));
        }
    }

    public static function assertNotNull(mixed $actual, ?string $message = null): void
    {
        if ($actual === null) {
            self::fail($message ?? "Esperava um valor não-nulo, obteve null.");
        }
    }

    public static function assertContains(mixed $needle, iterable $haystack, ?string $message = null): void
    {
        foreach ($haystack as $item) {
            if ($item === $needle) {
                return;
            }
        }
        self::fail($message ?? sprintf("O valor %s não foi encontrado na coleção.", self::stringify($needle)));
    }

    public static function assertCount(int $expected, Countable|array $countable, ?string $message = null): void
    {
        $actual = count($countable);
        if ($actual !== $expected) {
            self::fail($message ?? sprintf("Esperava %d elemento(s), obteve %d.", $expected, $actual));
        }
    }

    /**
     * Verifica que a execução de $callback lança uma exceção do tipo $expectedException.
     */
    public static function assertThrows(string $expectedException, callable $callback, ?string $message = null): void
    {
        try {
            $callback();
        } catch (Throwable $thrown) {
            if ($thrown instanceof $expectedException) {
                return;
            }
            self::fail($message ?? sprintf(
                "Esperava exceção %s, obteve %s.",
                $expectedException,
                get_class($thrown)
            ));
        }
        self::fail($message ?? sprintf("Esperava exceção %s, mas nada foi lançado.", $expectedException));
    }

    public static function fail(string $message = "Falha na asserção."): never
    {
        throw new AssertionFailedException($message);
    }

    private static function stringify(mixed $value): string
    {
        if ($value === null) {
            return "null";
        }
        if (is_bool($value)) {
            return $value ? "true" : "false";
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        if (is_array($value)) {
            return "array(" . count($value) . ")";
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return gettype($value);
    }
}
