<?php

declare(strict_types=1);

final class TagService
{
    public static function sync(int $userId, int $eventId, string $raw): void
    {
        $names = self::parse($raw);
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM event_tags WHERE event_id = :id')->execute(['id' => $eventId]);

        if (!$names) {
            return;
        }

        $find = $pdo->prepare('SELECT id FROM tags WHERE user_id = :uid AND name = :name LIMIT 1');
        $ins = $pdo->prepare('INSERT INTO tags (user_id, name) VALUES (:uid, :name)');
        $link = $pdo->prepare('INSERT IGNORE INTO event_tags (event_id, tag_id) VALUES (:e, :t)');

        foreach ($names as $name) {
            $find->execute(['uid' => $userId, 'name' => $name]);
            $id = $find->fetchColumn();
            if (!$id) {
                $ins->execute(['uid' => $userId, 'name' => $name]);
                $id = $pdo->lastInsertId();
            }
            $link->execute(['e' => $eventId, 't' => $id]);
        }
    }

    public static function namesForEvent(int $eventId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.name FROM tags t INNER JOIN event_tags et ON et.tag_id = t.id WHERE et.event_id = :id ORDER BY t.name'
        );
        $stmt->execute(['id' => $eventId]);
        return array_column($stmt->fetchAll() ?: [], 'name');
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM tags WHERE user_id = :uid ORDER BY name');
        $stmt->execute(['uid' => $userId]);
        return array_column($stmt->fetchAll() ?: [], 'name');
    }

    /** @return list<string> */
    public static function parse(string $raw): array
    {
        $parts = preg_split('/[,;#]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $name = trim(ltrim(trim($part), '#'));
            if ($name === '') {
                continue;
            }
            $name = mb_substr($name, 0, 80);
            $key = mb_strtolower($name);
            $out[$key] = $name;
        }
        return array_values($out);
    }
}
