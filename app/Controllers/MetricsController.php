<?php

declare(strict_types=1);

final class MetricsController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('metrics/index', [
            'title' => 'Métricas',
            'currentNav' => 'metrics',
            'metrics' => StatsService::metrics(Auth::id()),
        ]);
    }
}
