<?php

namespace App\Services;

use App\Models\Product;

/**
 * "Last Border Price" is a soft floor: selling below it is allowed, but the seller sees a warning
 * and has to type a reason, which is stored on the sale / pre-order.
 */
class BorderPrice
{
    public const MIN_REASON_LENGTH = 3;

    /**
     * @param  array $productIds
     * @param  array $prices unit price actually charged, same order as $productIds
     * @return array list of ['name' => string, 'price' => float, 'floor' => float]
     */
    public static function violations(array $productIds, array $prices): array
    {
        $found = [];
        foreach ($productIds as $idx => $pid) {
            $product = Product::find($pid);
            $floor = $product ? (float) $product->last_border_price : 0.0;
            $price = (float) ($prices[$idx] ?? 0);
            if ($floor > 0 && $price < $floor) {
                $found[] = ['name' => $product->name, 'price' => $price, 'floor' => $floor];
            }
        }
        return $found;
    }

    public static function cleanReason($reason): ?string
    {
        $reason = trim((string) $reason);
        return mb_strlen($reason) >= self::MIN_REASON_LENGTH ? mb_substr($reason, 0, 500) : null;
    }

    /**
     * Returns the reason to store (null when no line is below the floor), or a 422 JSON response
     * telling the page to ask for a reason.
     *
     * @return array{0: ?string, 1: ?\Illuminate\Http\JsonResponse}
     */
    public static function check(array $productIds, array $prices, $reasonInput): array
    {
        $violations = self::violations($productIds, $prices);
        if (!$violations) {
            return [null, null];
        }
        $reason = self::cleanReason($reasonInput);
        if ($reason) {
            return [$reason, null];
        }
        $lines = array_map(fn($v) => $v['name'] . ': ' . amount_format($v['price']) . ' (border price ' . amount_format($v['floor']) . ')', $violations);
        return [null, response()->json([
            'error' => 'Below the border price. A reason is required: ' . implode('; ', $lines),
            'needs_border_reason' => true,
            'lines' => $lines,
        ], 422)];
    }
}
