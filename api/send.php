<?php
// api/send.php — Исправленная версия для Vercel

// 🔐 CORS заголовки
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Обработка preflight CORS запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Доступ запрещён']);
    exit;
}

// 🔐 НАСТРОЙКИ
$token = "8717558717:AAHlH4BwuM26YbiRA95UzxZyXlMUJBY2_5M";
$chat_id = "612146874";

// 🔄 Получение данных: поддержка JSON и form-data
$input = json_decode(file_get_contents('php://input'), true);
$name = htmlspecialchars(trim($input['name'] ?? $_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars(trim($input['phone'] ?? $_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($input['message'] ?? $_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// 🛡️ Валидация
if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
    exit;
}

// 📝 Формирование сообщения
$ip = $_SERVER['HTTP_X_VERCEL_FORWARDER_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$time = date("d.m.Y H:i:s");

$text = "🔔 *Новая заявка с сайта*\n";
$text .= "👤 *Имя:* $name\n";
$text .= "📱 *Телефон:* $phone\n";
if (!empty($message)) {
    $text .= "💬 *Сообщение:* $message\n";
}
$text .= "🌐 *IP:* $ip\n";
$text .= "⏰ *Время:* $time";

// 📤 Отправка в Telegram
// ❗ Исправлено: убраны лишние пробелы в URL
$url = "https://api.telegram.org/bot$token/sendMessage";
$data = [
    'chat_id' => $chat_id,
    'text' => $text,
    'parse_mode' => 'Markdown',
    'disable_web_page_preview' => true
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data), // ❗ Важно: http_build_query для cURL
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 📥 Обработка ответа
if ($http_code === 200) {
    $result = json_decode($response, true);
    if ($result['ok'] ?? false) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Ошибка Telegram: ' . ($result['description'] ?? 'неизвестная')
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка соединения',
        'debug' => $curl_error ?: "HTTP $http_code"
    ]);
}
?>
