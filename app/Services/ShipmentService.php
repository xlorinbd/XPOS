<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Product_Warehouse;
use App\Models\ProductPurchase;
use App\Models\ProductSerial;
use App\Models\Purchase;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Foreign purchase shipments: In-Transit -> BD Warehouse Received -> Final Entry (stock added).
 * Nothing here changes stock until finalize().
 */
class ShipmentService
{
    /**
     * Quantity of a purchase line that can still be put on a new shipment.
     */
    public function remainingToShip(ProductPurchase $line): float
    {
        $committed = ShipmentItem::query()
            ->join('shipments', 'shipments.id', '=', 'shipment_items.shipment_id')
            ->where('shipment_items.product_purchase_id', $line->id)
            ->selectRaw("COALESCE(SUM(CASE
                WHEN shipments.status IN ('in_transit','received') THEN shipment_items.qty_shipped
                WHEN shipments.status = 'final_entry' THEN shipment_items.qty_finalized
                ELSE 0 END), 0) as committed")
            ->value('committed');

        return max(0, (float) $line->qty - (float) $committed);
    }

    public function nextReference(): string
    {
        $prefix = 'SH-' . date('Ymd') . '-';
        $n = Shipment::where('reference_no', 'like', $prefix . '%')->count() + 1;
        do {
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (Shipment::where('reference_no', $ref)->exists());

        return $ref;
    }

    /**
     * @param array $data   shipment header fields
     * @param array $items  [product_purchase_id => qty_shipped]
     */
    public function create(array $data, array $items, int $userId): Shipment
    {
        $items = array_filter($items, fn($q) => (float) $q > 0);
        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one product with a shipped quantity.']);
        }

        return DB::transaction(function () use ($data, $items, $userId) {
            $shipment = Shipment::create([
                'reference_no' => $this->nextReference(),
                'shipment_type' => $data['shipment_type'],
                'other_method' => $data['other_method'] ?? null,
                'carrier_name' => $data['carrier_name'] ?? null,
                'carrier_contact' => $data['carrier_contact'] ?? null,
                'tracking_no' => $data['tracking_no'] ?? null,
                'responsible_person' => $data['responsible_person'] ?? null,
                'shipment_date' => $data['shipment_date'] ?? null,
                'expected_arrival' => $data['expected_arrival'] ?? null,
                'destination_warehouse_id' => $data['destination_warehouse_id'],
                'status' => 'in_transit',
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'customs_cost' => $data['customs_cost'] ?? 0,
                'additional_cost' => $data['additional_cost'] ?? 0,
                'cost_account_id' => $data['cost_account_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $lineId => $qty) {
                $line = ProductPurchase::lockForUpdate()->find($lineId);
                if (!$line) {
                    throw ValidationException::withMessages(['items' => 'A purchase line no longer exists.']);
                }
                $purchase = Purchase::find($line->purchase_id);
                if (!$purchase || $purchase->purchase_type !== 'foreign') {
                    throw ValidationException::withMessages(['items' => 'Only foreign purchases can be shipped.']);
                }
                $remaining = $this->remainingToShip($line);
                if ((float) $qty > $remaining + 0.0001) {
                    $name = Product::where('id', $line->product_id)->value('name');
                    throw ValidationException::withMessages(['items' => "{$name}: only " . $remaining . " left to ship on {$purchase->reference_no}."]);
                }

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'purchase_id' => $line->purchase_id,
                    'product_purchase_id' => $line->id,
                    'product_id' => $line->product_id,
                    'qty_shipped' => $qty,
                ]);
            }

            return $shipment;
        });
    }

    /**
     * Step 2: goods reached the Bangladesh warehouse; quantities are verified. No stock yet.
     *
     * @param array $received [shipment_item_id => ['qty' => x, 'remarks' => '...']]
     */
    public function markReceived(Shipment $shipment, array $received, ?string $arrivalDate, int $userId): Shipment
    {
        if ($shipment->status !== 'in_transit') {
            throw ValidationException::withMessages(['status' => 'Only an in-transit shipment can be marked as received.']);
        }

        return DB::transaction(function () use ($shipment, $received, $arrivalDate, $userId) {
            foreach ($shipment->items as $item) {
                $row = $received[$item->id] ?? [];
                $qty = isset($row['qty']) && $row['qty'] !== '' ? (float) $row['qty'] : (float) $item->qty_shipped;
                if ($qty < 0) {
                    throw ValidationException::withMessages(['items' => 'Received quantity cannot be negative.']);
                }
                if ($qty > (float) $item->qty_shipped + 0.0001) {
                    throw ValidationException::withMessages(['items' => 'Received quantity cannot be more than the shipped quantity. Correct the shipment quantity first.']);
                }
                $item->update(['qty_received' => $qty, 'remarks' => $row['remarks'] ?? $item->remarks]);
            }

            $shipment->update([
                'status' => 'received',
                'actual_arrival' => $arrivalDate ?: now()->toDateString(),
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            return $shipment->fresh();
        });
    }

    /**
     * Step 3: serial numbers + landed cost are entered and stock is added.
     *
     * @param array $costs  ['shipping_cost','customs_cost','additional_cost','cost_account_id']
     * @param array $lines  [shipment_item_id => ['track_serials' => bool, 'serials' => "SN1\nSN2:cond"]]
     */
    public function finalize(Shipment $shipment, array $costs, array $lines, int $userId): Shipment
    {
        if ($shipment->status !== 'received') {
            throw ValidationException::withMessages(['status' => 'Mark the shipment as received before the final entry.']);
        }

        $total = (float) ($costs['shipping_cost'] ?? 0) + (float) ($costs['customs_cost'] ?? 0) + (float) ($costs['additional_cost'] ?? 0);
        if ($total > 0 && empty($costs['cost_account_id'])) {
            throw ValidationException::withMessages(['cost_account_id' => 'Choose the account the shipment costs were paid from.']);
        }

        return DB::transaction(function () use ($shipment, $costs, $lines, $userId, $total) {
            $shipment = Shipment::lockForUpdate()->find($shipment->id);
            if ($shipment->status !== 'received') {
                throw ValidationException::withMessages(['status' => 'This shipment was already finalized.']);
            }

            $items = $shipment->items()->where('qty_received', '>', 0)->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Nothing was received on this shipment.']);
            }

            // 1. parse and validate serials first (nothing is written if any line is wrong)
            $plans = [];
            $allSerials = [];
            $pieces = 0;
            foreach ($items as $item) {
                $line = ProductPurchase::lockForUpdate()->find($item->product_purchase_id);
                $product = Product::lockForUpdate()->find($item->product_id);
                $baseQty = $this->baseQty($line, (float) $item->qty_received);
                $cfg = $lines[$item->id] ?? [];
                $track = !empty($cfg['track_serials']);

                // Serial tracking recalculates stock from serial numbers, so it must not be mixed with stock that has none.
                $availableSerials = ProductSerial::where('product_id', $product->id)->where('status', 'available')->count();
                $untracked = max(0, (int) round((float) $product->qty) - $availableSerials);
                if ($track && $untracked > 0) {
                    throw ValidationException::withMessages(['items' => "{$product->name}: {$untracked} unit(s) already in stock have no serial number. Give those units serial numbers first, or receive this line without serial tracking."]);
                }
                if (!$track && $product->is_imei) {
                    throw ValidationException::withMessages(['items' => "{$product->name} is tracked by serial number. Enter a serial number for every unit."]);
                }

                $serials = [];
                if ($track) {
                    if (floor($baseQty) != $baseQty) {
                        throw ValidationException::withMessages(['items' => "{$product->name}: serial-tracked items need a whole quantity."]);
                    }
                    $serials = $this->parseSerials((string) ($cfg['serials'] ?? ''));
                    if (count($serials) !== (int) $baseQty) {
                        throw ValidationException::withMessages(['items' => "{$product->name}: enter exactly " . (int) $baseQty . ' serial numbers (found ' . count($serials) . ').']);
                    }
                    foreach ($serials as $s) {
                        if (isset($allSerials[$s['serial']])) {
                            throw ValidationException::withMessages(['items' => "Serial {$s['serial']} is entered twice."]);
                        }
                        $allSerials[$s['serial']] = true;
                    }
                }

                $plans[] = compact('item', 'line', 'product', 'baseQty', 'track', 'serials');
                $pieces += (int) ceil($baseQty);
            }

            if (!empty($allSerials)) {
                $existing = ProductSerial::withTrashed()->whereIn('serial_number', array_keys($allSerials))->pluck('serial_number')->all();
                if ($existing) {
                    throw ValidationException::withMessages(['items' => 'Serial number(s) already in the system: ' . implode(', ', $existing)]);
                }
            }

            // 2. landed cost, split equally per piece (rounded to whole paisa; the remainder goes to the first pieces)
            $totalCents = (int) round($total * 100);
            $perPiece = intdiv($totalCents, max(1, $pieces));
            $remainder = $totalCents % max(1, $pieces);
            $pieceIndex = 0;
            $shareCents = function (int $count) use (&$pieceIndex, $perPiece, $remainder) {
                $cents = [];
                for ($k = 0; $k < $count; $k++) {
                    $cents[] = $perPiece + ($pieceIndex < $remainder ? 1 : 0);
                    $pieceIndex++;
                }
                return $cents;
            };

            // 3. write stock
            foreach ($plans as $plan) {
                $item = $plan['item'];
                $line = $plan['line'];
                $product = $plan['product'];
                $baseQty = $plan['baseQty'];
                $unitCost = (float) $line->net_unit_cost;
                $count = (int) ceil($baseQty);
                $cents = $shareCents($count);
                $wh = $shipment->destination_warehouse_id;

                if ($plan['track']) {
                    foreach ($plan['serials'] as $idx => $s) {
                        ProductSerial::withoutEvents(function () use ($product, $s, $wh, $line, $shipment, $unitCost, $cents, $idx) {
                            ProductSerial::create([
                                'product_id' => $product->id,
                                'serial_number' => $s['serial'],
                                'detailed_condition' => $s['condition'],
                                'status' => 'available',
                                'warehouse_id' => $wh,
                                'purchase_id' => $line->purchase_id,
                                'shipment_id' => $shipment->id,
                                'purchase_cost' => $unitCost,
                                'landed_cost' => $cents[$idx] / 100,
                            ]);
                        });
                    }
                    remember_condition_tags(array_column($plan['serials'], 'condition'));
                    $product->is_imei = 1;
                    $avgLanded = array_sum($cents) / 100 / max(1, $count);
                    $product->cost = round($unitCost + $avgLanded, 2);
                    $product->save();
                    $product->syncSerialStock($wh);
                } else {
                    $avgLanded = array_sum($cents) / 100 / max(1, $count);
                    $newUnitCost = $unitCost + $avgLanded;
                    $oldQty = (float) $product->qty;
                    $product->cost = $oldQty > 0
                        ? round((($oldQty * (float) $product->cost) + ($baseQty * $newUnitCost)) / ($oldQty + $baseQty), 2)
                        : round($newUnitCost, 2);
                    $product->qty = $oldQty + $baseQty;
                    $product->save();

                    $pw = Product_Warehouse::where('product_id', $product->id)->where('warehouse_id', $wh)->first();
                    if ($pw) {
                        $pw->qty = (float) $pw->qty + $baseQty;
                        $pw->save();
                    } else {
                        Product_Warehouse::create(['product_id' => $product->id, 'warehouse_id' => $wh, 'qty' => $baseQty, 'price' => $product->price]);
                    }
                }

                $line->recieved = (float) $line->recieved + (float) $item->qty_received;
                $line->save();
                $item->update(['qty_finalized' => $item->qty_received]);
            }

            // 4. purchase statuses
            foreach ($items->pluck('purchase_id')->unique() as $purchaseId) {
                $this->refreshPurchaseStatus((int) $purchaseId);
            }

            // 5. shipment costs become an expense from the chosen account
            $expenseId = null;
            if ($total > 0) {
                $category = ExpenseCategory::firstOrCreate(['name' => 'Shipment & Customs'], ['code' => 'shipment', 'is_active' => true]);
                $expense = Expense::create([
                    'reference_no' => 'er-' . date('Ymd') . '-' . date('his'),
                    'expense_category_id' => $category->id,
                    'warehouse_id' => $shipment->destination_warehouse_id,
                    'account_id' => $costs['cost_account_id'],
                    'user_id' => $userId,
                    'amount' => $total,
                    'note' => 'Shipment ' . $shipment->reference_no . ' - shipping, customs and additional cost',
                ]);
                $expenseId = $expense->id;
            }

            $shipment->update([
                'status' => 'final_entry',
                'shipping_cost' => $costs['shipping_cost'] ?? 0,
                'customs_cost' => $costs['customs_cost'] ?? 0,
                'additional_cost' => $costs['additional_cost'] ?? 0,
                'cost_account_id' => $costs['cost_account_id'] ?? null,
                'expense_id' => $expenseId,
                'finalized_at' => now(),
                'finalized_by' => $userId,
            ]);

            return $shipment->fresh();
        });
    }

    public function cancel(Shipment $shipment): Shipment
    {
        if (!in_array($shipment->status, ['in_transit', 'received'], true)) {
            throw ValidationException::withMessages(['status' => 'Only a shipment that has not been added to stock can be cancelled.']);
        }
        $shipment->update(['status' => 'cancelled']);
        return $shipment;
    }

    public function refreshPurchaseStatus(int $purchaseId): void
    {
        $lines = ProductPurchase::where('purchase_id', $purchaseId)->get();
        $allIn = $lines->every(fn($l) => (float) $l->recieved + 0.0001 >= (float) $l->qty);
        $anyIn = $lines->contains(fn($l) => (float) $l->recieved > 0);
        Purchase::where('id', $purchaseId)->update(['status' => $allIn ? 1 : ($anyIn ? 2 : 4)]);
    }

    private function baseQty(ProductPurchase $line, float $qty): float
    {
        $unit = Unit::find($line->purchase_unit_id);
        if (!$unit || !(float) $unit->operation_value) {
            return $qty;
        }
        return $unit->operator == '*' ? $qty * $unit->operation_value : $qty / $unit->operation_value;
    }

    /**
     * "SN1", "SN2:Body scratch" ... separated by new lines or commas.
     */
    private function parseSerials(string $text): array
    {
        $out = [];
        foreach (preg_split('/[\r\n,]+/', $text, -1, PREG_SPLIT_NO_EMPTY) as $rawLine) {
            $rawLine = trim($rawLine);
            if ($rawLine === '') {
                continue;
            }
            if (strpos($rawLine, ':') !== false) {
                [$sn, $cond] = explode(':', $rawLine, 2);
                $out[] = ['serial' => trim($sn), 'condition' => trim($cond) !== '' ? trim($cond) : null];
            } else {
                $out[] = ['serial' => $rawLine, 'condition' => null];
            }
        }
        return array_values(array_filter($out, fn($s) => $s['serial'] !== ''));
    }
}
