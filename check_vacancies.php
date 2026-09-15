<?php

require __DIR__ . '/vendor/autoload.php';

use DouVacanciesBot\DouRssParser;
use DouVacanciesBot\Storage;
use DouVacanciesBot\TelegramNotifier;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->required(['TG_BOT_TOKEN', 'TG_CHAT_ID']);

$logFile = __DIR__ . '/logs/app.log';

function logMessage(string $logFile, string $message): void
{
    $line = sprintf('[%s] %s%s', date('Y-m-d H:i:s'), $message, PHP_EOL);
    file_put_contents($logFile, $line, FILE_APPEND);
}

$parser = new DouRssParser();
$storage = new Storage(__DIR__ . '/storage/vacancies.sqlite');
$notifier = new TelegramNotifier($_ENV['TG_BOT_TOKEN'], $_ENV['TG_CHAT_ID']);
$criteria = require __DIR__ . '/config/criteria.php';

foreach ($criteria as $criterion) {
    $category = $criterion['category'];
    $search = $criterion['search'];

    try {
        $vacancies = $parser->fetch($category, $search);
    } catch (\Throwable $e) {
        logMessage($logFile, "ПОМИЛКА RSS [{$category} / {$search}]: {$e->getMessage()}");
        continue;
    }

    $newVacancies = array_values(array_filter(
        $vacancies,
        fn (array $vacancy) => !$storage->isSent($vacancy['id'])
    ));

    if ($newVacancies === []) {
        continue;
    }

    $lines = [sprintf('<b>%s</b>', htmlspecialchars($category, ENT_QUOTES)), ''];

    foreach ($newVacancies as $index => $vacancy) {
        $lines[] = sprintf(
            '%d. <a href="%s">%s</a>',
            $index + 1,
            htmlspecialchars($vacancy['link'], ENT_QUOTES),
            htmlspecialchars($vacancy['title'], ENT_QUOTES)
        );
    }

    try {
        $notifier->send(implode("\n", $lines));

        foreach ($newVacancies as $vacancy) {
            $storage->markSent($vacancy['id']);
        }

        logMessage($logFile, sprintf(
            'Надіслано групу [%s]: %s',
            $category,
            implode(', ', array_column($newVacancies, 'id'))
        ));
    } catch (\Throwable $e) {
        logMessage($logFile, "ПОМИЛКА Telegram [{$category}]: {$e->getMessage()}");
    }

    usleep(500_000);
}
