<?php

declare(strict_types=1);

/**
 * Minimal WP_CLI stub for static analysis.
 */
class WP_CLI
{
    /**
     * @param string $message
     */
    public static function success($message): void
    {
    }

    /**
     * @param string $message
     * @param bool $exit
     */
    public static function error($message, $exit = true): void
    {
    }

    /**
     * @param string $name
     * @param mixed $callable
     * @param array<string, mixed> $args
     */
    public static function add_command($name, $callable, $args = []): void
    {
    }
}
