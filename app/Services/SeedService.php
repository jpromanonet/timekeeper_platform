<?php

declare(strict_types=1);

final class SeedService
{
    public static function demoEmail(): string
    {
        return 'archivo@timekeeper.local';
    }

    public static function demoPassword(): string
    {
        return 'timekeeper';
    }

    public static function run(PDO $pdo): array
    {
        $email = self::demoEmail();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $exists->execute(['email' => $email]);
        $userId = (int) ($exists->fetchColumn() ?: 0);

        if ($userId < 1) {
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)');
            $ins->execute([
                'name' => 'Archivista',
                'email' => $email,
                'hash' => password_hash(self::demoPassword(), PASSWORD_DEFAULT),
            ]);
            $userId = (int) $pdo->lastInsertId();
            PreferenceService::ensure($userId);
        }

        $countTl = $pdo->prepare('SELECT COUNT(*) FROM timelines WHERE user_id = :uid');
        $countTl->execute(['uid' => $userId]);
        if ((int) $countTl->fetchColumn() > 0) {
            return ['user_id' => $userId, 'seeded' => false];
        }

        $vida = self::collection($userId, 'Mi vida', 'Crónica personal, oficio y estudios.', 'hourglass', '#C3909B', 1);
        $soup = self::collection($userId, 'Soup IT', 'Historia de la empresa, clientes y productos.', 'castle', '#91A7C4', 2);
        $libros = self::collection($userId, 'Libros', 'Universos narrativos en construcción.', 'book', '#A99BC2', 3);
        $historia = self::collection($userId, 'Historia', 'Argentina, guerras y computación.', 'scroll', '#C9B58A', 4);

        $personal = self::timeline($userId, $vida, 'Vida personal', 'Mudanzas, familia y bases.', 'hourglass', '#C3909B', ['Personales', 'Familia', 'Ciudad']);
        $carrera = self::timeline($userId, $vida, 'Carrera profesional', 'Oficios, cargos y giros.', 'sword', '#B78972', ['Trabajo', 'Oficio', 'Hitos']);
        $estudios = self::timeline($userId, $vida, 'Estudios', 'Formación y lecturas que marcaron época.', 'book', '#9FB59D', ['Estudios', 'Lectura']);

        $histEmp = self::timeline($userId, $soup, 'Historia de la empresa', 'De la idea al primer cliente.', 'castle', '#91A7C4', ['Hitos', 'Equipo', 'Negocios']);
        $clientes = self::timeline($userId, $soup, 'Clientes', 'Relaciones comerciales.', 'coin', '#C9B58A', ['Clientes', 'Contratos']);
        $productos = self::timeline($userId, $soup, 'Productos', 'Puestito y el resto del catálogo.', 'chest', '#A99BC2', ['Productos', 'Lanzamientos']);
        $infra = self::timeline($userId, $soup, 'Infraestructura', 'Servidores, lab y herramientas internas.', 'shield', '#9FB59D', ['Infraestructura', 'Home lab']);

        $t35 = self::timeline($userId, $libros, 'Treinta y Cinco', 'Línea del universo principal.', 'star', '#A99BC2', ['Diego', 'Astrid', 'Argentina', 'Cabina']);
        $vamp = self::timeline($userId, $libros, 'Universo Vampiros', 'Cronología oculta.', 'flame', '#C3909B', ['Personajes', 'Lore']);
        $saga = self::timeline($userId, $libros, 'Saga Argentina', 'Historia y ficción entrelazadas.', 'map', '#B78972', ['Argentina', 'Economía']);

        $arg = self::timeline($userId, $historia, 'Argentina', 'Hitos políticos y sociales.', 'map', '#C9B58A', ['Política', 'Crisis', 'Cultura']);
        $ww2 = self::timeline($userId, $historia, 'Segunda Guerra Mundial', '1939–1945 y consecuencias.', 'shield', '#B78972', ['Europa', 'Frentes']);
        $comp = self::timeline($userId, $historia, 'Historia de la computación', 'De ENIAC a la web.', 'hourglass', '#91A7C4', ['Hardware', 'Redes', 'Software']);

        $sueltas = self::timeline($userId, null, 'Notas sueltas', 'Línea independiente, sin archivo.', 'scroll', '#AAA7A0', ['Notas']);

        $cat = static fn (int $tid, string $name): ?int => self::categoryId($tid, $name);

