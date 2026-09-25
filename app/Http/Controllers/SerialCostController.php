<?php

namespace App\Http\Controllers;

use App\Models\ProductSerial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Add to or take off a single unit's cost after it was bought: repair, configuration upgrade / downgrade,
 * an accessory added or removed. Only people who may see the cost can change it, and every change is kept with its reason.
 */
class SerialCostController extends Controller
{
    public function adjust(Request $request, int $id)
    {
        abort_unless(can_view_cost(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:255',
        ]);

        $serial = ProductSerial::findOrFail($id);
        $new = round((float) $serial->extra_cost + (float) $data['amount'], 2);
        if ($serial->purchase_cost + $serial->landed_cost + $new < 0) {
            return response()->json(['error' => 'The cost of a unit cannot go below zero.'], 422);
        }

        DB::transaction(function () use ($serial, $data, $new) {
            $serial->extra_cost = $new;
            $serial->save();
            DB::table('serial_cost_adjustments')->insert([
                'product_serial_id' => $serial->id, 'amount' => $data['amount'], 'reason' => $data['reason'],
                'user_id' => Auth::id(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $history = DB::table('serial_cost_adjustments')->where('product_serial_id', $serial->id)->orderByDesc('id')->limit(10)->get(['amount', 'reason', 'created_at']);
        return response()->json([
            'ok' => true,
            'extra_cost' => $serial->extra_cost,
            'total_cost' => $serial->total_cost,
            'history' => $history,
        ]);
    }
}
