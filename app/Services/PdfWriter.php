<?php

declare(strict_types=1);

final class PdfWriter
{
    public const PAGE_W = 595.28;
    public const PAGE_H = 841.89;

    /** @var list<string> */
    private array $pages = [];
    private int $page = -1;
    private string $title = 'Timekeeper';
    private string $author = 'Timekeeper';

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->page = count($this->pages) - 1;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    public function usePage(int $index): void
    {
        if ($index < 0 || $index >= count($this->pages)) {
            throw new RuntimeException('Página PDF inválida.');
        }
        $this->page = $index;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function fill(array $rgb): void
    {
        $this->cmd($this->color($rgb) . ' rg');
    }

    public function stroke(array $rgb): void
    {
        $this->cmd($this->color($rgb) . ' RG');
    }

    public function lineWidth(float $w): void
    {
        $this->cmd(sprintf('%.2f w', $w));
    }

    public function dash(float $on = 4.0, float $off = 3.0): void
    {
        $this->cmd(sprintf('[%.2f %.2f] 0 d', $on, $off));
    }

    public function solid(): void
    {
        $this->cmd('[] 0 d');
    }

    public function dim(): void
    {
        $this->cmd('/GS1 gs');
    }

    public function undim(): void
    {
        $this->cmd('/GS0 gs');
    }

    public function fillRect(float $x, float $yTop, float $w, float $h): void
    {
        $this->cmd(sprintf('%.2f %.2f %.2f %.2f re f', $x, $this->pdfY($yTop, $h), $w, $h));
    }

    public function strokeRect(float $x, float $yTop, float $w, float $h): void
    {
        $this->cmd(sprintf('%.2f %.2f %.2f %.2f re S', $x, $this->pdfY($yTop, $h), $w, $h));
    }

    public function box(float $x, float $yTop, float $w, float $h): void
    {
        $this->cmd(sprintf('%.2f %.2f %.2f %.2f re B', $x, $this->pdfY($yTop, $h), $w, $h));
    }

    public function line(float $x1, float $y1Top, float $x2, float $y2Top): void
    {
        $this->cmd(sprintf(
            '%.2f %.2f m %.2f %.2f l S',
            $x1,
            self::PAGE_H - $y1Top,
            $x2,
            self::PAGE_H - $y2Top
        ));
    }

    public function diamond(float $cx, float $cyTop, float $size, bool $fill = true): void
    {
        $cy = self::PAGE_H - $cyTop;
        $this->cmd(sprintf('%.2f %.2f m', $cx, $cy + $size));
        $this->cmd(sprintf('%.2f %.2f l', $cx + $size, $cy));
        $this->cmd(sprintf('%.2f %.2f l', $cx, $cy - $size));
        $this->cmd(sprintf('%.2f %.2f l', $cx - $size, $cy));
        $this->cmd($fill ? 'h B' : 'h S');
    }

    public function hourglass(float $cx, float $yTop, float $s, array $rgb): void
    {
        $top = self::PAGE_H - $yTop;
        $this->fill($rgb);
        $this->cmd(sprintf('%.2f %.2f m %.2f %.2f l %.2f %.2f l h f', $cx - $s, $top, $cx + $s, $top, $cx, $top - $s * 1.15));
        $this->cmd(sprintf(
            '%.2f %.2f m %.2f %.2f l %.2f %.2f l h f',
            $cx,
            $top - $s * 1.35,
            $cx - $s,
            $top - $s * 2.5,
            $cx + $s,
            $top - $s * 2.5
        ));
    }

    public function text(float $x, float $yTop, string $text, float $size, string $style = 'regular'): void
    {
        $font = match ($style) {
            'bold' => 'F2',
            'oblique' => 'F3',
            default => 'F1',
        };
        $baseline = self::PAGE_H - ($yTop + $size * 0.78);
        $this->cmd(sprintf(
            'BT /%s %.2f Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET',
            $font,
            $size,
            $x,
            $baseline,
            $this->escape($this->win($text))
        ));
    }

    public function textCenter(float $cx, float $yTop, string $text, float $size, string $style = 'regular'): void
    {
        $this->text($cx - $this->textWidth($text, $size, $style === 'bold') / 2, $yTop, $text, $size, $style);
    }

    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        $bytes = $this->win($text);
        $sum = 0;
        $len = strlen($bytes);
        for ($i = 0; $i < $len; $i++) {
            $sum += $this->glyphWidth(ord($bytes[$i]));
        }
        return $sum * $size / 1000 * ($bold ? 1.05 : 1);
    }

