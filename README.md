# Timekeeper

Archivo personal de líneas de tiempo. PHP 8.4 + MySQL, MVC propio, estética Archivist.

## Instalar

1. Copiá `.env.example` a `.env` y ajustá MySQL si hace falta.
2. Abrí `install.php` en el navegador (mismo host con el que entres: LAN, localhost, etc.).
3. Entrá con `archivo@timekeeper.local` / `timekeeper` (seed) o registrá un archivo nuevo.

Las URLs salen del request: no hay IP ni hostname fijos.

## MVP

Login, archivos (collections), líneas, eventos con fechas incompletas, categorías, tags, vista vertical y horizontal, filtros, buscador, export JSON/CSV, ajustes, papelera y favoritos.