        self::event($userId, $personal, [
            'title' => 'Compra departamento',
            'summary' => 'Diego establece su primera base.',
            'start' => '1991', 'precision' => 'year',
            'location' => 'Buenos Aires',
            'category' => $cat($personal, 'Ciudad'),
            'tags' => 'BuenosAires, 1991',
            'milestone' => true,
        ]);
        self::event($userId, $personal, [
            'title' => 'Primera inversión',
            'summary' => 'Un movimiento que cambia el tablero.',
            'start' => '1992', 'precision' => 'year',
            'category' => $cat($personal, 'Personales'),
            'tags' => 'finanzas',
        ]);
        self::event($userId, $carrera, [
            'title' => 'Engineering Manager',
            'summary' => 'Cargo actual en Soup IT.',
            'start' => '08/2025', 'precision' => 'month',
            'ongoing' => true,
            'category' => $cat($carrera, 'Oficio'),
            'tags' => 'SoupIT, oficio',
        ]);

        self::event($userId, $histEmp, [
            'title' => 'Primer concepto',
            'summary' => 'La idea de Soup IT toma forma.',
            'start' => '08/2025', 'precision' => 'month',
            'category' => $cat($histEmp, 'Hitos'),
            'tags' => 'SoupIT',
        ]);
        self::event($userId, $histEmp, [
            'title' => 'Soup IT fundada',
            'summary' => 'Nace la marca.',
            'start' => '2025', 'precision' => 'year',
            'category' => $cat($histEmp, 'Hitos'),
            'tags' => 'SoupIT, fundacion',
            'milestone' => true,
        ]);
        self::event($userId, $histEmp, [
            'title' => 'Soup IT consigue su primer cliente',
            'summary' => 'Primer proyecto comercial realizado bajo la marca Soup IT.',
            'description' => 'El archivo marca el paso de taller a empresa.',
            'start' => '15/05/2026', 'precision' => 'day',
            'category' => $cat($histEmp, 'Negocios'),
            'tags' => 'SoupIT, Clientes, Historia',
            'milestone' => true,
        ]);
        self::event($userId, $clientes, [
            'title' => 'Primer cliente utilizando Puestito',
            'summary' => 'El producto entra en operación real.',
            'start' => '29/06/2026', 'precision' => 'day',
            'category' => $cat($clientes, 'Clientes'),
            'tags' => 'Puestito, Clientes',
        ]);
        self::event($userId, $productos, [
            'title' => 'Lanzamiento Puestito',
            'summary' => 'Sale a producción el primer producto propio.',
            'start' => '12/06/2026', 'precision' => 'day',
            'category' => $cat($productos, 'Lanzamientos'),
            'tags' => 'Puestito, Productos',
            'milestone' => true,
        ]);
        self::event($userId, $infra, [
            'title' => 'Home lab en marcha',
            'summary' => 'Servicios internos del taller digital.',
            'start' => '2026', 'precision' => 'year',
            'category' => $cat($infra, 'Home lab'),
            'tags' => 'infra, lab',
        ]);

        self::event($userId, $t35, [
            'title' => 'Diego llega a Buenos Aires',
            'summary' => 'El personaje establece territorio.',
            'start' => 'década de 1990', 'precision' => 'decade',
            'location' => 'Buenos Aires',
            'category' => $cat($t35, 'Diego'),
            'tags' => 'Diego, Argentina, Cabina',
        ]);
        self::event($userId, $t35, [
            'title' => 'Astrid entra en escena',
            'summary' => 'Cruce que reordena la cronología.',
            'start' => '1991', 'precision' => 'year',
            'category' => $cat($t35, 'Astrid'),
            'tags' => 'Astrid, 1991',
            'milestone' => true,
        ]);

        self::event($userId, $arg, [
            'title' => 'Revolución de Mayo',
            'summary' => 'Se abre el proceso independentista.',
            'start' => '25/05/1810', 'precision' => 'day',
            'location' => 'Buenos Aires',
            'category' => $cat($arg, 'Política'),
            'tags' => 'Argentina, Mayo',
            'milestone' => true,
        ]);
        self::event($userId, $arg, [
            'title' => 'Independencia',
            'summary' => 'Declaración en Tucumán.',
            'start' => '09/07/1816', 'precision' => 'day',
            'category' => $cat($arg, 'Política'),
            'tags' => 'Argentina',
            'milestone' => true,
        ]);
        self::event($userId, $arg, [
            'title' => 'Convertibilidad',
            'summary' => 'Un peso, un dólar.',
            'start' => 'década de 1990', 'precision' => 'decade',
            'category' => $cat($arg, 'Crisis'),
            'tags' => 'Economia, 1990',
        ]);
        self::event($userId, $arg, [
            'title' => 'Crisis de 2001',
            'summary' => 'Fin de un régimen y apertura de otro.',
            'start' => '2001', 'precision' => 'year',
            'category' => $cat($arg, 'Crisis'),
            'tags' => 'Argentina, crisis',
            'milestone' => true,
        ]);

