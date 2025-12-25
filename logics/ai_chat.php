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

$apiKey = 'AIzaSyAHxVZRm1rJNLTR2PUjdVR1V_1PjP9fenM';
if ($apiKey === '') {
  // Common alternative name
  $apiKey = (string)getenv('GOOGLE_API_KEY');
}

$model = 'gemini-2.5-flash';
if ($model === '') {
  $model = 'gemini-1.5-flash';
}

if ($apiKey === '') {
  http_response_code(200);
  echo json_encode([
    'reply' => 'AI is not configured. Set GEMINI_API_KEY (or GOOGLE_API_KEY) in your server environment.'
  ]);
  exit;
}

$systemPrompt = 'You are a helpful assistant for an inventory management system. Use the provided business snapshot when answering. Keep answers short, specific, and actionable.';

// Pull live business context from DB (best-effort)
$snapshot = '';
try {
  require_once __DIR__ . '/../connection/db.php';

  $lowStockThreshold = 5;

  $stmt = $pdo->query('SELECT COUNT(*) AS c FROM products');
  $totalProducts = (int)($stmt->fetch()['c'] ?? 0);

  $stmt = $pdo->query('SELECT COALESCE(SUM(quantity * price), 0) AS total_value FROM products');
  $inventoryValue = (float)($stmt->fetch()['total_value'] ?? 0);

  $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM products WHERE quantity < :t');
  $stmt->execute([':t' => $lowStockThreshold]);
  $lowStockCount = (int)($stmt->fetch()['c'] ?? 0);

  $stmt = $pdo->prepare('SELECT name, sku, quantity FROM products WHERE quantity < :t ORDER BY quantity ASC LIMIT 8');
  $stmt->execute([':t' => $lowStockThreshold]);
  $lowStockItems = $stmt->fetchAll();

  $stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) AS total_sales FROM bills WHERE DATE(bill_date) = CURDATE()');
  $todaysSales = (float)($stmt->fetch()['total_sales'] ?? 0);

  $stmt = $pdo->query("SELECT COUNT(*) AS c FROM refunds WHERE status = 'pending'");
  $pendingRefunds = (int)($stmt->fetch()['c'] ?? 0);

  $stmt = $pdo->query('SELECT id, customer_name, total_amount, bill_date FROM bills ORDER BY bill_date DESC LIMIT 5');
  $recentBills = $stmt->fetchAll();

  $stmt = $pdo->query(
    "SELECT bi.product_name, SUM(bi.quantity) AS qty " .
    "FROM bill_items bi " .
    "JOIN bills b ON b.id = bi.bill_id " .
    "WHERE DATE(b.bill_date) = CURDATE() " .
    "GROUP BY bi.product_name " .
    "ORDER BY qty DESC " .
    "LIMIT 5"
  );
  $topToday = $stmt->fetchAll();

  $stmt = $pdo->query('SELECT supplier_name, total_cost, order_date FROM supply_orders ORDER BY order_date DESC LIMIT 5');
  $recentPurchases = $stmt->fetchAll();

  $snapshotLines = [];
  $snapshotLines[] = 'Business snapshot (live DB, read-only):';
  $snapshotLines[] = '- Total products: ' . number_format($totalProducts);
  $snapshotLines[] = '- Inventory value: $' . number_format($inventoryValue, 2);
  $snapshotLines[] = '- Low stock (< ' . $lowStockThreshold . '): ' . number_format($lowStockCount);
  $snapshotLines[] = '- Today\'s sales: $' . number_format($todaysSales, 2);
  $snapshotLines[] = '- Pending refunds: ' . number_format($pendingRefunds);

  if (is_array($lowStockItems) && count($lowStockItems) > 0) {
    $snapshotLines[] = 'Low stock items:';
    foreach ($lowStockItems as $it) {
      $snapshotLines[] = "- {$it['name']} (SKU: {$it['sku']}): {$it['quantity']}";
    }
  }

  if (is_array($topToday) && count($topToday) > 0) {
    $snapshotLines[] = 'Top sellers today (qty):';
    foreach ($topToday as $t) {
      $name = (string)($t['product_name'] ?? '');
      $qty = (int)($t['qty'] ?? 0);
      $snapshotLines[] = "- {$name}: {$qty}";
    }
  }

  if (is_array($recentBills) && count($recentBills) > 0) {
    $snapshotLines[] = 'Recent bills:';
    foreach ($recentBills as $b) {
      $id = (int)($b['id'] ?? 0);
      $cust = (string)($b['customer_name'] ?? '');
      $amt = (float)($b['total_amount'] ?? 0);
      $dt = (string)($b['bill_date'] ?? '');
      $snapshotLines[] = "- #{$id} {$cust}: $" . number_format($amt, 2) . " ({$dt})";
    }
  }

  if (is_array($recentPurchases) && count($recentPurchases) > 0) {
    $snapshotLines[] = 'Recent purchases:';
    foreach ($recentPurchases as $p) {
      $sup = (string)($p['supplier_name'] ?? '');
      $cost = (float)($p['total_cost'] ?? 0);
      $dt = (string)($p['order_date'] ?? '');
      $snapshotLines[] = "- {$sup}: $" . number_format($cost, 2) . " ({$dt})";
    }
  }

  $snapshot = implode("\n", $snapshotLines);
} catch (Throwable $e) {
  // If DB is unavailable, continue without snapshot.
  $snapshot = '';
}

$contents = [];
foreach ($_SESSION['ai_chat_history'] as $m) {
  $role = (string)($m['role'] ?? '');
  $content = (string)($m['content'] ?? '');
  $content = trim($content);
  if ($content === '') {
    continue;
  }

  // Gemini roles: user | model
  $geminiRole = ($role === 'assistant') ? 'model' : 'user';
  $contents[] = [
    'role' => $geminiRole,
    'parts' => [
      ['text' => $content]
    ]
  ];
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
  ],
  CURLOPT_POSTFIELDS => json_encode([
    'systemInstruction' => [
      'parts' => [
        ['text' => $systemPrompt],
        ['text' => $snapshot],
      ]
    ],
    'contents' => $contents,
    'generationConfig' => [
      'temperature' => 0.3,
    ],
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
$blockReason = $data['promptFeedback']['blockReason'] ?? '';
if ($blockReason) {
  $reply = 'Sorry, I can\'t help with that request.';
}

$parts = $data['candidates'][0]['content']['parts'] ?? null;
if (is_array($parts)) {
  $texts = [];
  foreach ($parts as $p) {
    if (is_array($p) && isset($p['text'])) {
      $texts[] = (string)$p['text'];
    }
  }
  $reply = implode('', $texts);
}
$reply = trim($reply);
if ($reply === '') {
  $reply = '…';
}

$_SESSION['ai_chat_history'][] = ['role' => 'assistant', 'content' => $reply];
$_SESSION['ai_chat_history'] = array_slice($_SESSION['ai_chat_history'], -12);

echo json_encode(['reply' => $reply]);
