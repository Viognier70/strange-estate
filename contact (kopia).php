<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$name    = htmlspecialchars(trim($input['name']    ?? ''));
$email   = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$subject = htmlspecialchars(trim($input['subject'] ?? ''));
$message = htmlspecialchars(trim($input['message'] ?? ''));

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Fyll i alla obligatoriska fält']);
    exit;
}

if (!$subject) $subject = 'Nytt meddelande från strangeestate.com';

$apiKey = "DIN_API_NYCKEL_HÄR";

$html = '
<div style="font-family:sans-serif;max-width:600px;padding:24px;background:#0a0a0a;color:#f2ede6;">
  <h2 style="color:#fa6000;margin-bottom:24px;">Nytt meddelande – Strange Estate</h2>
  <p><strong>Från:</strong> ' . $name . ' &lt;' . $email . '&gt;</p>
  <p><strong>Ämne:</strong> ' . $subject . '</p>
  <hr style="border:1px solid #222;margin:20px 0;">
  <p style="line-height:1.7;">' . nl2br($message) . '</p>
</div>';

$data = [
    'sender'      => ['name' => 'Strange Estate Web', 'email' => 'hi@strangeestate.com'],
    'to'          => [['email' => 'anders@strangeestate.com', 'name' => 'Anders']],
    'replyTo'     => ['email' => $email, 'name' => $name],
    'subject'     => $subject,
    'htmlContent' => $html
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
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

if ($httpCode === 201) {
    echo json_encode(['success' => true]);
} else {
    $body = json_decode($response, true);
    http_response_code(500);
    echo json_encode(['error' => $body['message'] ?? 'Meddelandet kunde inte skickas']);
}
