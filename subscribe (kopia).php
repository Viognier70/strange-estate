<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Ogiltig e-postadress']);
    exit;
}

$apiKey = "DIN_API_NYCKEL_HÄR";
$data = [
    'email'         => $email,
    'listIds'       => [2],
    'updateEnabled' => true
];

$ch = curl_init('https://api.brevo.com/v3/contacts');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'api-key: ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 201 = created, 204 = already exists (updated)
if ($httpCode === 201 || $httpCode === 204) {
    echo json_encode(['success' => true]);
} else {
    $body = json_decode($response, true);
    http_response_code(500);
    echo json_encode(['error' => $body['message'] ?? 'Prenumeration misslyckades']);
}
