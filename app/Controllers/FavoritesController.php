<?php

declare(strict_types=1);

final class FavoritesController
{
    public function index(): void
    {
        Auth::requireLogin();
        view('favorites/index', [
            'title' => 'Favoritos',
            'currentNav' => 'favorites',
            'events' => EventService::favorites(Auth::id()),
            'dateFormat' => Auth::prefs()['date_format'],
        ]);
    }
}
