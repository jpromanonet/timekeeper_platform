<?php

declare(strict_types=1);

final class TimelinePdfService
{
    private const BG = [24, 24, 33];
    private const PANEL = [35, 35, 46];
    private const ELEVATED = [48, 48, 61];
    private const TEXT = [225, 222, 213];
    private const MUTED = [170, 167, 160];
    private const SAND = [201, 181, 138];
    private const BORDER = [74, 73, 86];
    private const BLUE = [145, 167, 196];
    private const LAVENDER = [169, 155, 194];
    private const SHADOW = [17, 17, 24];
    private const AXIS = 297.64;
    private const CARD_W = 226.0;
    private const GAP = 18.0;
    private const FOOTER = 36.0;

    private PdfWriter $pdf;
    private float $y = 28.0;
    private int $side = 0;
    /** @var array<string,mixed> */
    private array $timeline;
    private string $dateFormat;
    /** @var list<int> */
    private array $accent;

    /**
     * @param array<string,mixed> $timeline
     * @param list<array<string,mixed>> $events
     * @param list<array<string,mixed>> $categories
     */
    public static function render(
        array $timeline,
        array $events,
        array $categories,
        string $dateFormat,
        string $exportedBy = ''
    ): string {
        $self = new self();
        return $self->build($timeline, $events, $categories, $dateFormat, $exportedBy);
    }

    /**
     * @param array<string,mixed> $timeline
     * @param list<array<string,mixed>> $events
     * @param list<array<string,mixed>> $categories
     */
    private function build(
        array $timeline,
        array $events,
        array $categories,
        string $dateFormat,
        string $exportedBy
    ): string {
        $this->timeline = $timeline;
        $this->dateFormat = $dateFormat;
        $this->accent = self::hex((string) ($timeline['color'] ?? '#C9B58A'));
        $this->pdf = new PdfWriter();
        $this->pdf->setTitle((string) $timeline['name']);
        $this->pdf->setAuthor($exportedBy !== '' ? $exportedBy : 'Timekeeper');
        $this->pdf->addPage();
        $this->paintPage(false);
        $this->drawCover($events, $exportedBy);
        $this->drawSparkline($events);
        $this->drawLegend($categories);
        $this->drawSpineFrom($this->y);
        $this->drawEvents($events);
        $this->writeFooters();
        return $this->pdf->output();
    }

    private function paintPage(bool $continuation): void
    {
        $pdf = $this->pdf;
        $pdf->fill(self::BG);
        $pdf->fillRect(0, 0, PdfWriter::PAGE_W, PdfWriter::PAGE_H);
        $pdf->dim();
        $pdf->fill([10, 10, 16]);
        for ($scan = 0; $scan < PdfWriter::PAGE_H; $scan += 4) {
            $pdf->fillRect(0, $scan, PdfWriter::PAGE_W, 0.7);
        }
        $pdf->undim();
        $pdf->fill($this->accent);
        $pdf->fillRect(0, 0, 5, PdfWriter::PAGE_H);
        $pdf->fill(self::SAND);
        $pdf->fillRect(5, 0, PdfWriter::PAGE_W - 5, 2.2);
        $pdf->fill(self::BORDER);
        $pdf->fillRect(5, PdfWriter::PAGE_H - 22, PdfWriter::PAGE_W - 5, 22);

        if ($continuation) {
            $pdf->fill(self::SAND);
            $pdf->text(28, 18, 'TIMEKEEPER', 8, 'bold');
            $pdf->fill(self::TEXT);
            $pdf->text(108, 18, (string) $this->timeline['name'], 9, 'bold');
            $pdf->fill(self::MUTED);
            $pdf->text(28, 32, 'continúa', 8, 'oblique');
            $this->drawSpineFrom(52);
        }
    }

