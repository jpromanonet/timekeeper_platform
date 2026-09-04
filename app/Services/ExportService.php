<?php

declare(strict_types=1);

final class ExportService
{
    public static function timelineJson(int $userId, int $timelineId): array
    {
        $timeline = TimelineService::find($userId, $timelineId);
        if (!$timeline) {
            throw new RuntimeException('Línea no encontrada.');
        }
        $categories = CategoryService::forTimeline($timelineId);
        $events = EventService::forTimeline($timelineId);
        $payloadEvents = [];
        foreach ($events as $ev) {
            $payloadEvents[] = [
                'title' => $ev['title'],
                'summary' => $ev['summary'],
                'description' => $ev['description'],
                'start_date' => $ev['start_date'],
                'end_date' => $ev['end_date'],
                'date_precision' => $ev['date_precision'],
                'end_date_precision' => $ev['end_date_precision'],
                'start_time' => $ev['start_time'],
                'end_time' => $ev['end_time'],
                'is_ongoing' => (int) $ev['is_ongoing'] === 1,
                'is_milestone' => (int) $ev['is_milestone'] === 1,
                'location' => $ev['location'],
                'people' => $ev['people'],
                'url' => $ev['url'],
                'video_url' => $ev['video_url'],
                'color' => $ev['color'],
                'notes' => $ev['notes'],
                'category' => $ev['category_name'],
                'tags' => $ev['tag_list'] ?? [],
            ];
        }

        return [
            'app' => 'Timekeeper',
            'version' => (string) app_config('version', '1.0.0'),
            'exported_at' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
            'timeline' => [
                'name' => $timeline['name'],
                'description' => $timeline['description'],
                'icon' => $timeline['icon'],
                'color' => $timeline['color'],
                'default_view' => $timeline['default_view'],
                'date_format' => $timeline['date_format'],
                'status' => $timeline['status'],
                'archive' => $timeline['collection_name'],
                'series' => $timeline['series_name'] ?? null,
            ],
            'categories' => array_map(static fn (array $c): array => [
                'name' => $c['name'],
                'color' => $c['color'],
                'icon' => $c['icon'],
            ], $categories),
            'events' => $payloadEvents,
        ];
    }

    public static function sendJson(int $userId, int $timelineId): never
    {
        $data = self::timelineJson($userId, $timelineId);
        $filename = slugify((string) $data['timeline']['name']) . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public static function sendCsv(int $userId, int $timelineId): never
    {
        $timeline = TimelineService::find($userId, $timelineId);
        if (!$timeline) {
            throw new RuntimeException('Línea no encontrada.');
        }
        $events = EventService::forTimeline($timelineId);
        $filename = slugify((string) $timeline['name']) . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['title', 'summary', 'start_date', 'end_date', 'precision', 'category', 'tags', 'milestone', 'ongoing', 'location']);
        foreach ($events as $ev) {
            fputcsv($out, [
                $ev['title'],
                $ev['summary'],
                $ev['start_date'],
                $ev['end_date'],
                $ev['date_precision'],
                $ev['category_name'],
                implode(';', $ev['tag_list'] ?? []),
                (int) $ev['is_milestone'],
                (int) $ev['is_ongoing'],
                $ev['location'],
            ]);
        }
        fclose($out);
        exit;
    }

    public static function sendPdf(int $userId, int $timelineId): never
    {
        $timeline = TimelineService::find($userId, $timelineId);
        if (!$timeline) {
            throw new RuntimeException('Línea no encontrada.');
        }
        $events = EventService::forTimeline($timelineId);
        $categories = CategoryService::forTimeline($timelineId);
        $dateFormat = (string) ($timeline['date_format'] ?: 'd/m/Y');
        $who = (string) (Auth::user()['name'] ?? '');
        $binary = TimelinePdfService::render($timeline, $events, $categories, $dateFormat, $who);
        $filename = slugify((string) $timeline['name']) . '.pdf';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) strlen($binary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $binary;
        exit;
    }
}
