<?php

namespace App\Services;

use App\Models\Lookup;

class ProcessorNormalizer
{
    /**
     * Normalize a typed processor name.
     * 1. If it matches an entry of the admin-managed Processor List, that entry is returned exactly as listed.
     * 2. Otherwise well-known families are formatted with trademark marks (Intel® Core™ i7-1165G7, AMD Ryzen™ 5 7535HS).
     * 3. Anything else is kept exactly as typed.
     *
     * @return array ['normalized' => string, 'is_recognized' => bool, 'original' => string, 'source' => 'list'|'pattern'|'none']
     */
    public static function normalize(?string $raw): array
    {
        if (empty($raw) || trim($raw) === '') {
            return ['normalized' => '', 'is_recognized' => false, 'original' => '', 'source' => 'none'];
        }

        $clean = trim($raw);

        $listed = self::fromList($clean);
        if ($listed !== null) {
            return ['normalized' => $listed, 'is_recognized' => true, 'original' => $clean, 'source' => 'list'];
        }

        $formatted = self::format($clean);
        if ($formatted !== null) {
            // A pattern-formatted name may still match a list entry that was stored in another spelling.
            $listed = self::fromList($formatted);
            return [
                'normalized' => $listed ?? $formatted,
                'is_recognized' => true,
                'original' => $clean,
                'source' => $listed ? 'list' : 'pattern',
            ];
        }

        return ['normalized' => $clean, 'is_recognized' => false, 'original' => $clean, 'source' => 'none'];
    }

    private static function fromList(string $text): ?string
    {
        try {
            $key = Lookup::processorKey($text);
            if ($key === '') {
                return null;
            }
            return Lookup::ofType('processor')->active()->where('match_key', $key)->value('name');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function format(string $clean): ?string
    {
        $out = null;

        // Apple Silicon
        if (preg_match('/^m([1-4])\s*(ultra|max|pro)?/i', $clean, $m)) {
            $tier = !empty($m[2]) ? ' ' . ucfirst(strtolower($m[2])) : '';
            $out = "Apple M{$m[1]}{$tier} Chip";
        }
        // Intel Core Ultra (e.g. Ultra 7 155H)
        elseif (preg_match('/(?:intel\W*)?(?:core\W*)?ultra\s*([579])\s*[-]?\s*(\d{3}[a-z]*)/i', $clean, $m)) {
            $out = 'Intel® Core™ Ultra ' . $m[1] . ' ' . strtoupper($m[2]);
        }
        // Intel Core i3 / i5 / i7 / i9
        elseif (preg_match('/(?:intel\W*)?(?:core\W*)?i([3579])\s*(?:[-]?\s*(?:\d+th\s*gen)?\s*)?[-]?\s*(\d{4,5}[a-z]*\d?[a-z]*)/i', $clean, $m)) {
            $out = 'Intel® Core™ i' . $m[1] . '-' . strtoupper($m[2]);
        }
        // AMD Ryzen 3 / 5 / 7 / 9
        elseif (preg_match('/(?:amd\W*)?(?:r|ryzen)\W*\s*([3579])\s*(?:ai)?\s*[-]?\s*(\d{4}[a-z]*\d?[a-z]*)/i', $clean, $m)) {
            $out = 'AMD Ryzen™ ' . $m[1] . ' ' . strtoupper($m[2]);
        }
        // AMD Athlon
        elseif (preg_match('/(?:amd\W*)?athlon\W*\s*(?:silver|gold)?\s*[-]?\s*([0-9a-z]+)/i', $clean, $m)) {
            $out = 'AMD Athlon™ ' . strtoupper($m[1]);
        }
        // Intel Celeron / Pentium
        elseif (preg_match('/(?:intel\W*)?(celeron|pentium)\W*\s*[-]?\s*([0-9a-z]+)/i', $clean, $m)) {
            $out = 'Intel® ' . ucfirst(strtolower($m[1])) . '® ' . strtoupper($m[2]);
        }

        return $out === null ? null : trim(preg_replace('/\s+/', ' ', $out));
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

        $cleaned = preg_replace('/[^\d.]/', '', (string) $raw);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
