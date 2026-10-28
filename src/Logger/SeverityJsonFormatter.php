<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Formatter\JsonFormatter;

/**
 * JSON formatter emitting a string "severity" ("INFO", "ERROR", ...) instead
 * of Monolog's numeric "level"/"level_name" pair.
 *
 * Log pipelines (e.g. groundcover) classify lines by string severity fields;
 * integer levels (200/400) are not mapped and surface as severity "unknown".
 * Mirrors the severity shape produced by Monolog 3's GoogleCloudLoggingFormatter,
 * which is not available on monolog 2.x (used by this project).
 */
final class SeverityJsonFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        $record['severity'] = $record['level_name'] ?? 'INFO';
        unset($record['level'], $record['level_name']);

        return parent::format($record);
    }
}
