<?php

declare(strict_types=1);

final class SearchController
{
    public function index(): void
    {
        Auth::requireLogin();
        $q = trim((string) ($_GET['q'] ?? ''));
        $results = $q !== '' ? SearchService::events(Auth::id(), $q) : [];
        view('search/index', [
            'title' => 'Buscar',
            'currentNav' => 'search',
            'q' => $q,
            'results' => $results,
            'dateFormat' => Auth::prefs()['date_format'],
        ]);
    }
}
