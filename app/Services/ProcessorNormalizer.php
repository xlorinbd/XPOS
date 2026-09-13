<?php

namespace App\Services;

class ProcessorNormalizer
{
    /**
     * Normalize messy processor string locally using pattern matching
     *
     * @param string|null $raw
     * @return array ['normalized' => string, 'is_recognized' => bool, 'original' => string]
     */
    public static function normalize(?string $raw): array
    {
        if (empty($raw) || trim($raw) === '') {
            return [
                'normalized' => '',
                'is_recognized' => false,
                'original' => ''
            ];
        }

        $clean = trim($raw);
        $normalized = $clean;
        $is_recognized = false;

        // 1. Apple Silicon Normalization
        if (preg_match('/^m([1-4])\s*(ultra|max|pro)?/i', $clean, $m)) {
            $gen = 'M' . $m[1];
            $tier = isset($m[2]) && !empty($m[2]) ? ' ' . ucfirst(strtolower($m[2])) : '';
            $normalized = "Apple {$gen}{$tier} Chip";
            $is_recognized = true;
        }
        // 2. Intel Core Ultra (e.g. Ultra 7 155H, Ultra 5 125H)
        elseif (preg_match('/(?:intel\s*)?(?:core\s*)?ultra\s*([579])\s*[-]?\s*(\d{3}[a-z]*)/i', $clean, $m)) {
            $series = $m[1];
            $model = strtoupper($m[2]);
            $normalized = "Intel Core Ultra {$series} {$model}";
            $is_recognized = true;
        }
        // 3. Intel Core i3 / i5 / i7 / i9 (e.g. i7 13700H, core i5-1235U, i5 12th gen 1240p)
        elseif (preg_match('/(?:intel\s*)?(?:core\s*)?i([3579])\s*(?:[-]?\s*(?:\d+th\s*gen)?\s*)?[-]?\s*(\d{4,5}[a-z]*)/i', $clean, $m)) {
            $series = $m[1];
            $model = strtoupper($m[2]);
            $normalized = "Intel Core i{$series}-{$model}";
            $is_recognized = true;
        }
        // 4. AMD Ryzen 3 / 5 / 7 / 9 (e.g. r7 6800h, ryzen 5 5600u, AMD Ryzen 7-7730U)
        elseif (preg_match('/(?:amd\s*)?(?:r|ryzen)\s*([3579])\s*(?:ai)?\s*[-]?\s*(\d{4}[a-z]*)/i', $clean, $m)) {
            $series = $m[1];
            $model = strtoupper($m[2]);
            $normalized = "AMD Ryzen {$series} {$model}";
            $is_recognized = true;
        }
        // 5. AMD Athlon
        elseif (preg_match('/(?:amd\s*)?athlon\s*(?:silver|gold)?\s*[-]?\s*([0-9a-z]+)/i', $clean, $m)) {
            $model = strtoupper($m[1]);
            $normalized = "AMD Athlon {$model}";
            $is_recognized = true;
        }
        // 6. Intel Celeron / Pentium
        elseif (preg_match('/(?:intel\s*)?(celeron|pentium)\s*[-]?\s*([0-9a-z]+)/i', $clean, $m)) {
            $family = ucfirst(strtolower($m[1]));
            $model = strtoupper($m[2]);
            $normalized = "Intel {$family} {$model}";
            $is_recognized = true;
        }

        // Clean extra double spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return [
            'normalized' => $normalized,
            'is_recognized' => $is_recognized,
            'original' => $clean
        ];
    }

    /**
     * Clean and parse price strings from Excel (removes ৳, Tk, $, commas, whitespace)
     */
    public static function cleanPrice($raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        // Remove currency symbols, commas, spaces, currency abbreviations (Tk, BDT, etc.)
        $cleaned = preg_replace('/[^\d.]/', '', (string) $raw);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
