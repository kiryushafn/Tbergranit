<?php
// send.php — Обработчик формы для Telegram

// 🔐 НАСТРОЙКИ (заполните своими данными)
$token = "8717558717:AAHlH4BwuM26YbiRA95UzxZyXlMUJBY2_5M"; // Токен бота
$chat_id = "612146874"; // Ваш Chat ID

// Проверка метода запроса
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(403);
    echo "Доступ запрещён";
    exit;
}

// Получение и очистка данных
$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));
$ip = $_SERVER['REMOTE_ADDR'];
$time = date("d.m.Y H:i:s");

// Валидация
if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Заполните обязательные поля"]);
    exit;
}

// Формирование сообщения для Telegram
$text = "🔔 *Новая заявка с сайта*\n";
$text .= "👤 *Имя:* $name\n";
$text .= "📱 *Телефон:* $phone\n";
if (!empty($message)) {
    $text .= "💬 *Сообщение:* $message\n";
}
$text .= "🌐 *IP:* $ip\n";
$text .= "⏰ *Время:* $time";

// Отправка в Telegram
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
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Обработка ответа
if ($http_code === 200) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Ошибка отправки в Telegram"]);
    // Для отладки: error_log("Telegram error: $response");
}
?>