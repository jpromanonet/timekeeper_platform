<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new Router();

$auth = new AuthController();
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);
$router->get('/registro', [$auth, 'showRegister']);
$router->post('/registro', [$auth, 'register']);
$router->post('/logout', [$auth, 'logout']);
$router->get('/recuperar', [$auth, 'showForgot']);
$router->post('/recuperar', [$auth, 'forgot']);
$router->get('/restablecer/{token}', [$auth, 'showReset']);
$router->post('/restablecer/{token}', [$auth, 'reset']);

$dash = new DashboardController();
$router->get('/', [$dash, 'index']);

$archives = new CollectionController();
$router->get('/archivos', [$archives, 'index']);
$router->get('/archivos/nuevo', [$archives, 'create']);
$router->post('/archivos', [$archives, 'store']);
$router->post('/archivos/eliminar-lote', [$archives, 'destroyMany']);
$router->get('/archivos/{id}', [$archives, 'show']);
$router->get('/archivos/{id}/editar', [$archives, 'edit']);
$router->get('/archivos/{id}/series/nueva', [$archives, 'createSeries']);
$router->get('/archivos/{id}/series/{sid}/editar', [$archives, 'editSeries']);
$router->post('/archivos/{id}', [$archives, 'update']);
$router->post('/archivos/{id}/eliminar', [$archives, 'destroy']);
$router->post('/archivos/{id}/series', [$archives, 'storeSeries']);
$router->post('/archivos/{id}/series/eliminar-lote', [$archives, 'destroyManySeries']);
$router->post('/archivos/{id}/series/{sid}', [$archives, 'updateSeries']);
$router->post('/archivos/{id}/series/{sid}/eliminar', [$archives, 'destroySeries']);

$lines = new TimelineController();
$router->get('/lineas', [$lines, 'index']);
$router->get('/lineas/nueva', [$lines, 'create']);
$router->post('/lineas', [$lines, 'store']);
$router->post('/lineas/eliminar-lote', [$lines, 'destroyMany']);
$router->get('/lineas/{id}', [$lines, 'show']);
$router->get('/lineas/{id}/editar', [$lines, 'edit']);
$router->post('/lineas/{id}', [$lines, 'update']);
$router->post('/lineas/{id}/serie', [$lines, 'assignSeries']);
$router->post('/lineas/{id}/eliminar', [$lines, 'destroy']);
$router->post('/lineas/{id}/duplicar', [$lines, 'duplicate']);
$router->get('/lineas/{id}/exportar.json', [$lines, 'exportJson']);
$router->get('/lineas/{id}/exportar.csv', [$lines, 'exportCsv']);
$router->get('/lineas/{id}/exportar.pdf', [$lines, 'exportPdf']);
$router->post('/lineas/{id}/categorias', [$lines, 'storeCategory']);
$router->post('/lineas/{id}/categorias/{cid}', [$lines, 'updateCategory']);
$router->post('/lineas/{id}/categorias/{cid}/eliminar', [$lines, 'destroyCategory']);

$events = new EventController();
$router->post('/lineas/{id}/eventos', [$events, 'store']);
$router->post('/lineas/{id}/eventos/{eid}', [$events, 'update']);
$router->post('/lineas/{id}/eventos/{eid}/eliminar', [$events, 'destroy']);
$router->post('/lineas/{id}/eventos/{eid}/favorito', [$events, 'favorite']);

$router->get('/buscar', [new SearchController(), 'index']);
$router->get('/metricas', [new MetricsController(), 'index']);
$router->get('/favoritos', [new FavoritesController(), 'index']);

$trash = new TrashController();
$router->get('/papelera', [$trash, 'index']);
$router->post('/papelera/lineas/{id}/restaurar', [$trash, 'restoreTimeline']);
$router->post('/papelera/lineas/{id}/purgar', [$trash, 'purgeTimeline']);
$router->post('/papelera/eventos/{id}/restaurar', [$trash, 'restoreEvent']);
$router->post('/papelera/eventos/{id}/purgar', [$trash, 'purgeEvent']);

$settings = new SettingsController();
$router->get('/ajustes', [$settings, 'index']);
$router->post('/ajustes/perfil', [$settings, 'updateProfile']);
$router->post('/ajustes/password', [$settings, 'updatePassword']);
$router->post('/ajustes/preferencias', [$settings, 'updatePreferences']);

$router->get('/media/{folder}/{file}', [new MediaController(), 'show']);

$router->dispatch(request_method(), $_SERVER['REQUEST_URI'] ?? '/');
