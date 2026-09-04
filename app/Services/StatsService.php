<?php

declare(strict_types=1);

final class StatsService
{
    public static function dashboard(int $userId): array
    {
        $pdo = Database::pdo();

        $count = static function (string $sql) use ($pdo, $userId): int {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['uid' => $userId]);
            return (int) $stmt->fetchColumn();
        };

        $timelines = $count('SELECT COUNT(*) FROM timelines WHERE user_id = :uid AND deleted_at IS NULL');
        $events = $count(
            'SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL'
        );
        $archives = $count('SELECT COUNT(*) FROM collections WHERE user_id = :uid');
        $tags = $count('SELECT COUNT(*) FROM tags WHERE user_id = :uid');
        $milestones = $count(
            'SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL AND e.is_milestone = 1'
        );
        $favorites = $count(
            'SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL AND e.is_favorite = 1'
        );

        $largest = $pdo->prepare(
            'SELECT t.id, t.name, COUNT(e.id) AS event_count
             FROM timelines t
             LEFT JOIN events e ON e.timeline_id = t.id AND e.deleted_at IS NULL
             WHERE t.user_id = :uid AND t.deleted_at IS NULL
             GROUP BY t.id, t.name
             ORDER BY event_count DESC, t.name
             LIMIT 1'
        );
        $largest->execute(['uid' => $userId]);
        $largestRow = $largest->fetch() ?: null;

        $oldest = $pdo->prepare(
            'SELECT id, name, created_at FROM timelines
             WHERE user_id = :uid AND deleted_at IS NULL
             ORDER BY created_at ASC, id ASC
             LIMIT 1'
        );
        $oldest->execute(['uid' => $userId]);
        $oldestRow = $oldest->fetch() ?: null;

        $decade = $pdo->prepare(
            'SELECT decade_year,
                    CONCAT(decade_year, "s") AS decade_label,
                    COUNT(*) AS total
             FROM (
                SELECT FLOOR(YEAR(e.start_date) / 10) * 10 AS decade_year
                FROM events e
                INNER JOIN timelines t ON t.id = e.timeline_id
                WHERE t.user_id = :uid
                  AND e.deleted_at IS NULL
                  AND t.deleted_at IS NULL
                  AND e.start_date IS NOT NULL
             ) decades
             GROUP BY decade_year
             ORDER BY total DESC, decade_year
             LIMIT 1'
        );
        $decade->execute(['uid' => $userId]);
        $decadeRow = $decade->fetch() ?: null;

