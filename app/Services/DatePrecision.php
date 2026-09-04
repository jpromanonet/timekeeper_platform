<?php

declare(strict_types=1);

final class DatePrecision
{
    public const DAY = 'day';
    public const MONTH = 'month';
    public const YEAR = 'year';
    public const DECADE = 'decade';
    public const CENTURY = 'century';
    public const UNKNOWN = 'unknown';

    /** @var list<string> */
    public const ALL = [self::DAY, self::MONTH, self::YEAR, self::DECADE, self::CENTURY, self::UNKNOWN];

    public static function labels(): array
    {
        return [
            self::DAY => 'Día',
            self::MONTH => 'Mes',
            self::YEAR => 'Año',
            self::DECADE => 'Década',
            self::CENTURY => 'Siglo',
            self::UNKNOWN => 'Desconocida',
        ];
    }

    /**
     * @return array{date:?string,precision:string}
     */
    public static function parse(string $input, ?string $forcedPrecision = null, string $dateFormat = 'd/m/Y'): array
    {
        $raw = trim($input);
        $forced = self::normalizePrecision($forcedPrecision);

        if ($raw === '' || $raw === '?' || preg_match('/^(sin fecha|desconocid[ao]|unknown)$/iu', $raw)) {
            return ['date' => null, 'precision' => $forced ?? self::UNKNOWN];
        }

        if (preg_match('/siglo\s*(?:x{0,3}(?:ix|iv|v?i{0,3})|\d{1,2})/iu', $raw) || preg_match('/^s\.?\s*(x{0,3}(?:ix|iv|v?i{0,3})|\d{1,2})$/iu', $raw)) {
            $century = self::parseCentury($raw);
            if ($century !== null) {
                $year = ($century - 1) * 100 + 1;
                return ['date' => sprintf('%04d-01-01', $year), 'precision' => $forced ?? self::CENTURY];
            }
        }

        if (preg_match('/d[eé]cada(?:\s+de)?\s*(\d{3,4})/iu', $raw, $m) || preg_match('/^(\d{4})s$/iu', $raw, $m) || preg_match('/^(?:los|a[nñ]os)\s*(\d{2,4})/iu', $raw, $m)) {
            $decade = self::decadeStart((int) $m[1]);
            return ['date' => sprintf('%04d-01-01', $decade), 'precision' => $forced ?? self::DECADE];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return ['date' => $raw, 'precision' => $forced ?? self::DAY];
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            return ['date' => sprintf('%04d-%02d-01', (int) $m[1], (int) $m[2]), 'precision' => $forced ?? self::MONTH];
        }

        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $raw, $m)) {
            $dayFirst = !str_starts_with(strtolower($dateFormat), 'm');
            $a = (int) $m[1];
            $b = (int) $m[2];
            $year = (int) $m[3];
            $day = $dayFirst ? $a : $b;
            $month = $dayFirst ? $b : $a;
            if ($month > 12 && $day <= 12) {
                [$day, $month] = [$month, $day];
            }
            if (checkdate($month, $day, $year)) {
                return ['date' => sprintf('%04d-%02d-%02d', $year, $month, $day), 'precision' => $forced ?? self::DAY];
            }
        }

        if (preg_match('/^(\d{1,2})[\/.\-](\d{4})$/', $raw, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12) {
            return ['date' => sprintf('%04d-%02d-01', (int) $m[2], (int) $m[1]), 'precision' => $forced ?? self::MONTH];
        }

        if (preg_match('/^(\d{4})$/', $raw, $m)) {
            $year = (int) $m[1];
            if ($forced === self::DECADE) {
                return ['date' => sprintf('%04d-01-01', self::decadeStart($year)), 'precision' => self::DECADE];
            }
            if ($forced === self::CENTURY) {
                $century = $year <= 21 ? $year : (int) ceil($year / 100);
                return ['date' => sprintf('%04d-01-01', ($century - 1) * 100 + 1), 'precision' => self::CENTURY];
            }
            return ['date' => sprintf('%04d-01-01', $year), 'precision' => $forced ?? self::YEAR];
        }

        $ts = strtotime($raw);
        if ($ts !== false) {
            return ['date' => date('Y-m-d', $ts), 'precision' => $forced ?? self::DAY];
        }

        return ['date' => null, 'precision' => $forced ?? self::UNKNOWN];
    }

    public static function format(?string $date, string $precision, string $dateFormat = 'd/m/Y'): string
    {
        $precision = self::normalizePrecision($precision) ?? self::DAY;
        if ($precision === self::UNKNOWN || $date === null || $date === '') {
            return 'Sin fecha';
        }

        try {
            $dt = new DateTimeImmutable($date);
        } catch (Throwable) {
            return $date;
        }

        return match ($precision) {
            self::DAY => $dt->format($dateFormat),
            self::MONTH => $dt->format(str_starts_with($dateFormat, 'Y') ? 'Y-m' : 'm/Y'),
            self::YEAR => $dt->format('Y'),
            self::DECADE => 'Década de ' . (intdiv((int) $dt->format('Y'), 10) * 10),
            self::CENTURY => 'Siglo ' . self::intToRoman((int) ceil(((int) $dt->format('Y')) / 100)),
            default => $dt->format($dateFormat),
        };
    }

    public static function formatSpan(
        ?string $start,
        string $startPrecision,
        ?string $end,
        ?string $endPrecision,
        bool $ongoing,
        string $dateFormat = 'd/m/Y'
    ): string {
        $left = self::format($start, $startPrecision, $dateFormat);
        if ($ongoing) {
            return $left . ' → Actualidad';
        }
        if ($end === null || $end === '') {
            return $left;
        }
        $right = self::format($end, $endPrecision ?: $startPrecision, $dateFormat);
        if ($right === $left) {
            return $left;
        }
        return $left . ' → ' . $right;
    }

    public static function sortKey(?string $date): ?string
    {
        return $date ?: null;
    }

    public static function normalizePrecision(?string $precision): ?string
    {
        if ($precision === null || $precision === '') {
            return null;
        }
        $precision = strtolower(trim($precision));
        return in_array($precision, self::ALL, true) ? $precision : null;
    }

    public static function parseTime(?string $value): ?string
    {
        $value = null_if_blank($value);
        if ($value === null) {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $i >= 0 && $i <= 59) {
                return sprintf('%02d:%02d:00', $h, $i);
            }
        }
        return null;
    }

    public static function formatTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }
        return strlen($time) >= 5 ? substr($time, 0, 5) : $time;
    }

    public static function inputValue(?string $date, string $precision): string
    {
        if ($date === null || $date === '' || $precision === self::UNKNOWN) {
            return '';
        }
        return self::format($date, $precision, 'd/m/Y');
    }

    private static function decadeStart(int $n): int
    {
        if ($n < 100) {
            $n = 1900 + $n;
        }
        return intdiv($n, 10) * 10;
    }

    private static function parseCentury(string $raw): ?int
    {
        if (preg_match('/(\d{1,2})/', $raw, $m) && !preg_match('/[ivxlcdm]/i', $raw)) {
            $n = (int) $m[1];
            return $n >= 1 && $n <= 30 ? $n : null;
        }
        if (preg_match('/(x{0,3}(?:ix|iv|v?i{0,3}))/i', $raw, $m)) {
            $n = self::romanToInt(strtoupper($m[1]));
            return $n >= 1 && $n <= 30 ? $n : null;
        }
        return null;
    }

    private static function romanToInt(string $roman): int
    {
        $map = ['M' => 1000, 'D' => 500, 'C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1];
        $total = 0;
        $prev = 0;
        for ($i = strlen($roman) - 1; $i >= 0; $i--) {
            $val = $map[$roman[$i]] ?? 0;
            $total += $val < $prev ? -$val : $val;
            $prev = $val;
        }
        return $total;
    }

    private static function intToRoman(int $n): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $out = '';
        foreach ($map as $value => $glyph) {
            while ($n >= $value) {
                $out .= $glyph;
                $n -= $value;
            }
        }
        return $out;
    }
}
