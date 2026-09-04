<?php

declare(strict_types=1);

final class CategoryService
{
    public static function forTimeline(int $timelineId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM categories WHERE timeline_id = :id ORDER BY sort_order, name'
        );
        $stmt->execute(['id' => $timelineId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(int $timelineId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO categories (timeline_id, name, color, icon, sort_order)
             VALUES (:tid, :name, :color, :icon, :ord)'
        );
        $stmt->execute([
            'tid' => $timelineId,
            'name' => $data['name'],
            'color' => $data['color'] ?? '#91A7C4',
            'icon' => $data['icon'] ?? 'diamond',
            'ord' => (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $timelineId, int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE categories SET name = :name, color = :color, icon = :icon
             WHERE id = :id AND timeline_id = :tid'
        );
        $stmt->execute([
            'name' => $data['name'],
            'color' => $data['color'] ?? '#91A7C4',
            'icon' => $data['icon'] ?? 'diamond',
            'id' => $id,
            'tid' => $timelineId,
        ]);
    }

    public static function destroy(int $timelineId, int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE events SET category_id = NULL WHERE category_id = :id AND timeline_id = :tid')
            ->execute(['id' => $id, 'tid' => $timelineId]);
        $pdo->prepare('DELETE FROM categories WHERE id = :id AND timeline_id = :tid')
            ->execute(['id' => $id, 'tid' => $timelineId]);
    }

    public static function seedDefaults(int $timelineId, array $names): void
    {
        $colors = array_keys(pastel_colors());
        foreach ($names as $i => $name) {
            self::create($timelineId, [
                'name' => $name,
                'color' => $colors[$i % count($colors)],
                'icon' => 'diamond',
                'sort_order' => $i,
            ]);
        }
    }
}
