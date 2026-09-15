<?php

namespace DouVacanciesBot;

use SQLite3;

class Storage
{
    private SQLite3 $db;

    public function __construct(string $sqliteFilePath)
    {
        $this->db = new SQLite3($sqliteFilePath);
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS sent_vacancies (
                id TEXT PRIMARY KEY,
                sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public function isSent(string $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM sent_vacancies WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $result = $stmt->execute();

        return $result->fetchArray() !== false;
    }

    public function markSent(string $id): void
    {
        $stmt = $this->db->prepare('INSERT OR IGNORE INTO sent_vacancies (id) VALUES (:id)');
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->execute();
    }
}
