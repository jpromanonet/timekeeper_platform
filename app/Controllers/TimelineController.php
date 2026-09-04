<?php

declare(strict_types=1);

final class TimelineController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('timelines/index', [
            'title' => 'Líneas de tiempo',
            'currentNav' => 'timelines',
            'timelines' => TimelineService::all(Auth::id()),
            'archives' => CollectionService::names(Auth::id()),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        $prefs = Auth::prefs();
        view('timelines/form', [
            'title' => 'Nueva línea',
            'currentNav' => 'timelines',
            'timeline' => null,
            'archives' => CollectionService::all(Auth::id()),
            'seriesList' => SeriesService::allForUser(Auth::id()),
            'preselect' => int_or_null(input('archivo')),
            'preselectSeries' => int_or_null(input('serie')),
            'prefs' => $prefs,
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $data = $this->payload();
        if ($data['name'] === '') {
            flash('error', 'La línea necesita un nombre.');
            redirect('/lineas/nueva');
        }
        try {
            $cover = UploadService::storeImage($_FILES['cover'] ?? null, 'covers', 'tl-' . Auth::id() . '-' . time());
            if ($cover) {
                $data['cover_image'] = $cover;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/lineas/nueva');
        }
        $id = TimelineService::create(Auth::id(), $data);
        $cats = trim((string) input('categories', ''));
        if ($cats !== '') {
            CategoryService::seedDefaults($id, TagService::parse($cats));
        }
        flash('success', 'Línea creada.');
        redirect('/lineas/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $timeline = TimelineService::find(Auth::id(), (int) $id);
        if (!$timeline) {
            flash('error', 'Línea no encontrada.');
            redirect('/lineas');
        }

        $viewMode = (string) ($_GET['vista'] ?? $timeline['default_view'] ?? Auth::prefs()['default_view']);
        if (!in_array($viewMode, ['vertical', 'horizontal'], true)) {
            $viewMode = 'vertical';
        }

        $from = DatePrecision::parse((string) ($_GET['desde'] ?? ''));
        $to = DatePrecision::parse((string) ($_GET['hasta'] ?? ''));
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category' => int_or_null($_GET['categoria'] ?? null),
            'tag' => null_if_blank((string) ($_GET['tag'] ?? '')),
            'from' => $from['date'],
            'to' => $to['date'],
        ];

        $events = EventService::forTimeline((int) $id, $filters);
        $categories = CategoryService::forTimeline((int) $id);
        $dateFormat = (string) ($timeline['date_format'] ?: Auth::prefs()['date_format']);

        $editEvent = null;
        $eventoId = int_or_null($_GET['evento'] ?? null);
        if ($eventoId) {
            $found = EventService::findOwned(Auth::id(), $eventoId);
            if ($found && (int) $found['timeline_id'] === (int) $id) {
                $editEvent = $found;
            }
        }

        $groups = $this->groupVertical($events, $dateFormat);

        view('timelines/show', [
            'title' => $timeline['name'],
            'currentNav' => 'timelines',
            'timeline' => $timeline,
            'categories' => $categories,
            'events' => $events,
            'groups' => $groups,
            'filters' => $filters,
            'viewMode' => $viewMode,
            'dateFormat' => $dateFormat,
            'editEvent' => $editEvent,
            'openDrawer' => isset($_GET['nuevo']) || $editEvent !== null,
            'jsEvents' => EventService::jsonPayload($events, $dateFormat),
            'allTags' => TagService::forUser(Auth::id()),
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $timeline = TimelineService::find(Auth::id(), (int) $id);
        if (!$timeline) {
            redirect('/lineas');
        }
        view('timelines/form', [
            'title' => 'Editar línea',
            'currentNav' => 'timelines',
            'timeline' => $timeline,
            'archives' => CollectionService::all(Auth::id()),
            'seriesList' => SeriesService::allForUser(Auth::id()),
            'preselect' => null,
            'preselectSeries' => null,
            'prefs' => Auth::prefs(),
            'categories' => CategoryService::forTimeline((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!TimelineService::find(Auth::id(), (int) $id)) {
            redirect('/lineas');
        }
        $data = $this->payload();
        if ($data['name'] === '') {
            flash('error', 'La línea necesita un nombre.');
            redirect('/lineas/' . $id . '/editar');
        }
        try {
            $cover = UploadService::storeImage($_FILES['cover'] ?? null, 'covers', 'tl-' . $id);
            if ($cover) {
                $data['cover_image'] = $cover;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/lineas/' . $id . '/editar');
        }
        TimelineService::update(Auth::id(), (int) $id, $data);
        flash('success', 'Línea actualizada.');
        redirect('/lineas/' . $id);
    }

    public function assignSeries(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $timeline = TimelineService::find(Auth::id(), (int) $id);
        if (!$timeline) {
            redirect('/lineas');
        }
        TimelineService::assignSeries(Auth::id(), (int) $id, int_or_null(input('series_id')));
        flash('success', 'Serie actualizada.');
        $fallback = !empty($timeline['collection_id'])
            ? '/archivos/' . (int) $timeline['collection_id']
            : '/lineas';
        redirect(safe_return_path($fallback));
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        TimelineService::softDelete(Auth::id(), (int) $id);
        flash('success', 'Línea enviada a la papelera.');
        redirect('/lineas');
    }

    public function destroyMany(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $n = TimelineService::softDeleteMany(Auth::id(), input_id_list());
        if ($n < 1) {
            flash('error', 'No hay líneas seleccionadas.');
            redirect(safe_return_path('/lineas'));
        }
        flash('success', $n === 1
            ? 'Línea enviada a la papelera.'
            : $n . ' líneas enviadas a la papelera.');
        redirect(safe_return_path('/lineas'));
    }

    public function duplicate(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $newId = TimelineService::duplicate(Auth::id(), (int) $id);
        if (!$newId) {
            flash('error', 'No se pudo duplicar.');
            redirect('/lineas');
        }
        flash('success', 'Copia creada como borrador.');
        redirect('/lineas/' . $newId);
    }

    public function exportJson(string $id): void
    {
        Auth::requireLogin();
        ExportService::sendJson(Auth::id(), (int) $id);
    }

    public function exportCsv(string $id): void
    {
        Auth::requireLogin();
        ExportService::sendCsv(Auth::id(), (int) $id);
    }

    public function exportPdf(string $id): void
    {
        Auth::requireLogin();
        try {
            ExportService::sendPdf(Auth::id(), (int) $id);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/lineas');
        }
    }

    public function storeCategory(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!TimelineService::find(Auth::id(), (int) $id)) {
            redirect('/lineas');
        }
        $name = mb_substr(trim((string) input('name', '')), 0, 120);
        if ($name === '') {
            flash('error', 'La categoría necesita un nombre.');
            redirect('/lineas/' . $id . '/editar');
        }
        $color = (string) input('color', '#91A7C4');
        CategoryService::create((int) $id, ['name' => $name, 'color' => $color]);
        flash('success', 'Categoría agregada.');
        redirect('/lineas/' . $id . '/editar');
    }

    public function updateCategory(string $id, string $cid): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!TimelineService::find(Auth::id(), (int) $id)) {
            redirect('/lineas');
        }
        CategoryService::update((int) $id, (int) $cid, [
            'name' => mb_substr(trim((string) input('name', '')), 0, 120),
            'color' => (string) input('color', '#91A7C4'),
        ]);
        flash('success', 'Categoría actualizada.');
        redirect('/lineas/' . $id . '/editar');
    }

    public function destroyCategory(string $id, string $cid): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!TimelineService::find(Auth::id(), (int) $id)) {
            redirect('/lineas');
        }
        CategoryService::destroy((int) $id, (int) $cid);
        flash('success', 'Categoría eliminada.');
        redirect('/lineas/' . $id . '/editar');
    }

    private function payload(): array
    {
        $prefs = Auth::prefs();
        $icons = array_keys(archive_icons());
        $icon = (string) input('icon', 'hourglass');
        $color = (string) input('color', '#C9B58A');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#C9B58A';
        }
        $view = (string) input('default_view', $prefs['default_view']);
        $format = (string) input('date_format', $prefs['date_format']);
        $status = (string) input('status', 'active');
        $cid = int_or_null(input('collection_id'));
        if ($cid) {
            $col = CollectionService::find(Auth::id(), $cid);
            if (!$col) {
                $cid = null;
            }
        }
        $sid = SeriesService::resolveForTimeline(Auth::id(), $cid, input('series_id'));
        $start = DatePrecision::parse((string) input('start_date', ''));
        $end = DatePrecision::parse((string) input('end_date', ''));

        return [
            'collection_id' => $cid,
            'series_id' => $sid,
            'name' => mb_substr(trim((string) input('name', '')), 0, 190),
            'description' => null_if_blank((string) input('description', '')),
            'icon' => in_array($icon, $icons, true) ? $icon : 'hourglass',
            'color' => $color,
            'default_view' => in_array($view, ['vertical', 'horizontal'], true) ? $view : 'vertical',
            'date_format' => in_array($format, ['d/m/Y', 'Y-m-d', 'm/d/Y'], true) ? $format : 'd/m/Y',
            'start_date' => $start['date'],
            'end_date' => $end['date'],
            'status' => in_array($status, ['active', 'archived', 'draft'], true) ? $status : 'active',
        ];
    }

    private function groupVertical(array $events, string $dateFormat): array
    {
        $groups = [];
        foreach ($events as $ev) {
            $key = 'unknown';
            $label = 'Sin fecha';
            if (!empty($ev['start_date'])) {
                $year = (int) substr((string) $ev['start_date'], 0, 4);
                $prec = (string) $ev['date_precision'];
                if ($prec === 'century') {
                    $key = 'c-' . (int) ceil($year / 100);
                    $label = DatePrecision::format($ev['start_date'], 'century', $dateFormat);
                } elseif ($prec === 'decade') {
                    $key = 'd-' . (intdiv($year, 10) * 10);
                    $label = DatePrecision::format($ev['start_date'], 'decade', $dateFormat);
                } else {
                    $key = 'y-' . $year;
                    $label = (string) $year;
                }
            }
            if (!isset($groups[$key])) {
                $groups[$key] = ['label' => $label, 'items' => []];
            }
            $groups[$key]['items'][] = $ev;
        }
        return $groups;
    }
}
