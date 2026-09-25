<?php

namespace Tests\Feature\KhanGadget;

use App\Models\Product;
use App\Services\BorderPrice;

class BorderPriceTest extends KgTestCase
{
    private function product(float $floor): Product
    {
        return Product::forceCreate([
            'name' => 'T Laptop', 'code' => 'T-' . uniqid(), 'type' => 'standard', 'barcode_symbology' => 'C128', 'brand_id' => null,
            'category_id' => 1, 'unit_id' => 1, 'purchase_unit_id' => 1, 'sale_unit_id' => 1, 'cost' => 40000, 'price' => 52000,
            'last_border_price' => $floor, 'qty' => 0, 'is_active' => 1,
        ]);
    }

    public function test_selling_below_the_border_price_needs_a_reason_but_is_not_blocked(): void
    {
        $p = $this->product(45000);

        [$reason, $response] = BorderPrice::check([$p->id], [40000], null);
        $this->assertNull($reason);
        $this->assertNotNull($response, 'a reason must be asked for');
        $this->assertSame(422, $response->getStatusCode());
        $this->assertTrue($response->getData()->needs_border_reason);

        [$reason, $response] = BorderPrice::check([$p->id], [40000], 'Old stock clearance');
        $this->assertNull($response);
        $this->assertSame('Old stock clearance', $reason);
    }

    public function test_price_at_or_above_the_floor_needs_nothing(): void
    {
        $p = $this->product(45000);
        [$reason, $response] = BorderPrice::check([$p->id], [45000], null);
        $this->assertNull($reason);
        $this->assertNull($response);
    }

    public function test_a_one_letter_reason_is_not_accepted(): void
    {
        $p = $this->product(45000);
        [, $response] = BorderPrice::check([$p->id], [30000], 'x');
        $this->assertNotNull($response);
    }
}