    /** @return list<string> */
    public function wrap(string $text, float $maxWidth, float $size, bool $bold = false, int $maxLines = 8): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return [];
        }
        $words = preg_split('/\s+/u', $text) ?: [$text];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;
            if ($this->textWidth($try, $size, $bold) <= $maxWidth) {
                $current = $try;
                continue;
            }
            if ($current !== '') {
                $lines[] = $current;
            }
            if ($this->textWidth($word, $size, $bold) <= $maxWidth) {
                $current = $word;
                continue;
            }
            $current = '';
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $chunk = '';
            foreach ($chars as $ch) {
                $next = $chunk . $ch;
                if ($this->textWidth($next, $size, $bold) <= $maxWidth) {
                    $chunk = $next;
                } else {
                    if ($chunk !== '') {
                        $lines[] = $chunk;
                    }
                    $chunk = $ch;
                }
            }
            $current = $chunk;
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $lines[$maxLines - 1] = rtrim($lines[$maxLines - 1], ' .') . '...';
        }
        return $lines;
    }

    public function output(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $fonts = [
            1 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            2 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>',
        ];
        $nPages = count($this->pages);
        $contentStart = 4;
        $pageStart = $contentStart + $nPages;
        $pagesId = $pageStart + $nPages;
        $catalogId = $pagesId + 1;
        $infoId = $catalogId + 1;

        $kids = [];
        $objs = $fonts;
        for ($i = 0; $i < $nPages; $i++) {
            $contentId = $contentStart + $i;
            $pageId = $pageStart + $i;
            $stream = $this->pages[$i];
            $objs[$contentId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
            $objs[$pageId] = sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 1 0 R /F2 2 0 R /F3 3 0 R >> /ExtGState << /GS0 << /Type /ExtGState /ca 1 /CA 1 >> /GS1 << /Type /ExtGState /ca 0.07 /CA 0.07 >> >> >> >>',
                $pagesId,
                self::PAGE_W,
                self::PAGE_H,
                $contentId
            );
            $kids[] = $pageId . ' 0 R';
        }
        $objs[$pagesId] = '<< /Type /Pages /Count ' . $nPages . ' /Kids [' . implode(' ', $kids) . '] >>';
        $objs[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
        $objs[$infoId] = sprintf(
            '<< /Title (%s) /Author (%s) /Creator (Timekeeper) /Producer (Timekeeper) >>',
            $this->escape($this->win($this->title)),
            $this->escape($this->win($this->author))
        );

        ksort($objs);
        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objs as $id => $body) {
            $offsets[$id] = strlen($out);
            $out .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $maxId = $infoId;
        $xref = strlen($out);
        $out .= 'xref' . "\n0 " . ($maxId + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $out .= sprintf('%010d 00000 n ', $offsets[$id] ?? 0) . "\n";
        }
        $out .= 'trailer' . "\n<< /Size " . ($maxId + 1) . ' /Root ' . $catalogId . ' 0 R /Info ' . $infoId . " 0 R >>\n";
        $out .= 'startxref' . "\n" . $xref . "\n%%EOF";
        return $out;
    }

    private function cmd(string $s): void
    {
        if ($this->page < 0) {
            $this->addPage();
        }
        $this->pages[$this->page] .= $s . "\n";
    }

    private function pdfY(float $yTop, float $h): float
    {
        return self::PAGE_H - $yTop - $h;
    }

    private function color(array $rgb): string
    {
        return sprintf('%.3f %.3f %.3f', $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255);
    }

    private function win(string $utf8): string
    {
        $utf8 = strtr($utf8, [
            '→' => '-',
            '—' => '-',
            '–' => '-',
            '“' => '"',
            '”' => '"',
            '„' => '"',
            '‘' => "'",
            '’' => "'",
            '…' => '...',
            '✦' => '*',
            '★' => '*',
            '✗' => 'x',
        ]);
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $utf8);
        if ($out === false) {
            $out = @iconv('UTF-8', 'Windows-1252//IGNORE', $utf8);
        }
        return $out === false ? '?' : $out;
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
    }

    private function glyphWidth(int $b): int
    {
        static $ascii = [
            278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
            556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
            1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
            667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
            333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
            556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
        ];
        if ($b >= 32 && $b <= 126) {
            return $ascii[$b - 32];
        }
        return match ($b) {
            0xA1 => 333,
            0xAB, 0xBB => 556,
            0xB7 => 278,
            0xBF => 611,
            0xC0, 0xC1, 0xC2, 0xC4, 0xC7, 0xC8, 0xC9, 0xCA, 0xCB => 667,
            0xCC, 0xCD, 0xCE, 0xCF => 278,
            0xD1 => 722,
            0xD2, 0xD3, 0xD4, 0xD6, 0xD8 => 778,
            0xD9, 0xDA, 0xDB, 0xDC => 722,
            0xDD => 667,
            0xDF => 611,
            0xE0, 0xE1, 0xE2, 0xE4, 0xE7, 0xE8, 0xE9, 0xEA, 0xEB => 556,
            0xEC, 0xED, 0xEE, 0xEF => 222,
            0xF1 => 556,
            0xF2, 0xF3, 0xF4, 0xF6, 0xF8 => 556,
            0xF9, 0xFA, 0xFB, 0xFC => 556,
            0xFD, 0xFF => 500,
            0x96, 0x97 => 556,
            0x91, 0x92 => 191,
            0x93, 0x94 => 333,
            0x85 => 1000,
            0xA0 => 278,
            0x80 => 556,
            default => 600,
        };
    }
}