    /**
     * @param list<array<string,mixed>> $events
     */
    private function drawCover(array $events, string $exportedBy): void
    {
        $pdf = $this->pdf;
        $pdf->hourglass(42, 18, 7, self::SAND);
        $pdf->fill(self::SAND);
        $pdf->text(58, 16, 'TIMEKEEPER', 13, 'bold');
        $pdf->fill(self::MUTED);
        $pdf->text(58, 32, 'ARCHIVO PERSONAL  ·  LÍNEA DE TIEMPO', 8, 'regular');

        $this->y = 54;
        $pdf->fill($this->accent);
        $pdf->fillRect(28, $this->y, 72, 3);
        $this->y += 12;

        $titleLines = $pdf->wrap((string) $this->timeline['name'], 520, 22, true, 3);
        $pdf->fill(self::TEXT);
        foreach ($titleLines as $line) {
            $pdf->text(28, $this->y, $line, 22, 'bold');
            $this->y += 26;
        }

        $desc = trim((string) ($this->timeline['description'] ?? ''));
        if ($desc !== '') {
            $pdf->fill(self::MUTED);
            foreach ($pdf->wrap($desc, 520, 10, false, 4) as $line) {
                $pdf->text(28, $this->y, $line, 10, 'oblique');
                $this->y += 13;
            }
            $this->y += 4;
        }

        $archive = trim((string) ($this->timeline['collection_name'] ?? ''));
        $serie = trim((string) ($this->timeline['series_name'] ?? ''));
        $range = $this->dateRangeLabel($events);
        $chips = [
            $archive !== '' ? $archive : 'Sin archivo',
        ];
        if ($serie !== '') {
            $chips[] = $serie;
        }
        $chips[] = count($events) . (count($events) === 1 ? ' evento' : ' eventos');
        $chips[] = status_label((string) ($this->timeline['status'] ?? 'active'));
        if ($range !== '') {
            $chips[] = $range;
        }
        if ($exportedBy !== '') {
            $chips[] = $exportedBy;
        }
        $this->drawChips($chips);
        $this->y += 8;
    }

    /** @param list<string> $chips */
    private function drawChips(array $chips): void
    {
        $x = 28.0;
        $pdf = $this->pdf;
        foreach ($chips as $chip) {
            $w = $pdf->textWidth($chip, 8, false) + 14;
            if ($x + $w > 560) {
                $this->y += 20;
                $x = 28;
            }
            $pdf->fill(self::ELEVATED);
            $pdf->stroke(self::BORDER);
            $pdf->lineWidth(1);
            $pdf->box($x, $this->y, $w, 16);
            $pdf->fill(self::SAND);
            $pdf->text($x + 7, $this->y + 3.5, $chip, 8, 'regular');
            $x += $w + 6;
        }
        $this->y += 22;
    }

    /** @param list<array<string,mixed>> $events */
    private function drawSparkline(array $events): void
    {
        $dated = [];
        foreach ($events as $ev) {
            if (!empty($ev['start_date'])) {
                $ts = strtotime((string) $ev['start_date']);
                if ($ts !== false) {
                    $dated[] = ['ts' => $ts, 'mile' => (int) ($ev['is_milestone'] ?? 0) === 1, 'color' => $this->eventColor($ev)];
                }
            }
        }
        if ($dated === []) {
            return;
        }

        $pdf = $this->pdf;
        $x = 36.0;
        $w = 523.0;
        $y = $this->y + 10;
        $pdf->stroke(self::BORDER);
        $pdf->lineWidth(1.6);
        $pdf->line($x, $y, $x + $w, $y);

        $min = $dated[0]['ts'];
        $max = $dated[array_key_last($dated)]['ts'];
        if ($max <= $min) {
            $max = $min + 86400;
        }
        foreach ($dated as $dot) {
            $t = ($dot['ts'] - $min) / ($max - $min);
            $cx = $x + $t * $w;
            $size = $dot['mile'] ? 5.5 : 3.6;
            $pdf->fill($dot['color']);
            $pdf->stroke(self::SAND);
            $pdf->lineWidth(1);
            $pdf->diamond($cx, $y, $size, true);
        }

        $pdf->fill(self::MUTED);
        $first = DatePrecision::format(date('Y-m-d', $min), 'year', $this->dateFormat);
        $last = DatePrecision::format(date('Y-m-d', $max), 'year', $this->dateFormat);
        $pdf->text($x, $y + 10, $first, 8, 'regular');
        $lastW = $pdf->textWidth($last, 8, false);
        $pdf->text($x + $w - $lastW, $y + 10, $last, 8, 'regular');
        $this->y = $y + 28;
    }

    /** @param list<array<string,mixed>> $categories */
    private function drawLegend(array $categories): void
    {
        if ($categories === []) {
            $this->y += 6;
            return;
        }
        $pdf = $this->pdf;
        $x = 28.0;
        $pdf->fill(self::MUTED);
            $pdf->text($x, $this->y, 'Categorías', 8, 'regular');
        $x += 64;
        foreach ($categories as $cat) {
            $name = (string) $cat['name'];
            $w = $pdf->textWidth($name, 8, false) + 22;
            if ($x + $w > 560) {
                $this->y += 16;
                $x = 92;
            }
            $color = self::hex((string) ($cat['color'] ?? '#91A7C4'));
            $pdf->fill($color);
            $pdf->fillRect($x, $this->y + 2, 8, 8);
            $pdf->fill(self::TEXT);
            $pdf->text($x + 12, $this->y, $name, 8, 'regular');
            $x += $w;
        }
        $this->y += 22;
    }

