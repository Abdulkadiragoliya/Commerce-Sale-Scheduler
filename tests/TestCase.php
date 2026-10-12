<?php
declare(strict_types=1);

namespace abdulkadiragoliya\commercesaleschedulertests;

use AssertionError;

abstract class TestCase
{
    public function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $expectedStr = var_export($expected, true);
            $actualStr = var_export($actual, true);
            throw new AssertionError("Failed asserting that {$actualStr} is identical to {$expectedStr}" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertNotSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            $expectedStr = var_export($expected, true);
            throw new AssertionError("Failed asserting that two values are not identical ({$expectedStr})" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            $expectedStr = var_export($expected, true);
            $actualStr = var_export($actual, true);
            throw new AssertionError("Failed asserting that {$actualStr} is equal to {$expectedStr}" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected == $actual) {
            $expectedStr = var_export($expected, true);
            throw new AssertionError("Failed asserting that two values are not equal ({$expectedStr})" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertTrue(mixed $condition, string $message = ''): void
    {
        if ($condition !== true) {
            throw new AssertionError("Failed asserting that condition is true" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertFalse(mixed $condition, string $message = ''): void
    {
        if ($condition !== false) {
            throw new AssertionError("Failed asserting that condition is false" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertNull(mixed $actual, string $message = ''): void
    {
        if ($actual !== null) {
            throw new AssertionError("Failed asserting that value is null" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertNotNull(mixed $actual, string $message = ''): void
    {
        if ($actual === null) {
            throw new AssertionError("Failed asserting that value is not null" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertGreaterThan(mixed $expected, mixed $actual, string $message = ''): void
    {
        if (!($actual > $expected)) {
            throw new AssertionError("Failed asserting that {$actual} is greater than {$expected}" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertCount(int $expectedCount, mixed $haystack, string $message = ''): void
    {
        $count = is_countable($haystack) ? count($haystack) : 0;
        if ($count !== $expectedCount) {
            throw new AssertionError("Failed asserting that count {$count} matches expected {$expectedCount}" . ($message ? " - {$message}" : ''));
        }
    }

    public function assertStringContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionError("Failed asserting that '{$haystack}' contains '{$needle}'" . ($message ? " - {$message}" : ''));
        }
    }

    public function getAnyElementId(): int
    {
        $id = (new \craft\db\Query())->from('{{%elements}}')->select('id')->scalar();
        return (int)$id;
    }
}
