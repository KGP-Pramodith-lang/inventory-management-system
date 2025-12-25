<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

$message = isset($payload['message']) ? (string)$payload['message'] : '';
$message = trim($message);

if ($message === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Message is required']);
  exit;
}

if (mb_strlen($message) > 1000) {
  http_response_code(400);
  echo json_encode(['error' => 'Message too long']);
  exit;
}

// Keep a small rolling history in session (optional)
if (!isset($_SESSION['ai_chat_history']) || !is_array($_SESSION['ai_chat_history'])) {
  $_SESSION['ai_chat_history'] = [];
}

$_SESSION['ai_chat_history'][] = ['role' => 'user', 'content' => $message];
$_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -12);

$apiKey = (string)getenv('OPENAI_API_KEY');
$model = (string)getenv('OPENAI_MODEL');
if ($model === '') {
  $model = 'gpt-4o-mini';
}

if ($apiKey === '') {
  http_response_code(200);
  echo json_encode([
    'reply' => 'AI is not configured. Set OPENAI_API_KEY in your server environment.'
  ]);
  exit;
}

$systemPrompt = 'You are a helpful assistant for an inventory management system. Keep answers short and actionable.';

$messages = array_merge(
  [['role' => 'system', 'content' => $systemPrompt]],
  $_SESSION['ai_chat_history']
);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
  ],
  CURLOPT_POSTFIELDS => json_encode([
    'model' => $model,
    'messages' => $messages,
    'temperature' => 0.3,
  ]),
  CURLOPT_TIMEOUT => 25,
]);

$resp = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
  http_response_code(500);
  echo json_encode(['error' => 'Request failed: ' . $curlErr]);
  exit;
}

$data = json_decode($resp, true);

if ($httpCode < 200 || $httpCode >= 300) {
  $msg = 'AI request failed.';
  if (is_array($data) && isset($data['error']['message'])) {
    $msg = (string)$data['error']['message'];
  }
  http_response_code(500);
  echo json_encode(['error' => $msg]);
  exit;
}

$reply = '';
if (is_array($data) && isset($data['choices'][0]['message']['content'])) {
  $reply = (string)$data['choices'][0]['message']['content'];
}
$reply = trim($reply);
if ($reply === '') {
  $reply = '…';
}

$_SESSION['ai_chat_history'][] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -12);

echo json_encode(['reply' => $reply]);
