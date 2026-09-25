<?php

use Carbon\Carbon;

if (!function_exists('normalize_to_sql_datetime')) {
    function normalize_to_sql_datetime($input, $useCurrentTime = false)
    {
        if (empty($input)) {
            return Carbon::now()->format('Y-m-d H:i:s');
        }

        $input = trim($input);

        // Replace multiple possible separators with "-"
        $normalized = preg_replace('/[\/\.\s]+/', '-', $input);

        // Formats to test (you can add more if needed)
        $formats = [
            'd-m-Y',
            'd/m/Y',
            'd.m.Y',
            'm-d-Y',
            'm/d/Y',
            'm.d.Y',
            'Y-m-d',
            'Y/m/d',
            'Y.m.d',
        ];

        foreach ($formats as $fmt) {
            try {
                $date = Carbon::createFromFormat($fmt, $normalized);

                if ($date !== false) {
                    if ($useCurrentTime) {
                        // inject current time if only date provided
                        $date->setTimeFrom(Carbon::now());
                    }
                    return $date->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // just continue to next format
            }
        }

        // fallback: try Carbon::parse (loose parsing)
        try {
            $date = Carbon::parse($input);
            if ($useCurrentTime) {
                $date->setTimeFrom(Carbon::now());
            }
            return $date->format('Y-m-d').' '.date('H:i:s');
        } catch (\Exception $e) {
            // totally failed → return current datetime
            return Carbon::now()->format('Y-m-d H:i:s');
        }
    }
}

if (!function_exists('indian_number_format')) {
    /**
     * number_format() with lakh/crore grouping (12,34,567.50). Same signature as number_format().
     */
    function indian_number_format($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    {
        $number = (float) $number;
        $negative = $number < 0;
        $plain = number_format(abs($number), (int) $decimals, '.', '');
        $parts = explode('.', $plain);
        $int = $parts[0];
        $frac = $parts[1] ?? '';

        if (strlen($int) > 3) {
            $last3 = substr($int, -3);
            $rest = substr($int, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', $thousandsSep, $rest);
            $int = $rest . $thousandsSep . $last3;
        }

        $out = $int . ($frac !== '' ? $decPoint . $frac : '');
        return $negative ? '-' . $out : $out;
    }
}

if (!function_exists('amount_format')) {
    /**
     * Display amount: lakh/crore commas, decimals only when the value actually has them (40,000 / 40,000.50).
     */
    function amount_format($amount, $decimals = null)
    {
        $decimals = $decimals ?? (int) config('decimal', 2);
        $value = round((float) $amount, $decimals);
        if ($value == floor($value)) {
            $decimals = 0;
        }
        return indian_number_format($value, $decimals);
    }
}

if (!function_exists('money')) {
    /**
     * Amount with the currency symbol, e.g. ৳4,00,000
     */
    function money($amount, $decimals = null)
    {
        $symbol = config('currency', '৳');
        $formatted = amount_format($amount, $decimals);
        $sign = '';
        if (strpos($formatted, '-') === 0) {
            $sign = '-';
            $formatted = substr($formatted, 1);
        }
        return $sign . (config('currency_position') === 'suffix' ? $formatted . $symbol : $symbol . $formatted);
    }
}

if (!function_exists('can_view_cost')) {
    /**
     * Purchase price / cost / profit are visible only to roles holding the 'view-cost-profit' permission
     * (Admin and Manager by default).
     */
    function can_view_cost(): bool
    {
        static $cache = [];
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }
        if (!array_key_exists($user->id, $cache)) {
            try {
                $role = \Spatie\Permission\Models\Role::find($user->role_id);
                $cache[$user->id] = $role ? $role->hasPermissionTo('view-cost-profit') : false;
            } catch (\Throwable $e) {
                $cache[$user->id] = $user->role_id <= 2;
            }
        }
        return $cache[$user->id];
    }
}

if (!function_exists('category_paths')) {
    /**
     * Turns a flat category list into one whose `name` is the full path ("Accessories › Charger › HP"),
     * sorted by that path, so a 3-level tree stays unambiguous inside dropdowns.
     */
    function category_paths($categories)
    {
        $categories = collect($categories);
        $byId = $categories->keyBy('id');
        return $categories->map(function ($category) use ($byId) {
            $names = [];
            $current = $category;
            $guard = 0;
            while ($current && $guard++ < 6) {
                array_unshift($names, data_get($current, 'name'));
                $parentId = data_get($current, 'parent_id');
                $current = $parentId ? $byId->get($parentId) : null;
            }
            $category->name = implode(' › ', $names);
            return $category;
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
}

if (!function_exists('remember_condition_tags')) {
    /**
     * Saves detailed-condition texts as reusable tags (suggested next time on any serial).
     */
    function remember_condition_tags(array $conditions): void
    {
        try {
            foreach (array_unique(array_filter(array_map('trim', array_map('strval', $conditions)))) as $text) {
                \App\Models\Lookup::remember('condition_tag', $text);
            }
        } catch (\Throwable $e) {
            // never block stock entry because of the tag list
        }
    }
}

if (!function_exists('kg_transfer_counts')) {
    /**
     * Cash transfers waiting for the logged-in user (to accept / refunds to accept), for menu indicators.
     */
    function kg_transfer_counts(): array
    {
        static $cache = [];
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return ['incoming' => 0, 'refunds' => 0];
        }
        if (!isset($cache[$user->id])) {
            try {
                $cache[$user->id] = app(\App\Services\MoneyTransferService::class)->pendingCounts($user);
            } catch (\Throwable $e) {
                $cache[$user->id] = ['incoming' => 0, 'refunds' => 0];
            }
        }
        return $cache[$user->id];
    }

    function kg_pending_gateway_count(): int
    {
        static $count = null;
        if ($count === null) {
            try {
                $count = (int) \Illuminate\Support\Facades\DB::table('payments')->where('confirm_status', 'pending')->count();
            } catch (\Throwable $e) {
                $count = 0;
            }
        }
        return $count;
    }

    function kg_unread_notifications()
    {
        static $cache = [];
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return collect();
        }
        if (!isset($cache[$user->id])) {
            try {
                $cache[$user->id] = \App\Models\KgNotification::where('user_id', $user->id)->whereNull('read_at')->orderByDesc('id')->limit(15)->get();
            } catch (\Throwable $e) {
                $cache[$user->id] = collect();
            }
        }
        return $cache[$user->id];
    }

    /**
     * Cost of damaged units that are written off (logged as damaged and not sent to a supplier).
     * A unit that goes through a supplier RMA books its own loss as an expense, so it is not counted twice.
     */
    function kg_damage_loss($startDate, $endDate, $warehouseId = 0): float
    {
        try {
            return (float) \Illuminate\Support\Facades\DB::table('damage_records')
                ->whereNull('deleted_at')->where('status', 'logged')
                ->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->sum('damage_cost');
        } catch (\Throwable $e) {
            return 0.0;
        }
    }
}
