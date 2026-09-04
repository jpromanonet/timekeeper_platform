<?php

declare(strict_types=1);

final class PreferenceService
{
    public static function defaults(): array
    {
        return [
            'default_view' => 'vertical',
            'density' => 'compact',
            'date_format' => 'd/m/Y',
            'theme' => 'archivist',
            'interface_sounds' => 0,
        ];
    }

    public static function ensure(int $userId): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT IGNORE INTO user_preferences (user_id, default_view, density, date_format, theme, interface_sounds)
             VALUES (:id, :view, :density, :format, :theme, 0)'
        );
        $stmt->execute([
            'id' => $userId,
            'view' => 'vertical',
            'density' => 'compact',
            'format' => 'd/m/Y',
            'theme' => 'archivist',
        ]);
    }

    public static function update(int $userId, array $data): void
    {
        self::ensure($userId);
        $views = ['vertical', 'horizontal'];
        $densities = ['compact', 'comfortable'];
        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y'];
        $themeKeys = array_keys(themes());

        $stmt = Database::pdo()->prepare(
            'UPDATE user_preferences
             SET default_view = :view, density = :density, date_format = :format, theme = :theme, interface_sounds = :sounds
             WHERE user_id = :id'
        );
        $stmt->execute([
            'view' => in_array($data['default_view'] ?? '', $views, true) ? $data['default_view'] : 'vertical',
            'density' => in_array($data['density'] ?? '', $densities, true) ? $data['density'] : 'compact',
            'format' => in_array($data['date_format'] ?? '', $formats, true) ? $data['date_format'] : 'd/m/Y',
            'theme' => in_array($data['theme'] ?? '', $themeKeys, true) ? $data['theme'] : 'archivist',
            'sounds' => !empty($data['interface_sounds']) ? 1 : 0,
            'id' => $userId,
        ]);
    }
}
