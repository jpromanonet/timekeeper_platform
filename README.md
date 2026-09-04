# Timekeeper

Colección personal de líneas de tiempo.

Timekeeper guarda lo que no entra en un calendario: una vida, una empresa, un libro, una guerra, una familia. Fechas exactas, décadas, siglos o “no se sabe”. Todo queda en un archivo propio, en tu servidor, sin cuenta en la nube.

## Cómo se organiza

- **Colección.** Tu espacio. Ahí entrás.
- **Archivos.** Carpetas temáticas: *Mi vida*, *Soup IT*, *Historia*.
- **Líneas.** Una cronología dentro de un archivo, o suelta.
- **Eventos.** Lo que pasó. Título, resumen, gente, lugar, categoría, tags.

Nada se borra del todo de entrada: las líneas y los eventos van a la **papelera**.

## Qué podés hacer

**Armar el relato.** Eventos con día, mes, año, década, siglo o fecha desconocida. Hitos, eventos en curso, favoritos con estrella.

**Mirarlo.** Vista vertical (eje al centro, fichas a los costados) o horizontal (zoom, minimapa, ir a un año). Filtros por categoría, tag y rango.

**Encontrarlo.** Buscador, paleta de comandos (`Ctrl+K`) y página de métricas: volumen, precisión, peso de cada línea.

**Sacarlo.** En cada línea, menú **Exportar**: JSON, CSV o un PDF de la cronología.

**Cuidarlo.** Selección con tilde para borrar varias líneas o archivos. Ajustes de nombre, correo, tema, densidad y formato de fecha.

## Cómo se ve

Estética *Archivist*: fondo oscuro, pasteles, tipografía pixel y monoespaciada. Temas (Dungeon, Forest, Royal, Arcane, Monastery) y sonidos de interfaz opcionales.

## Empezar

Hace falta PHP 8+ y MySQL o MariaDB, en local o en el homelab.

1. Copiá `.env.example` a `.env` y completá la base.
2. Abrí `install.php` en el navegador (el mismo host con el que vayas a entrar).
3. Entrá a la colección de ejemplo:

   `archivo@timekeeper.local` / `timekeeper`

   o registrá la tuya.

Las URLs salen del request: no hay IP ni hostname fijos. Sirve `localhost` o `192.168.x.x`.
