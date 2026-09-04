<?php

declare(strict_types=1);

final class TimelineService
{
    public static function all(int $userId, bool $includeDeleted = false): array
    {
        $sql = 'SELECT t.*, c.name AS collection_name, c.color AS collection_color,
                       (SELECT COUNT(*) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL) AS event_count,
                       (SELECT MIN(e.start_date) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL AND e.start_date IS NOT NULL) AS first_event,
                       (SELECT MAX(COALESCE(e.end_date, e.start_date)) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL) AS last_event
                FROM timelines t
                LEFT JOIN collections c ON c.id = t.collection_id
                WHERE t.user_id = :uid';
        if (!$includeDeleted) {
            $sql .= ' AND t.deleted_at IS NULL';
        }
        $sql .= ' ORDER BY (t.collection_id IS NULL), c.sort_order, t.sort_order, t.name';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function ungrouped(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL) AS event_count
             FROM timelines t
             WHERE t.user_id = :uid AND t.collection_id IS NULL AND t.deleted_at IS NULL
             ORDER BY t.sort_order, t.name'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function forCollection(int $userId, int $collectionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL) AS event_count,
                    (SELECT MIN(e.start_date) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL AND e.start_date IS NOT NULL) AS first_event,
                    (SELECT MAX(COALESCE(e.end_date, e.start_date)) FROM events e WHERE e.timeline_id = t.id AND e.deleted_at IS NULL) AS last_event
             FROM timelines t
             WHERE t.user_id = :uid AND t.collection_id = :cid AND t.deleted_at IS NULL
             ORDER BY t.sort_order, t.name'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $collectionId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $userId, int $id, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT t.*, c.name AS collection_name, c.color AS collection_color
                FROM timelines t
                LEFT JOIN collections c ON c.id = t.collection_id
                WHERE t.id = :id AND t.user_id = :uid';
        if (!$includeDeleted) {
            $sql .= ' AND t.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $slug = self::uniqueSlug($userId, slugify((string) $data['name']));
        $stmt = Database::pdo()->prepare(
            'INSERT INTO timelines
                (user_id, collection_id, name, slug, description, icon, color, cover_image, default_view, date_format, start_date, end_date, status, sort_order)
             VALUES
                (:uid, :cid, :name, :slug, :description, :icon, :color, :cover, :view, :format, :start, :end, :status, :ord)'
        );
        $stmt->execute([
            'uid' => $userId,
            'cid' => $data['collection_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'cover' => $data['cover_image'] ?? null,
            'view' => $data['default_view'],
            'format' => $data['date_format'],
            'start' => $data['start_date'],
            'end' => $data['end_date'],
            'status' => $data['status'],
            'ord' => (int) ($data['sort_order'] ?? 0),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $userId, int $id, array $data): void
    {
        $current = self::find($userId, $id);
        if (!$current) {
            return;
        }
        $slug = $current['slug'];
        if (slugify((string) $data['name']) !== slugify((string) $current['name'])) {
            $slug = self::uniqueSlug($userId, slugify((string) $data['name']), $id);
        }
        $stmt = Database::pdo()->prepare(
            'UPDATE timelines SET
                collection_id = :cid, name = :name, slug = :slug, description = :description,
                icon = :icon, color = :color, cover_image = COALESCE(:cover, cover_image),
                default_view = :view, date_format = :format, start_date = :start, end_date = :end, status = :status
             WHERE id = :id AND user_id = :uid AND deleted_at IS NULL'
        );
        $stmt->execute([
            'cid' => $data['collection_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
            'icon' => $data['icon'],
            'color' => $data['color'],
            'cover' => $data['cover_image'] ?? null,
            'view' => $data['default_view'],
            'format' => $data['date_format'],
            'start' => $data['start_date'],
            'end' => $data['end_date'],
            'status' => $data['status'],
            'id' => $id,
            'uid' => $userId,
        ]);
    }

    public static function softDelete(int $userId, int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE timelines SET deleted_at = NOW() WHERE id = :id AND user_id = :uid AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function softDeleteMany(int $userId, array $ids): int
    {
        $n = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id < 1 || !self::find($userId, $id)) {
                continue;
            }
            self::softDelete($userId, $id);
            $n++;
        }
        return $n;
    }

    public static function restore(int $userId, int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE timelines SET deleted_at = NULL WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function purge(int $userId, int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM timelines WHERE id = :id AND user_id = :uid');
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function trashed(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT t.*, c.name AS collection_name
             FROM timelines t
             LEFT JOIN collections c ON c.id = t.collection_id
             WHERE t.user_id = :uid AND t.deleted_at IS NOT NULL
             ORDER BY t.deleted_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function duplicate(int $userId, int $id): ?int
    {
        $source = self::find($userId, $id);
        if (!$source) {
            return null;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $newId = self::create($userId, [
                'collection_id' => $source['collection_id'] !== null ? (int) $source['collection_id'] : null,
                'name' => $source['name'] . ' (copia)',
                'description' => $source['description'],
                'icon' => $source['icon'],
                'color' => $source['color'],
                'cover_image' => $source['cover_image'],
                'default_view' => $source['default_view'],
                'date_format' => $source['date_format'],
                'start_date' => $source['start_date'],
                'end_date' => $source['end_date'],
                'status' => 'draft',
                'sort_order' => (int) $source['sort_order'],
            ]);

            $cats = Database::pdo()->prepare('SELECT * FROM categories WHERE timeline_id = :id ORDER BY sort_order, id');
            $cats->execute(['id' => $id]);
            $catMap = [];
            $insCat = $pdo->prepare(
                'INSERT INTO categories (timeline_id, name, color, icon, sort_order) VALUES (:tid, :name, :color, :icon, :ord)'
            );
            foreach ($cats->fetchAll() ?: [] as $cat) {
                $insCat->execute([
                    'tid' => $newId,
                    'name' => $cat['name'],
                    'color' => $cat['color'],
                    'icon' => $cat['icon'],
                    'ord' => $cat['sort_order'],
                ]);
                $catMap[(int) $cat['id']] = (int) $pdo->lastInsertId();
            }

            $events = $pdo->prepare('SELECT * FROM events WHERE timeline_id = :id AND deleted_at IS NULL ORDER BY id');
            $events->execute(['id' => $id]);
            $insEv = $pdo->prepare(
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
            $evMap = [];
            foreach ($events->fetchAll() ?: [] as $ev) {
                $cid = $ev['category_id'] !== null ? ($catMap[(int) $ev['category_id']] ?? null) : null;
                $insEv->execute([
                    'tid' => $newId,
                    'cid' => $cid,
                    'title' => $ev['title'],
                    'summary' => $ev['summary'],
                    'description' => $ev['description'],
                    'start' => $ev['start_date'],
                    'end' => $ev['end_date'],
                    'prec' => $ev['date_precision'],
                    'eprec' => $ev['end_date_precision'],
                    'stime' => $ev['start_time'],
                    'etime' => $ev['end_time'],
                    'ongoing' => $ev['is_ongoing'],
                    'mile' => $ev['is_milestone'],
                    'location' => $ev['location'],
                    'people' => $ev['people'],
                    'url' => $ev['url'],
                    'video' => $ev['video_url'],
                    'icon' => $ev['icon'],
                    'color' => $ev['color'],
                    'image' => $ev['image'],
                    'notes' => $ev['notes'],
                    'ord' => $ev['sort_order'],
                ]);
                $evMap[(int) $ev['id']] = (int) $pdo->lastInsertId();
            }

            if ($evMap) {
                $placeholders = implode(',', array_fill(0, count($evMap), '?'));
                $tagRows = $pdo->prepare("SELECT event_id, tag_id FROM event_tags WHERE event_id IN ({$placeholders})");
                $tagRows->execute(array_keys($evMap));
                $insTag = $pdo->prepare('INSERT IGNORE INTO event_tags (event_id, tag_id) VALUES (:e, :t)');
                foreach ($tagRows->fetchAll() ?: [] as $row) {
                    $newEventId = $evMap[(int) $row['event_id']] ?? null;
                    if ($newEventId) {
                        $insTag->execute(['e' => $newEventId, 't' => $row['tag_id']]);
                    }
                }
            }

            $pdo->commit();
            return $newId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function names(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name FROM timelines WHERE user_id = :uid AND deleted_at IS NULL ORDER BY name'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    private static function uniqueSlug(int $userId, string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i = 2;
        while (true) {
            $sql = 'SELECT id FROM timelines WHERE user_id = :uid AND slug = :slug';
            $params = ['uid' => $userId, 'slug' => $slug];
            if ($ignoreId) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            if (!$stmt->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }
}
