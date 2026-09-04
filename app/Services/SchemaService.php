<?php

declare(strict_types=1);

final class SchemaService
{
    public static function ensure(): void
    {
        $pdo = Database::pdo();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS `series` (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              user_id BIGINT UNSIGNED NOT NULL,
              collection_id BIGINT UNSIGNED NOT NULL,
              name VARCHAR(160) NOT NULL,
              color VARCHAR(16) NOT NULL DEFAULT \'#C9B58A\',
              sort_order INT NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_series_col_name (collection_id, name),
              KEY idx_series_user (user_id, collection_id, sort_order),
              CONSTRAINT fk_series_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
              CONSTRAINT fk_series_col FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!self::hasColumn($pdo, 'timelines', 'series_id')) {
            $pdo->exec('ALTER TABLE timelines ADD COLUMN series_id BIGINT UNSIGNED NULL AFTER collection_id');
        }

        if (!self::hasIndex($pdo, 'timelines', 'idx_tl_series')) {
            $pdo->exec('ALTER TABLE timelines ADD KEY idx_tl_series (series_id)');
        }

        if (!self::hasConstraint($pdo, 'timelines', 'fk_tl_series')) {
            $pdo->exec(
                'ALTER TABLE timelines
                 ADD CONSTRAINT fk_tl_series FOREIGN KEY (series_id) REFERENCES `series`(id) ON DELETE SET NULL'
            );
        }
    }

    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :col
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'col' => $column]);
        return (bool) $stmt->fetchColumn();
    }

    private static function hasIndex(PDO $pdo, string $table, string $name): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :name
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'name' => $name]);
        return (bool) $stmt->fetchColumn();
    }

    private static function hasConstraint(PDO $pdo, string $table, string $name): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :name
             LIMIT 1'
        );
        $stmt->execute(['table' => $table, 'name' => $name]);
        return (bool) $stmt->fetchColumn();
    }
}
