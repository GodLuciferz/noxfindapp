<?php
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function get_firebase_access_token(array $config): string {
    $serviceAccountPath = $config['service_account_path'];
    if (!file_exists($serviceAccountPath)) {
        throw new Exception('firebase-service-account.json not found');
    }

    $creds = json_decode(file_get_contents($serviceAccountPath), true);
    if (!$creds) {
        throw new Exception('Invalid service account json');
    }

    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => $creds['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $unsignedJwt = base64url_encode(json_encode($header)) . '.' . base64url_encode(json_encode($claims));
    $privateKey = openssl_pkey_get_private($creds['private_key']);
    if (!$privateKey) {
        throw new Exception('Unable to read private key');
    }

    $signature = '';
    openssl_sign($unsignedJwt, $signature, $privateKey, 'sha256WithRSAEncryption');
    $jwt = $unsignedJwt . '.' . base64url_encode($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Token request failed: ' . curl_error($ch));
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($response, true);
    if ($status < 200 || $status >= 300 || empty($json['access_token'])) {
        throw new Exception('Unable to fetch access token: ' . $response);
    }

    return $json['access_token'];
}
