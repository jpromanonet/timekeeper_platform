<?php

declare(strict_types=1);

final class SeriesService
{
    public static function forCollection(int $userId, int $collectionId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT s.*,
                    (SELECT COUNT(*) FROM timelines t
                      WHERE t.series_id = s.id AND t.deleted_at IS NULL) AS timeline_count
             FROM `series` s
             WHERE s.user_id = :uid AND s.collection_id = :cid
             ORDER BY s.sort_order ASC, s.name ASC'
        );
        $stmt->execute(['uid' => $userId, 'cid' => $collectionId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function allForUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT s.id, s.collection_id, s.name, s.color
             FROM `series` s
             WHERE s.user_id = :uid
             ORDER BY s.sort_order ASC, s.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $userId, int $collectionId, int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM `series`
             WHERE id = :id AND user_id = :uid AND collection_id = :cid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId, 'cid' => $collectionId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function resolveForTimeline(int $userId, ?int $collectionId, mixed $seriesId): ?int
    {
        if ($collectionId === null) {
            return null;
        }
        $sid = (int) $seriesId;
        if ($sid < 1) {
            return null;
        }
        return self::find($userId, $collectionId, $sid) ? $sid : null;
    }

    public static function create(int $userId, int $collectionId, array $data): int
    {
        $max = Database::pdo()->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) FROM `series` WHERE collection_id = :cid AND user_id = :uid'
        );
        $max->execute(['cid' => $collectionId, 'uid' => $userId]);
        $order = (int) $max->fetchColumn() + 1;

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO `series` (user_id, collection_id, name, color, sort_order)
                 VALUES (:uid, :cid, :name, :color, :ord)'
            );
            $stmt->execute([
                'uid' => $userId,
                'cid' => $collectionId,
                'name' => $data['name'],
                'color' => self::color($data['color'] ?? null),
                'ord' => $order,
            ]);
        } catch (PDOException $e) {
            if (self::isDuplicate($e)) {
                throw new RuntimeException('Ya existe una serie con ese nombre en este archivo.');
            }
            throw $e;
        }

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $userId, int $collectionId, int $id, array $data): void
    {
        if (!self::find($userId, $collectionId, $id)) {
            return;
        }
        try {
            $stmt = Database::pdo()->prepare(
                'UPDATE `series`
                 SET name = :name, color = :color
                 WHERE id = :id AND user_id = :uid AND collection_id = :cid'
            );
            $stmt->execute([
                'name' => $data['name'],
                'color' => self::color($data['color'] ?? null),
                'id' => $id,
                'uid' => $userId,
                'cid' => $collectionId,
            ]);
        } catch (PDOException $e) {
            if (self::isDuplicate($e)) {
                throw new RuntimeException('Ya existe una serie con ese nombre en este archivo.');
            }
            throw $e;
        }
    }

    public static function destroy(int $userId, int $collectionId, int $id): void
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE timelines SET series_id = NULL
             WHERE series_id = :id AND collection_id = :cid AND user_id = :uid'
        )->execute(['id' => $id, 'cid' => $collectionId, 'uid' => $userId]);
        $pdo->prepare(
            'DELETE FROM `series` WHERE id = :id AND user_id = :uid AND collection_id = :cid'
        )->execute(['id' => $id, 'uid' => $userId, 'cid' => $collectionId]);
    }

    public static function destroyMany(int $userId, int $collectionId, array $ids): int
    {
        $n = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id < 1 || !self::find($userId, $collectionId, $id)) {
                continue;
            }
            self::destroy($userId, $collectionId, $id);
            $n++;
        }
        return $n;
    }

    /**
     * @param list<array<string,mixed>> $series
     * @param list<array<string,mixed>> $timelines
     * @return array{groups: list<array{serie: array<string,mixed>, items: list<array<string,mixed>>}>, loose: list<array<string,mixed>>}
     */
    public static function groupTimelines(array $series, array $timelines): array
    {
        $bySerie = [];
        foreach ($series as $serie) {
            $bySerie[(int) $serie['id']] = ['serie' => $serie, 'items' => []];
        }
        $loose = [];
        foreach ($timelines as $tl) {
            $sid = (int) ($tl['series_id'] ?? 0);
            if ($sid && isset($bySerie[$sid])) {
                $bySerie[$sid]['items'][] = $tl;
            } else {
                $loose[] = $tl;
            }
        }
        return [
            'groups' => array_values($bySerie),
            'loose' => $loose,
        ];
    }

    private static function color(?string $color): string
    {
        $color = (string) $color;
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return '#C9B58A';
        }
        return strtoupper($color);
    }

    private static function isDuplicate(PDOException $e): bool
    {
        return $e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate');
    }
}
