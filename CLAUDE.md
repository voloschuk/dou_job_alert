# DOU Vacancies Bot

PHP-скрипт, що парсить нові вакансії з RSS-фіду jobs.dou.ua за заданими
критеріями (категорія + ключове слово пошуку) і надсилає їх у Telegram-чат,
згрупованими за категоріями. Запускається періодично через cron (кожні
2 години).

## Структура

- `check_vacancies.php` — точка входу: для кожного критерію з
  `config/criteria.php` отримує вакансії, фільтрує вже надіслані (SQLite),
  формує одне групове повідомлення на категорію і надсилає в Telegram.
  Порожні групи (без нових вакансій) не надсилаються.
- `src/DouRssParser.php` — завантаження та парсинг RSS-фіду
  `https://jobs.dou.ua/vacancies/feeds/` через `curl` + `SimpleXMLElement`.
  ID вакансії витягується з URL вакансії.
- `src/Storage.php` — SQLite (`storage/vacancies.sqlite`), таблиця
  `sent_vacancies`, відстежує вже надіслані ID вакансій (дедуплікація).
- `src/TelegramNotifier.php` — надсилання повідомлень через Telegram Bot API
  (`sendMessage`, HTML-розмітка, прев'ю посилань вимкнено).
- `config/criteria.php` — масив критеріїв фільтрації `['category' => ...,
  'search' => ...]`. Заголовок групи в Telegram-повідомленні = значення
  `category`.
- `logs/app.log` — лог надісланих груп/помилок (пишеться скриптом).
- `logs/cron.log` — stdout/stderr cron-запусків.

## Конфігурація

`.env` (не в git, див. `.env.example`): `TG_BOT_TOKEN`, `TG_CHAT_ID`.

## Запуск

```
composer install
php check_vacancies.php
```

Cron (кожні 2 години):
```
0 */2 * * * /usr/bin/php /var/www/dou_vacancies_bot/check_vacancies.php >> /var/www/dou_vacancies_bot/logs/cron.log 2>&1
```

## Залежності середовища

Потрібні PHP-розширення: `curl`, `sqlite3`, `simplexml`.

## Нотатки

- Вакансія позначається як надіслана в SQLite лише після успішного
  надсилання групового повідомлення в Telegram — щоб не втратити вакансію
  при збої мережі.
- Додавання нової категорії — додати новий елемент у
  `config/criteria.php`, окремого коду не потребує.
