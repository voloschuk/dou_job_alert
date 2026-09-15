<?php

namespace DouVacanciesBot;

use RuntimeException;
use SimpleXMLElement;

class DouRssParser
{
    private const FEED_URL = 'https://jobs.dou.ua/vacancies/feeds/';

    /**
     * @return array<int, array{id: string, title: string, link: string, description: string}>
     */
    public function fetch(string $category, string $search): array
    {
        $url = self::FEED_URL . '?' . http_build_query([
            'category' => $category,
            'search' => $search,
        ]);

        $xml = $this->download($url);
        $feed = @simplexml_load_string($xml);

        if ($feed === false || !isset($feed->channel->item)) {
            throw new RuntimeException("Не вдалося розібрати RSS-фід за URL: {$url}");
        }

        $vacancies = [];

        foreach ($feed->channel->item as $item) {
            $link = (string) $item->link;
            $id = $this->extractId($link);

            if ($id === null) {
                continue;
            }

            $vacancies[] = [
                'id' => $id,
                'title' => trim((string) $item->title),
                'link' => $link,
                'description' => trim((string) $item->description),
            ];
        }

        return $vacancies;
    }

    private function download(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DouVacanciesBot/1.0)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new RuntimeException("Помилка завантаження RSS ({$url}): {$error}");
        }

        if ($httpCode !== 200) {
            throw new RuntimeException("RSS повернув HTTP {$httpCode} ({$url})");
        }

        return $response;
    }

    private function extractId(string $link): ?string
    {
        if (preg_match('~/vacancies/(\d+)~', $link, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
