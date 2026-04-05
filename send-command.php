<?php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/firebase_auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

$appSecret = $_SERVER['HTTP_X_APP_SECRET'] ?? '';
if ($appSecret !== $config['app_secret']) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid secret']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$commandId = $data['commandId'] ?? '';
$targetPushToken = $data['targetPushToken'] ?? '';
$commandType = $data['commandType'] ?? 'RING';
$ringMode = $data['ringMode'] ?? 'NORMAL';

if (!$commandId || !$targetPushToken) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing commandId or targetPushToken']);
    exit;
}

try {
    $accessToken = get_firebase_access_token($config);
    $url = 'https://fcm.googleapis.com/v1/projects/' . $config['project_id'] . '/messages:send';

    $message = [
        'message' => [
            'token' => $targetPushToken,
            'data' => [
                'commandId' => $commandId,
                'commandType' => $commandType,
                'ringMode' => $ringMode,
            ],
            'android' => [
                'priority' => 'high',
                'ttl' => '30s',
            ],
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($message),
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('FCM send failed: ' . curl_error($ch));
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        throw new Exception('FCM send failed: ' . $response);
    }

    echo json_encode(['ok' => true, 'response' => json_decode($response, true)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
