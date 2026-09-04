<?php

declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        Auth::requireLogin();
        $uid = Auth::id();
        view('dashboard/index', [
            'title' => 'Archivo',
            'currentNav' => 'dashboard',
            'stats' => StatsService::dashboard($uid),
            'archives' => CollectionService::all($uid),
            'ungrouped' => TimelineService::ungrouped($uid),
            'recent' => EventService::recent($uid),
        ]);
    }
}
