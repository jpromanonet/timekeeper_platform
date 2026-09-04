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
        try {
            $cover = UploadService::storeImage($_FILES['cover'] ?? null, 'covers', 'col-' . Auth::id() . '-' . time());
            if ($cover) {
                $data['cover_image'] = $cover;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
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
        view('collections/show', [
            'title' => $archive['name'],
            'currentNav' => 'archives',
            'archive' => $archive,
            'timelines' => TimelineService::forCollection(Auth::id(), (int) $id),
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
        try {
            $cover = UploadService::storeImage($_FILES['cover'] ?? null, 'covers', 'col-' . $id);
            if ($cover) {
                $data['cover_image'] = $cover;
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
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
