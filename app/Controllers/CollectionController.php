<?php

declare(strict_types=1);

final class CollectionController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('collections/index', [
            'title' => 'Archivos',
            'currentNav' => 'archives',
            'archives' => CollectionService::all(Auth::id()),
            'ungrouped' => TimelineService::ungrouped(Auth::id()),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        view('collections/form', [
            'title' => 'Nuevo archivo',
            'currentNav' => 'archives',
            'archive' => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $data = $this->payload();
        if ($data['name'] === '') {
            flash('error', 'El archivo necesita un nombre.');
            redirect('/archivos/nuevo');
        }
        $id = CollectionService::create(Auth::id(), $data);
        flash('success', 'Archivo creado.');
        redirect('/archivos/' . $id);
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            flash('error', 'Archivo no encontrado.');
            redirect('/archivos');
        }
        $series = SeriesService::forCollection(Auth::id(), (int) $id);
        $timelines = TimelineService::forCollection(Auth::id(), (int) $id);
        $grouped = SeriesService::groupTimelines($series, $timelines);

        $filterRaw = trim((string) ($_GET['serie'] ?? ''));
        $serieFilter = 'all';
        if ($filterRaw === 'none') {
            $serieFilter = 'none';
        } elseif ($filterRaw !== '' && ctype_digit($filterRaw)) {
            $sid = (int) $filterRaw;
            foreach ($series as $serie) {
                if ((int) $serie['id'] === $sid) {
                    $serieFilter = $sid;
                    break;
                }
            }
        }

        $visible = $grouped;
        if ($serieFilter === 'none') {
            $visible = ['groups' => [], 'loose' => $grouped['loose']];
        } elseif (is_int($serieFilter)) {
            $match = [];
            foreach ($grouped['groups'] as $group) {
                if ((int) $group['serie']['id'] === $serieFilter) {
                    $match[] = $group;
                    break;
                }
            }
            $visible = ['groups' => $match, 'loose' => []];
        }

        $lineFilter = int_or_null($_GET['linea'] ?? null);
        if ($lineFilter) {
            $owned = false;
            foreach ($timelines as $tl) {
                if ((int) $tl['id'] === $lineFilter) {
                    $owned = true;
                    break;
                }
            }
            if (!$owned) {
                $lineFilter = null;
            }
        }
        if ($lineFilter) {
            $keep = static fn (array $tl): bool => (int) $tl['id'] === $lineFilter;
            foreach ($visible['groups'] as $i => $group) {
                $visible['groups'][$i]['items'] = array_values(array_filter($group['items'], $keep));
            }
            $visible['groups'] = array_values(array_filter(
                $visible['groups'],
                static fn (array $group): bool => $group['items'] !== []
            ));
            $visible['loose'] = array_values(array_filter($visible['loose'], $keep));
        }

        view('collections/show', [
            'title' => $archive['name'],
            'currentNav' => 'archives',
            'archive' => $archive,
            'series' => $series,
            'timelines' => $timelines,
            'grouped' => $visible,
            'serieFilter' => $serieFilter,
            'lineFilter' => $lineFilter,
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireLogin();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            flash('error', 'Archivo no encontrado.');
            redirect('/archivos');
        }
        view('collections/form', [
            'title' => 'Editar archivo',
            'currentNav' => 'archives',
            'archive' => $archive,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            redirect('/archivos');
        }
        $data = $this->payload();
        if ($data['name'] === '') {
            flash('error', 'El archivo necesita un nombre.');
            redirect('/archivos/' . $id . '/editar');
        }
        CollectionService::update(Auth::id(), (int) $id, $data);
        flash('success', 'Archivo actualizado.');
        redirect('/archivos/' . $id);
    }

    public function destroy(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        CollectionService::destroy(Auth::id(), (int) $id);
        flash('success', 'Archivo eliminado. Las líneas quedaron independientes.');
        redirect('/archivos');
    }

    public function destroyMany(): void
    {
        Auth::requireLogin();
        verify_csrf();
        $n = CollectionService::destroyMany(Auth::id(), input_id_list());
        if ($n < 1) {
            flash('error', 'No hay archivos seleccionados.');
            redirect(safe_return_path('/archivos'));
        }
        flash('success', $n === 1
            ? 'Archivo eliminado. Las líneas quedaron independientes.'
            : $n . ' archivos eliminados. Las líneas quedaron independientes.');
        redirect(safe_return_path('/archivos'));
    }

    public function createSeries(string $id): void
    {
        Auth::requireLogin();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            flash('error', 'Archivo no encontrado.');
            redirect('/archivos');
        }
        $colors = array_keys(pastel_colors());
        $existing = SeriesService::forCollection(Auth::id(), (int) $id);
        view('series/form', [
            'title' => 'Nueva serie',
            'currentNav' => 'archives',
            'archive' => $archive,
            'serie' => null,
            'defaultColor' => $colors[count($existing) % count($colors)],
        ]);
    }

    public function editSeries(string $id, string $sid): void
    {
        Auth::requireLogin();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            redirect('/archivos');
        }
        $serie = SeriesService::find(Auth::id(), (int) $id, (int) $sid);
        if (!$serie) {
            flash('error', 'Serie no encontrada.');
            redirect('/archivos/' . $id);
        }
        view('series/form', [
            'title' => 'Editar serie',
            'currentNav' => 'archives',
            'archive' => $archive,
            'serie' => $serie,
            'defaultColor' => (string) $serie['color'],
        ]);
    }

    public function storeSeries(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            redirect('/archivos');
        }
        $name = mb_substr(trim((string) input('name', '')), 0, 160);
        if ($name === '') {
            flash('error', 'La serie necesita un nombre.');
            redirect('/archivos/' . $id . '/series/nueva');
        }
        try {
            $newId = SeriesService::create(Auth::id(), (int) $id, $this->seriesPayload((string) $archive['color']));
            flash('success', 'Serie creada.');
            redirect('/archivos/' . $id . '?serie=' . $newId);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/archivos/' . $id . '/series/nueva');
        }
    }

    public function updateSeries(string $id, string $sid): void
    {
        Auth::requireLogin();
        verify_csrf();
        $archive = CollectionService::find(Auth::id(), (int) $id);
        if (!$archive) {
            redirect('/archivos');
        }
        $name = mb_substr(trim((string) input('name', '')), 0, 160);
        if ($name === '') {
            flash('error', 'La serie necesita un nombre.');
            redirect('/archivos/' . $id . '/series/' . $sid . '/editar');
        }
        try {
            SeriesService::update(Auth::id(), (int) $id, (int) $sid, $this->seriesPayload());
            flash('success', 'Serie actualizada.');
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/archivos/' . $id . '/series/' . $sid . '/editar');
        }
        redirect('/archivos/' . $id);
    }

    public function destroySeries(string $id, string $sid): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!CollectionService::find(Auth::id(), (int) $id)) {
            redirect('/archivos');
        }
        SeriesService::destroy(Auth::id(), (int) $id, (int) $sid);
        flash('success', 'Serie eliminada. Las líneas quedan en el archivo, sin serie.');
        redirect(safe_return_path('/archivos/' . $id));
    }

    public function destroyManySeries(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        if (!CollectionService::find(Auth::id(), (int) $id)) {
            redirect('/archivos');
        }
        $n = SeriesService::destroyMany(Auth::id(), (int) $id, input_id_list());
        if ($n < 1) {
            flash('error', 'No hay series seleccionadas.');
            redirect(safe_return_path('/archivos/' . $id));
        }
        flash('success', $n === 1
            ? 'Serie eliminada. Las líneas quedan en el archivo, sin serie.'
            : $n . ' series eliminadas. Las líneas quedan en el archivo, sin serie.');
        redirect(safe_return_path('/archivos/' . $id));
    }

    private function seriesPayload(?string $fallbackColor = null): array
    {
        $color = (string) input('color', $fallbackColor ?? '#C9B58A');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = $fallbackColor ?? '#C9B58A';
        }
        return [
            'name' => mb_substr(trim((string) input('name', '')), 0, 160),
            'color' => $color,
        ];
    }

    private function payload(): array
    {
        $icons = array_keys(archive_icons());
        $icon = (string) input('icon', 'scroll');
        $color = (string) input('color', '#91A7C4');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#91A7C4';
        }
        return [
            'name' => mb_substr(trim((string) input('name', '')), 0, 160),
            'description' => null_if_blank((string) input('description', '')),
            'icon' => in_array($icon, $icons, true) ? $icon : 'scroll',
            'color' => $color,
        ];
    }
}