    /** @param list<array<string,mixed>> $events */
    private function drawEvents(array $events): void
    {
        if ($events === []) {
            $this->ensureSpace(48);
            $this->pdf->fill(self::MUTED);
            $this->pdf->textCenter(self::AXIS, $this->y + 12, 'Todavia no hay eventos en esta linea.', 10, 'oblique');
            return;
        }

        $groups = $this->group($events);
        foreach ($groups as $group) {
            $this->ensureSpace(36);
            $this->drawYear((string) $group['label']);
            foreach ($group['items'] as $ev) {
                $this->drawCard($ev);
            }
        }
    }

    private function drawYear(string $label): void
    {
        $pdf = $this->pdf;
        $w = max(72.0, $pdf->textWidth($label, 10, true) + 20);
        $x = self::AXIS - $w / 2;
        $pdf->fill(self::ELEVATED);
        $pdf->stroke(self::SAND);
        $pdf->lineWidth(1.4);
        $pdf->box($x, $this->y, $w, 18);
        $pdf->fill(self::SAND);
        $pdf->textCenter(self::AXIS, $this->y + 3.5, $label, 10, 'bold');
        $this->y += 30;
    }

    /** @param array<string,mixed> $ev */
    private function drawCard(array $ev): void
    {
        $pdf = $this->pdf;
        $color = $this->eventColor($ev);
        $isMile = (int) ($ev['is_milestone'] ?? 0) === 1;
        $inner = self::CARD_W - 20;
        $label = $this->eventLabel($ev);
        $title = (string) $ev['title'];
        $summary = trim((string) ($ev['summary'] ?? ''));
        if ($summary === '') {
            $summary = mb_substr(trim((string) ($ev['description'] ?? '')), 0, 220);
        }
        $titleLines = $pdf->wrap($title, $inner, 11, true, 3);
        $sumLines = $summary !== '' ? $pdf->wrap($summary, $inner, 9, false, 3) : [];
        $meta = [];
        if (!empty($ev['category_name'])) {
            $meta[] = (string) $ev['category_name'];
        }
        foreach (array_slice($ev['tag_list'] ?? [], 0, 4) as $tag) {
            $meta[] = '#' . $tag;
        }
        $location = trim((string) ($ev['location'] ?? ''));
        $h = 8 + 10 + 12 + 4 + (count($titleLines) * 13);
        if ($sumLines) {
            $h += 4 + count($sumLines) * 11;
        }
        if ($meta) {
            $h += 13;
        }
        if ($location !== '') {
            $h += 12;
        }
        if ($isMile) {
            $h += 8;
        }
        $h += 10;

        $this->ensureSpace($h + 14);
        $this->side = 1 - $this->side;
        $left = $this->side === 1;
        $x = $left ? self::AXIS - self::GAP - self::CARD_W : self::AXIS + self::GAP;
        $y = $this->y;

        $pdf->fill(self::SHADOW);
        $pdf->fillRect($x + 3, $y + 3, self::CARD_W, $h);
        $pdf->fill(self::PANEL);
        $pdf->stroke($isMile ? self::SAND : self::BORDER);
        $pdf->lineWidth($isMile ? 1.8 : 1.2);
        $pdf->box($x, $y, self::CARD_W, $h);
        $pdf->fill($color);
        $pdf->fillRect($x, $y, self::CARD_W, 4.5);

        $ty = $y + 10;
        if ($isMile) {
            $pdf->fill(self::SAND);
            $pdf->text($x + 10, $ty, '* HITO', 7, 'bold');
            $ty += 11;
        }
        $pdf->fill(self::SAND);
        $pdf->text($x + 10, $ty, $label, 8, 'bold');
        $ty += 14;
        $pdf->fill(self::TEXT);
        foreach ($titleLines as $line) {
            $pdf->text($x + 10, $ty, $line, 11, 'bold');
            $ty += 13;
        }
        if ($sumLines) {
            $ty += 2;
            $pdf->fill(self::MUTED);
            foreach ($sumLines as $line) {
                $pdf->text($x + 10, $ty, $line, 9, 'regular');
                $ty += 11;
            }
        }
        if ($meta) {
            $ty += 2;
            $pdf->fill(self::BLUE);
            $pdf->text($x + 10, $ty, implode('  ', $meta), 8, 'regular');
            $ty += 12;
        }
        if ($location !== '') {
            $pdf->fill(self::LAVENDER);
            $pdf->text($x + 10, $ty, $location, 8, 'oblique');
        }

        $nodeY = $y + 16;
        $pdf->fill($isMile ? self::SAND : self::BG);
        $pdf->stroke($color);
        $pdf->lineWidth(1.8);
        $pdf->diamond(self::AXIS, $nodeY, $isMile ? 5.5 : 4.2, true);
        $this->y = $y + $h + 16;
    }