        return [
            'timelines' => $timelines,
            'events' => $events,
            'archives' => $archives,
            'tags' => $tags,
            'milestones' => $milestones,
            'favorites' => $favorites,
            'largest' => $largestRow,
            'oldest' => $oldestRow,
            'decade' => $decadeRow,
        ];
    }

    public static function metrics(int $userId): array
    {
        $pdo = Database::pdo();
        $cards = self::dashboard($userId);

        $count = static function (string $sql) use ($pdo, $userId): int {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['uid' => $userId]);
            return (int) $stmt->fetchColumn();
        };

        $cards['ongoing'] = $count(
            'SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL AND e.is_ongoing = 1'
        );
        $cards['undated'] = $count(
            'SELECT COUNT(*) FROM events e INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL
               AND (e.start_date IS NULL OR e.date_precision = "unknown")'
        );
        $cards['drafts'] = $count(
            'SELECT COUNT(*) FROM timelines WHERE user_id = :uid AND deleted_at IS NULL AND status = "draft"'
        );
        $cards['archived_timelines'] = $count(
            'SELECT COUNT(*) FROM timelines WHERE user_id = :uid AND deleted_at IS NULL AND status = "archived"'
        );

        $byArchive = $pdo->prepare(
            'SELECT COALESCE(c.name, "Sin archivo") AS label,
                    COALESCE(c.color, "#AAA7A0") AS color,
                    COUNT(e.id) AS total
             FROM events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             LEFT JOIN collections c ON c.id = t.collection_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL
             GROUP BY c.id, c.name, c.color
             ORDER BY total DESC, label'
        );
        $byArchive->execute(['uid' => $userId]);

        $byPrecision = $pdo->prepare(
            'SELECT e.date_precision AS label, COUNT(*) AS total
             FROM events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL
             GROUP BY e.date_precision
             ORDER BY total DESC'
        );
        $byPrecision->execute(['uid' => $userId]);

        $byTimeline = $pdo->prepare(
            'SELECT t.id, t.name AS label, t.color, COUNT(e.id) AS total
             FROM timelines t
             LEFT JOIN events e ON e.timeline_id = t.id AND e.deleted_at IS NULL
             WHERE t.user_id = :uid AND t.deleted_at IS NULL
             GROUP BY t.id, t.name, t.color
             ORDER BY total DESC, t.name
             LIMIT 12'
        );
        $byTimeline->execute(['uid' => $userId]);

        $byYear = $pdo->prepare(
            'SELECT year_num AS label, COUNT(*) AS total
             FROM (
                SELECT YEAR(e.start_date) AS year_num
                FROM events e
                INNER JOIN timelines t ON t.id = e.timeline_id
                WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL AND e.start_date IS NOT NULL
             ) years
             GROUP BY year_num
             ORDER BY year_num'
        );
        $byYear->execute(['uid' => $userId]);

        $composition = $pdo->prepare(
            'SELECT FLOOR(YEAR(e.start_date) / 10) * 10 AS decade_year,
                    SUM(CASE WHEN e.is_milestone = 1 THEN 1 ELSE 0 END) AS milestones,
                    SUM(CASE WHEN e.is_milestone = 0 THEN 1 ELSE 0 END) AS regulars,
                    SUM(CASE WHEN e.is_ongoing = 1 THEN 1 ELSE 0 END) AS ongoing
             FROM events e
             INNER JOIN timelines t ON t.id = e.timeline_id
             WHERE t.user_id = :uid AND e.deleted_at IS NULL AND t.deleted_at IS NULL AND e.start_date IS NOT NULL
             GROUP BY FLOOR(YEAR(e.start_date) / 10) * 10
             ORDER BY decade_year'
        );
        $composition->execute(['uid' => $userId]);

        $byStatus = $pdo->prepare(
            'SELECT status AS label, COUNT(*) AS total
             FROM timelines
             WHERE user_id = :uid AND deleted_at IS NULL
             GROUP BY status
             ORDER BY total DESC'
        );
        $byStatus->execute(['uid' => $userId]);

        $precisionLabels = DatePrecision::labels();
        $precisionRows = [];
        foreach ($byPrecision->fetchAll() ?: [] as $row) {
            $key = (string) $row['label'];
            $precisionRows[] = [
                'label' => $precisionLabels[$key] ?? $key,
                'total' => (int) $row['total'],
            ];
        }

        $statusLabels = [
            'active' => 'Activas',
            'draft' => 'Borrador',
            'archived' => 'Archivadas',
        ];
        $statusRows = [];
        foreach ($byStatus->fetchAll() ?: [] as $row) {
            $key = (string) $row['label'];
            $statusRows[] = [
                'label' => $statusLabels[$key] ?? $key,
                'total' => (int) $row['total'],
            ];
        }

        $compRows = [];
        foreach ($composition->fetchAll() ?: [] as $row) {
            $year = (int) $row['decade_year'];
            $compRows[] = [
                'label' => 'Década de ' . $year,
                'regulars' => (int) $row['regulars'],
                'milestones' => (int) $row['milestones'],
                'ongoing' => (int) $row['ongoing'],
            ];
        }

        return [
            'cards' => $cards,
            'by_archive' => $byArchive->fetchAll() ?: [],
            'by_precision' => $precisionRows,
            'by_timeline' => $byTimeline->fetchAll() ?: [],
            'by_year' => $byYear->fetchAll() ?: [],
            'composition' => $compRows,
            'by_status' => $statusRows,
        ];
    }
}
