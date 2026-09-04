<?php

declare(strict_types=1);

final class EventService
{
    public static function forTimeline(int $timelineId, array $filters = []): array
    {
        $sql = 'SELECT e.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                       (SELECT GROUP_CONCAT(tg.name ORDER BY tg.name SEPARATOR ",")
                          FROM event_tags et
                          INNER JOIN tags tg ON tg.id = et.tag_id
                         WHERE et.event_id = e.id) AS tags
                FROM events e
                LEFT JOIN categories c ON c.id = e.category_id
                WHERE e.timeline_id = :tid AND e.deleted_at IS NULL';
        $params = ['tid' => $timelineId];

        if (!empty($filters['category'])) {
            $sql .= ' AND e.category_id = :cid';
            $params['cid'] = (int) $filters['category'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (e.title LIKE :q OR e.summary LIKE :q OR e.description LIKE :q OR e.notes LIKE :q OR e.location LIKE :q OR e.people LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['from'])) {
            $sql .= ' AND e.start_date >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $sql .= ' AND e.start_date <= :to';
            $params['to'] = $filters['to'];
        }
        if (!empty($filters['tag'])) {
            $sql .= ' AND e.id IN (SELECT et2.event_id FROM event_tags et2 INNER JOIN tags t2 ON t2.id = et2.tag_id WHERE t2.name = :tag)';
            $params['tag'] = $filters['tag'];
        }

        $sql .= ' ORDER BY (e.start_date IS NULL) ASC, e.start_date ASC, e.start_time ASC, e.sort_order ASC, e.id ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $row['tag_list'] = $row['tags'] ? explode(',', (string) $row['tags']) : [];
        }
        unset($row);
        return $rows;
    }

    public static function findOwned(int $userId, int $eventId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, t.user_id, t.name AS timeline_name, t.date_format,
                    c.name AS collection_name, cat.name AS category_name, cat.color AS category_color
             FROM events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             LEFT JOIN collections c ON c.id = t.collection_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE e.id = :id AND t.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['tag_list'] = TagService::namesForEvent((int) $row['id']);
        $row['tags'] = implode(', ', $row['tag_list']);
        return $row;
    }

    public static function create(int $userId, int $timelineId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO events (
                timeline_id, category_id, title, summary, description, start_date, end_date,
                date_precision, end_date_precision, start_time, end_time, is_ongoing, is_milestone, is_favorite,
                location, people, url, video_url, icon, color, image, notes, sort_order
             ) VALUES (
                :tid, :cid, :title, :summary, :description, :start, :end,
                :prec, :eprec, :stime, :etime, :ongoing, :mile, 0,
                :location, :people, :url, :video, :icon, :color, :image, :notes, :ord
             )'
        );
        $stmt->execute(self::bind($timelineId, $data));
        $id = (int) Database::pdo()->lastInsertId();
        TagService::sync($userId, $id, (string) ($data['tags'] ?? ''));
        return $id;
    }

    public static function update(int $userId, int $eventId, int $timelineId, array $data): void
    {
        $sql = 'UPDATE events SET
                    category_id = :cid, title = :title, summary = :summary, description = :description,
                    start_date = :start, end_date = :end, date_precision = :prec, end_date_precision = :eprec,
                    start_time = :stime, end_time = :etime, is_ongoing = :ongoing, is_milestone = :mile,
                    location = :location, people = :people, url = :url, video_url = :video,
                    icon = :icon, color = :color, notes = :notes, sort_order = :ord';
        $params = self::bind($timelineId, $data);
        unset($params['tid']);
        if (array_key_exists('image', $data) && $data['image']) {
            $sql .= ', image = :image';
        } else {
            unset($params['image']);
        }
        $sql .= ' WHERE id = :id AND timeline_id = :tid AND deleted_at IS NULL';
        $params['id'] = $eventId;
        $params['tid'] = $timelineId;
        Database::pdo()->prepare($sql)->execute($params);
        TagService::sync($userId, $eventId, (string) ($data['tags'] ?? ''));
    }

    public static function softDelete(int $userId, int $eventId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             SET e.deleted_at = NOW()
             WHERE e.id = :id AND t.user_id = :uid AND e.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
    }

    public static function restore(int $userId, int $eventId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             SET e.deleted_at = NULL
             WHERE e.id = :id AND t.user_id = :uid'
        );
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
    }

    public static function purge(int $userId, int $eventId): void
    {
        $stmt = Database::pdo()->prepare(
            'DELETE e FROM events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE e.id = :id AND t.user_id = :uid'
        );
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
    }

    public static function toggleFavorite(int $userId, int $eventId): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             SET e.is_favorite = IF(e.is_favorite = 1, 0, 1)
             WHERE e.id = :id AND t.user_id = :uid AND e.deleted_at IS NULL'
        );
        $stmt->execute(['id' => $eventId, 'uid' => $userId]);
    }

    public static function favorites(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, tl.name AS timeline_name, tl.id AS timeline_id, c.name AS collection_name,
                    cat.name AS category_name, cat.color AS category_color
             FROM events e
             INNER JOIN timelines tl ON tl.id = e.timeline_id
             LEFT JOIN collections c ON c.id = tl.collection_id
             LEFT JOIN categories cat ON cat.id = e.category_id
             WHERE tl.user_id = :uid AND e.is_favorite = 1 AND e.deleted_at IS NULL AND tl.deleted_at IS NULL
             ORDER BY (e.start_date IS NULL), e.start_date DESC, e.id DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function trashed(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, tl.name AS timeline_name, tl.id AS timeline_id
             FROM events e
             INNER JOIN timelines tl ON tl.id = e.timeline_id
             WHERE tl.user_id = :uid AND e.deleted_at IS NOT NULL AND tl.deleted_at IS NULL
             ORDER BY e.deleted_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function recent(int $userId, int $limit = 8): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT e.*, tl.name AS timeline_name, tl.id AS timeline_id, c.name AS collection_name
             FROM events e
             INNER JOIN timelines tl ON tl.id = e.timeline_id
             LEFT JOIN collections c ON c.id = tl.collection_id
             WHERE tl.user_id = :uid AND e.deleted_at IS NULL AND tl.deleted_at IS NULL
             ORDER BY e.updated_at DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function jsonPayload(array $events, string $dateFormat): array
    {
        $out = [];
        foreach ($events as $ev) {
            $precision = (string) ($ev['date_precision'] ?? 'day');
            $out[] = [
                'id' => (int) $ev['id'],
                'title' => $ev['title'],
                'summary' => $ev['summary'],
                'start' => $ev['start_date'],
                'end' => $ev['end_date'],
                'precision' => $precision,
                'label' => DatePrecision::formatSpan(
                    $ev['start_date'],
                    $precision,
                    $ev['end_date'],
                    $ev['end_date_precision'] ?? $precision,
                    (bool) $ev['is_ongoing'],
                    $dateFormat
                ),
                'is_milestone' => (int) $ev['is_milestone'] === 1,
                'is_ongoing' => (int) $ev['is_ongoing'] === 1,
                'is_favorite' => (int) ($ev['is_favorite'] ?? 0) === 1,
                'color' => $ev['color'] ?: ($ev['category_color'] ?? '#C9B58A'),
                'category' => $ev['category_name'] ?? null,
                'category_id' => $ev['category_id'] !== null ? (int) $ev['category_id'] : null,
                'tags' => $ev['tag_list'] ?? [],
                'location' => $ev['location'],
                'image' => !empty($ev['image']) ? media_url((string) $ev['image']) : null,
                'url' => url('/lineas/' . (int) $ev['timeline_id'] . '?evento=' . (int) $ev['id']),
            ];
        }
        return $out;
    }

    private static function bind(int $timelineId, array $data): array
    {
        return [
            'tid' => $timelineId,
            'cid' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'] ?? null,
            'start' => $data['start_date'] ?? null,
            'end' => $data['end_date'] ?? null,
            'prec' => $data['date_precision'] ?? 'day',
            'eprec' => $data['end_date_precision'] ?? null,
            'stime' => $data['start_time'] ?? null,
            'etime' => $data['end_time'] ?? null,
            'ongoing' => !empty($data['is_ongoing']) ? 1 : 0,
            'mile' => !empty($data['is_milestone']) ? 1 : 0,
            'location' => $data['location'] ?? null,
            'people' => $data['people'] ?? null,
            'url' => $data['url'] ?? null,
            'video' => $data['video_url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'image' => $data['image'] ?? null,
            'notes' => $data['notes'] ?? null,
            'ord' => (int) ($data['sort_order'] ?? 0),
        ];
    }
}
