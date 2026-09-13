<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Preset: Continuous Thermal 50mm x 30mm (Laptop Specs & Barcode)
        // 50mm = 1.9685 inches, 30mm = 1.1811 inches
        DB::table('barcodes')->updateOrInsert(
            ['name' => 'Continuous Thermal - 50mm x 30mm (Laptop Specs)'],
            [
                'description' => 'Thermal Roll Size: 50mm x 30mm (1.97" x 1.18"), 1 sticker per row, continuous',
                'width' => 1.9685,
                'height' => 1.1811,
                'paper_width' => 1.9685,
                'paper_height' => 1.1811,
                'top_margin' => 0,
                'left_margin' => 0,
                'row_distance' => 0,
                'col_distance' => 0,
                'stickers_in_one_row' => 1,
                'stickers_in_one_sheet' => 1,
                'is_continuous' => 1,
                'is_default' => 0,
                'is_custom' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Preset: Continuous Thermal 38mm x 25mm (Gadget / Compact)
        // 38mm = 1.4961 inches, 25mm = 0.9842 inches
        DB::table('barcodes')->updateOrInsert(
            ['name' => 'Continuous Thermal - 38mm x 25mm (Compact Gadget)'],
            [
                'description' => 'Thermal Roll Size: 38mm x 25mm (1.50" x 0.98"), 1 sticker per row, continuous',
                'width' => 1.4961,
                'height' => 0.9842,
                'paper_width' => 1.4961,
                'paper_height' => 0.9842,
                'top_margin' => 0,
                'left_margin' => 0,
                'row_distance' => 0,
                'col_distance' => 0,
                'stickers_in_one_row' => 1,
                'stickers_in_one_sheet' => 1,
                'is_continuous' => 1,
                'is_default' => 0,
                'is_custom' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('barcodes')->whereIn('name', [
            'Continuous Thermal - 50mm x 30mm (Laptop Specs)',
            'Continuous Thermal - 38mm x 25mm (Compact Gadget)'
        ])->delete();
    }
};