    private function ensureSpace(float $need): void
    {
        if ($this->y + $need <= PdfWriter::PAGE_H - self::FOOTER - 8) {
            return;
        }
        $this->pdf->addPage();
        $this->paintPage(true);
        $this->y = 58;
        $this->side = 0;
    }

    private function drawSpineFrom(float $top): void
    {
        $this->pdf->stroke(self::BORDER);
        $this->pdf->lineWidth(1.4);
        $this->pdf->dash(5, 4);
        $this->pdf->line(self::AXIS, $top, self::AXIS, PdfWriter::PAGE_H - self::FOOTER);
        $this->pdf->solid();
        $this->pdf->fill(self::SAND);
        $this->pdf->stroke(self::SAND);
        $this->pdf->lineWidth(1.2);
        $this->pdf->diamond(self::AXIS, $top, 4.0, true);
    }

    private function writeFooters(): void
    {
        $n = $this->pdf->pageCount();
        $when = (new DateTimeImmutable('now'))->format('d/m/Y H:i');
        $left = 'TIMEKEEPER  ·  ' . $when;
        for ($i = 0; $i < $n; $i++) {
            $this->pdf->usePage($i);
            $this->pdf->fill(self::MUTED);
            $this->pdf->text(16, PdfWriter::PAGE_H - 16, $left, 7, 'regular');
            $label = ($i + 1) . ' / ' . $n;
            $w = $this->pdf->textWidth($label, 7, false);
            $this->pdf->text(PdfWriter::PAGE_W - 16 - $w, PdfWriter::PAGE_H - 16, $label, 7, 'regular');
        }
    }

    /** @param array<string,mixed> $ev */
    private function eventLabel(array $ev): string
    {
        $label = DatePrecision::formatSpan(
            $ev['start_date'] ?? null,
            (string) ($ev['date_precision'] ?? 'day'),
            $ev['end_date'] ?? null,
            $ev['end_date_precision'] ?? $ev['date_precision'] ?? 'day',
            (bool) ($ev['is_ongoing'] ?? false),
            $this->dateFormat
        );
        $label = str_replace(' → ', ' - ', $label);
        if (!empty($ev['start_time'])) {
            $label .= ' · ' . DatePrecision::formatTime((string) $ev['start_time']);
        }
        return $label;
    }

    /** @param array<string,mixed> $ev @return list<int> */
    private function eventColor(array $ev): array
    {
        $hex = (string) ($ev['color'] ?: ($ev['category_color'] ?? $this->timeline['color'] ?? '#C9B58A'));
        return self::hex($hex);
    }

    /** @param list<array<string,mixed>> $events */
    private function dateRangeLabel(array $events): string
    {
        $dates = [];
        foreach ($events as $ev) {
            if (!empty($ev['start_date'])) {
                $dates[] = (string) $ev['start_date'];
            }
        }
        if ($dates === []) {
            return '';
        }
        sort($dates);
        $a = substr($dates[0], 0, 4);
        $b = substr($dates[array_key_last($dates)], 0, 4);
        return $a === $b ? $a : $a . ' - ' . $b;
    }

    /**
     * @param list<array<string,mixed>> $events
     * @return list<array{label:string,items:list<array<string,mixed>>}>
     */
    private function group(array $events): array
    {
        $groups = [];
        foreach ($events as $ev) {
            $key = 'unknown';
            $label = 'Sin fecha';
            if (!empty($ev['start_date'])) {
                $year = (int) substr((string) $ev['start_date'], 0, 4);
                $prec = (string) ($ev['date_precision'] ?? 'year');
                if ($prec === 'century') {
                    $key = 'c-' . (int) ceil($year / 100);
                    $label = DatePrecision::format($ev['start_date'], 'century', $this->dateFormat);
                } elseif ($prec === 'decade') {
                    $key = 'd-' . (intdiv($year, 10) * 10);
                    $label = DatePrecision::format($ev['start_date'], 'decade', $this->dateFormat);
                } else {
                    $key = 'y-' . $year;
                    $label = (string) $year;
                }
            }
            if (!isset($groups[$key])) {
                $groups[$key] = ['label' => $label, 'items' => []];
            }
            $groups[$key]['items'][] = $ev;
        }
        return array_values($groups);
    }

    /** @return list<int> */
    private static function hex(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return self::SAND;
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