        self::event($userId, $comp, [
            'title' => 'ENIAC',
            'summary' => 'Máquina electrónica de propósito general.',
            'start' => 'década de 1940', 'precision' => 'decade',
            'category' => $cat($comp, 'Hardware'),
            'tags' => 'ENIAC, hardware',
            'milestone' => true,
        ]);
        self::event($userId, $comp, [
            'title' => 'ARPANET',
            'summary' => 'La red que anticipa internet.',
            'start' => '1969', 'precision' => 'year',
            'category' => $cat($comp, 'Redes'),
            'tags' => 'redes',
        ]);
        self::event($userId, $comp, [
            'title' => 'IBM PC',
            'summary' => 'El escritorio se vuelve estándar.',
            'start' => '1981', 'precision' => 'year',
            'category' => $cat($comp, 'Hardware'),
            'tags' => 'IBM, PC',
        ]);
        self::event($userId, $comp, [
            'title' => 'World Wide Web',
            'summary' => 'Tim Berners-Lee publica la telaraña.',
            'start' => '1991', 'precision' => 'year',
            'category' => $cat($comp, 'Software'),
            'tags' => 'WWW, 1991',
            'milestone' => true,
        ]);
        self::event($userId, $comp, [
            'title' => 'Era móvil',
            'summary' => 'El siglo se vuelve bolsillo.',
            'start' => 'siglo XXI', 'precision' => 'century',
            'category' => $cat($comp, 'Software'),
            'tags' => 'movil',
        ]);

        self::event($userId, $ww2, [
            'title' => 'Invasión de Polonia',
            'summary' => 'Comienza el conflicto en Europa.',
            'start' => '01/09/1939', 'precision' => 'day',
            'category' => $cat($ww2, 'Europa'),
            'tags' => 'WW2',
            'milestone' => true,
        ]);
        self::event($userId, $ww2, [
            'title' => 'Fin de la guerra en Europa',
            'summary' => 'Capitulación alemana.',
            'start' => '08/05/1945', 'precision' => 'day',
            'category' => $cat($ww2, 'Europa'),
            'tags' => 'WW2',
            'milestone' => true,
        ]);

        self::event($userId, $sueltas, [
            'title' => 'Nota de archivo',
            'summary' => 'Esta línea no pertenece a ningún archivo.',
            'start' => '2026', 'precision' => 'year',
            'category' => $cat($sueltas, 'Notas'),
            'tags' => 'independiente',
        ]);

        return ['user_id' => $userId, 'seeded' => true];
    }

    private static function collection(int $userId, string $name, string $description, string $icon, string $color, int $order): int
    {
        return CollectionService::create($userId, [
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'sort_order' => $order,
        ]);
    }

    private static function timeline(int $userId, ?int $collectionId, string $name, string $description, string $icon, string $color, array $categories): int
    {
        $id = TimelineService::create($userId, [
            'collection_id' => $collectionId,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'default_view' => 'vertical',
            'date_format' => 'd/m/Y',
            'start_date' => null,
            'end_date' => null,
            'status' => 'active',
            'sort_order' => 0,
        ]);
        CategoryService::seedDefaults($id, $categories);
        return $id;
    }

    private static function categoryId(int $timelineId, string $name): ?int
    {
        foreach (CategoryService::forTimeline($timelineId) as $cat) {
            if (mb_strtolower((string) $cat['name']) === mb_strtolower($name)) {
                return (int) $cat['id'];
            }
        }
        return null;
    }

    private static function event(int $userId, int $timelineId, array $spec): void
    {
        $start = DatePrecision::parse((string) $spec['start'], $spec['precision'] ?? null);
        $end = null;
        $endPrec = null;
        if (!empty($spec['end'])) {
            $parsedEnd = DatePrecision::parse((string) $spec['end'], $spec['end_precision'] ?? $spec['precision'] ?? null);
            $end = $parsedEnd['date'];
            $endPrec = $parsedEnd['precision'];
        }
        EventService::create($userId, $timelineId, [
            'category_id' => $spec['category'] ?? null,
            'title' => $spec['title'],
            'summary' => $spec['summary'] ?? null,
            'description' => $spec['description'] ?? null,
            'start_date' => $start['date'],
            'end_date' => $end,
            'date_precision' => $start['precision'],
            'end_date_precision' => $endPrec,
            'is_ongoing' => !empty($spec['ongoing']),
            'is_milestone' => !empty($spec['milestone']),
            'location' => $spec['location'] ?? null,
            'tags' => $spec['tags'] ?? '',
        ]);
    }
}
