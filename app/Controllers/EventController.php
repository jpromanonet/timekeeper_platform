<?php

declare(strict_types=1);

final class EventController
{
    public function store(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $timeline = TimelineService::find(Auth::id(), (int) $id);
        if (!$timeline) {
            redirect('/lineas');
        }
        $data = $this->payload((int) $id, (string) $timeline['date_format']);
        if ($data['title'] === '') {
            flash('error', 'El evento necesita un título.');
            redirect('/lineas/' . $id . '?nuevo=1');
        }
        try {
            $image = UploadService::storeImage($_FILES['image'] ?? null, 'events', 'ev-' . $id . '-' . time());
            if ($image) {
                $data['image'] = $image;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/lineas/' . $id . '?nuevo=1');
        }
        EventService::create(Auth::id(), (int) $id, $data);
        flash('success', 'Evento guardado.');
        redirect('/lineas/' . $id);
    }

    public function update(string $id, string $eid): void
    {
        Auth::requireLogin();
        verify_csrf();
        $timeline = TimelineService::find(Auth::id(), (int) $id);
        $event = EventService::findOwned(Auth::id(), (int) $eid);
        if (!$timeline || !$event || (int) $event['timeline_id'] !== (int) $id) {
            redirect('/lineas');
        }
        $data = $this->payload((int) $id, (string) $timeline['date_format']);
        if ($data['title'] === '') {
            flash('error', 'El evento necesita un título.');
            redirect('/lineas/' . $id . '?evento=' . $eid);
        }
        try {
            $image = UploadService::storeImage($_FILES['image'] ?? null, 'events', 'ev-' . $eid);
            if ($image) {
                $data['image'] = $image;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/lineas/' . $id . '?evento=' . $eid);
        }
        EventService::update(Auth::id(), (int) $eid, (int) $id, $data);
        flash('success', 'Evento actualizado.');
        redirect('/lineas/' . $id);
    }

    public function destroy(string $id, string $eid): void
    {
        Auth::requireLogin();
        verify_csrf();
        EventService::softDelete(Auth::id(), (int) $eid);
        flash('success', 'Evento enviado a la papelera.');
        redirect('/lineas/' . $id);
    }

    public function favorite(string $id, string $eid): void
    {
        Auth::requireLogin();
        verify_csrf();
        EventService::toggleFavorite(Auth::id(), (int) $eid);
        $back = (string) input('back', '/lineas/' . $id);
        if (str_starts_with($back, '/')) {
            redirect($back);
        }
        redirect('/lineas/' . $id);
    }

    private function payload(int $timelineId, string $dateFormat): array
    {
        $start = DatePrecision::parse((string) input('start_input', ''), DatePrecision::normalizePrecision((string) input('date_precision', '')), $dateFormat);
        $endRaw = (string) input('end_input', '');
        $end = $endRaw !== ''
            ? DatePrecision::parse($endRaw, DatePrecision::normalizePrecision((string) input('end_date_precision', '')), $dateFormat)
            : ['date' => null, 'precision' => null];

        $cid = int_or_null(input('category_id'));
        if ($cid) {
            $ok = false;
            foreach (CategoryService::forTimeline($timelineId) as $cat) {
                if ((int) $cat['id'] === $cid) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $cid = null;
            }
        }

        $color = null_if_blank((string) input('color', ''));
        if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = null;
        }

        return [
            'category_id' => $cid,
            'title' => mb_substr(trim((string) input('title', '')), 0, 255),
            'summary' => null_if_blank((string) input('summary', '')),
            'description' => null_if_blank((string) input('description', '')),
            'start_date' => $start['date'],
            'end_date' => empty(input('is_ongoing')) ? $end['date'] : null,
            'date_precision' => $start['precision'],
            'end_date_precision' => empty(input('is_ongoing')) ? $end['precision'] : null,
            'start_time' => DatePrecision::parseTime((string) input('start_time', '')),
            'end_time' => DatePrecision::parseTime((string) input('end_time', '')),
            'is_ongoing' => (bool) input('is_ongoing'),
            'is_milestone' => (bool) input('is_milestone'),
            'location' => null_if_blank((string) input('location', '')),
            'people' => null_if_blank((string) input('people', '')),
            'url' => null_if_blank((string) input('url', '')),
            'video_url' => null_if_blank((string) input('video_url', '')),
            'color' => $color,
            'notes' => null_if_blank((string) input('notes', '')),
            'tags' => (string) input('tags', ''),
        ];
    }
}
