<?php

namespace DouVacanciesBot;

class TelegramNotifier
{
    private const API_URL = 'https://api.telegram.org/bot%s/sendMessage';

    public function __construct(
        private readonly string $botToken,
        private readonly string $chatId,
    ) {
    }

    public function send(string $text): bool
    {
        $url = sprintf(self::API_URL, $this->botToken);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => $this->chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '' || $httpCode !== 200) {
            throw new \RuntimeException(
                "Помилка надсилання в Telegram (HTTP {$httpCode}): {$error} {$response}"
            );
        }

        return true;
    }
}
