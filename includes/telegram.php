<?php
date_default_timezone_set('Asia/Tashkent');

function sendTelegramNotification($message) {
    $botToken = "8692251308:AAEU8Uh15CzzG_zXjyVS62X6dYTyg1tYt3c"; 
    $chatID   = "25887542"; 

    if (empty($botToken) || empty($chatID)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = [
        'chat_id' => $chatID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    return @file_get_contents($url, false, $context);
}
?>