<?php

/**
 * Validation helper (NOT part of the application): mints RS256 JWTs using the
 * repository's committed test keys, to exercise the LTI service security
 * (client credentials assertion / tool-signed bodies) entirely locally.
 *
 * Usage inside the app container:
 *   php /tmp/validate/mint.php service-assertion <jwksUrl> <aud> <clientId>
 */

declare(strict_types=1);

[$script, $mode, $jwksUrl, $aud, $clientId] = $argv + array_fill(0, 5, '');

if ('service-assertion' !== $mode || '' === $jwksUrl || '' === $aud || '' === $clientId) {
    fwrite(STDERR, "usage: php mint.php service-assertion <jwksUrl> <aud> <clientId>\n");
    exit(1);
}

$jwks = json_decode((string) file_get_contents($jwksUrl), true);
$kid = $jwks['keys'][0]['kid'] ?? null;

$key = openssl_pkey_get_private('file:////app/config/keys/private.key');
if (false === $key) {
    fwrite(STDERR, "cannot load /app/config/keys/private.key\n");
    exit(1);
}

$base64Url = static function (string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
};

$header = ['typ' => 'JWT', 'alg' => 'RS256'];
if (null !== $kid) {
    $header['kid'] = $kid;
}

$now = time();
$payload = [
    'iss' => $clientId,
    'sub' => $clientId,
    'aud' => $aud,
    'iat' => $now,
    'exp' => $now + 300,
    'jti' => bin2hex(random_bytes(8)),
];

$signingInput = $base64Url((string) json_encode($header)) . '.' . $base64Url((string) json_encode($payload));

if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
    fwrite(STDERR, "openssl_sign failed\n");
    exit(1);
}

echo $signingInput . '.' . $base64Url($signature);
