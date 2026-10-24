<?php

/**
 * Validation helper: calls the Caddy admin API worker-restart endpoint
 * from inside the app container and reports the HTTP status.
 */

declare(strict_types=1);

$context = stream_context_create([
    'http' => ['method' => 'POST', 'timeout' => 5, 'ignore_errors' => true],
]);

$body = @file_get_contents('http://localhost:2019/frankenphp/workers/restart', false, $context);

$code = 0;
if (isset($http_response_header[0]) && preg_match('{\s(\d{3})\s}', $http_response_header[0], $matches)) {
    $code = (int) $matches[1];
}

echo sprintf("restart-http:%d body:%s\n", $code, var_export($body, true));
exit($code >= 200 && $code < 300 ? 0 : 1);
