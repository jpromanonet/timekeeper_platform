<?php

declare(strict_types=1);

final class TrashController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('trash/index', [
            'title' => 'Papelera',
            'currentNav' => 'trash',
            'timelines' => TimelineService::trashed(Auth::id()),
            'events' => EventService::trashed(Auth::id()),
        ]);
    }

    public function restoreTimeline(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        TimelineService::restore(Auth::id(), (int) $id);
        flash('success', 'Línea restaurada.');
        redirect('/papelera');
    }

    public function purgeTimeline(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        TimelineService::purge(Auth::id(), (int) $id);
        flash('success', 'Línea eliminada definitivamente.');
        redirect('/papelera');
    }

    public function restoreEvent(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        EventService::restore(Auth::id(), (int) $id);
        flash('success', 'Evento restaurado.');
        redirect('/papelera');
    }

    public function purgeEvent(string $id): void
    {
        Auth::requireLogin();
        verify_csrf();
        EventService::purge(Auth::id(), (int) $id);
        flash('success', 'Evento eliminado definitivamente.');
        redirect('/papelera');
    }
}
