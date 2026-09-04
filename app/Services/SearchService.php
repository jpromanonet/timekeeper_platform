<?php

declare(strict_types=1);

final class SearchService
{
    public static function events(int $userId, string $q, int $limit = 80): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $like = '%' . $q . '%';
        $stmt = Database::pdo()->prepare(
            'SELECT e.id, e.title, e.summary, e.start_date, e.date_precision, e.is_milestone,
                    tl.id AS timeline_id, tl.name AS timeline_name,
                    c.name AS collection_name
             FROM events e
             INNER JOIN timelines tl ON tl.id = e.timeline_id
             LEFT JOIN collections c ON c.id = tl.collection_id
             WHERE tl.user_id = :uid AND e.deleted_at IS NULL AND tl.deleted_at IS NULL
               AND (
                    e.title LIKE :q OR e.summary LIKE :q OR e.description LIKE :q OR e.notes LIKE :q
                    OR e.location LIKE :q OR e.people LIKE :q
                    OR EXISTS (
                        SELECT 1 FROM event_tags et
                        INNER JOIN tags t ON t.id = et.tag_id
                        WHERE et.event_id = e.id AND t.name LIKE :q
                    )
               )
             ORDER BY (e.start_date IS NULL), e.start_date DESC, e.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId, 'q' => $like]);
        return $stmt->fetchAll() ?: [];
    }

    public static function palette(int $userId): array
    {
        return [
            'archives' => CollectionService::names($userId),
            'timelines' => TimelineService::names($userId),
        ];
    }
}
