<?php

declare(strict_types=1);

final class CollectionService
{
    public static function all(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM timelines t WHERE t.collection_id = c.id AND t.user_id = c.user_id AND t.deleted_at IS NULL) AS timeline_count,
                    (SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
                      WHERE t.collection_id = c.id AND t.user_id = c.user_id AND t.deleted_at IS NULL AND e.deleted_at IS NULL) AS event_count
             FROM collections c
             WHERE c.user_id = :uid
             ORDER BY c.sort_order ASC, c.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $userId, int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM timelines t WHERE t.collection_id = c.id AND t.deleted_at IS NULL) AS timeline_count,
                    (SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
                      WHERE t.collection_id = c.id AND t.deleted_at IS NULL AND e.deleted_at IS NULL) AS event_count
             FROM collections c
             WHERE c.id = :id AND c.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $max = Database::pdo()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM collections WHERE user_id = :uid');
        $max->execute(['uid' => $userId]);
        $order = (int) $max->fetchColumn() + 1;

        $stmt = Database::pdo()->prepare(
            'INSERT INTO collections (user_id, name, description, icon, color, cover_image, sort_order)
             VALUES (:uid, :name, :description, :icon, :color, :cover, :ord)'
        );
        $stmt->execute([
            'uid' => $userId,
            'name' => $data['name'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'cover' => $data['cover_image'] ?? null,
            'ord' => $order,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $userId, int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE collections
             SET name = :name, description = :description, icon = :icon, color = :color, cover_image = COALESCE(:cover, cover_image)
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'cover' => $data['cover_image'] ?? null,
            'id' => $id,
            'uid' => $userId,
        ]);
    }

    public static function destroy(int $userId, int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE timelines SET collection_id = NULL WHERE collection_id = :id AND user_id = :uid')
            ->execute(['id' => $id, 'uid' => $userId]);
        $pdo->prepare('DELETE FROM collections WHERE id = :id AND user_id = :uid')
            ->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function names(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT id, name FROM collections WHERE user_id = :uid ORDER BY sort_order, name');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }
}
