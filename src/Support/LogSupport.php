<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

final class LogSupport
{
    private static array $messages = [];

    public static function add(string $message): void
    {
        if (config('app.debug')) {
            $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
            $key        = 1;

            if (app()->environment('testing')) {
                $key = 0;
            }

            self::$messages[$message . $stackTrace[$key]['line']] = [
                'message'     => $message,
                'stack_trace' => $stackTrace,
            ];
        }
    }

    public static function getMessages(): array
    {
        return array_values(self::$messages);
    }
}
