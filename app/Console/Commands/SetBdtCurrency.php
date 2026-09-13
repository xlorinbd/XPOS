<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Currency;
use App\Models\GeneralSetting;

class SetBdtCurrency extends Command
{
    use \App\Traits\CacheForget;

    protected $signature = 'currency:bdt';

    protected $description = 'Set Bangladeshi Taka (BDT) as the primary/default currency';

    public function handle()
    {
        $bdt = Currency::updateOrCreate(
            ['code' => 'BDT'],
            [
                'name' => 'Bangladeshi Taka',
                'symbol' => 'Tk',
                'exchange_rate' => 1.0,
                'is_active' => true,
            ]
        );

        // If USD exists, make its exchange rate secondary
        Currency::where('code', 'USD')->update([
            'exchange_rate' => 120.0,
            'is_active' => true,
        ]);

        $setting = GeneralSetting::latest()->first();
        if ($setting) {
            $setting->update([
                'currency' => $bdt->id,
                'currency_position' => 'prefix',
            ]);
        }

        $this->cacheForget('currency');
        $this->cacheForget('general_setting');

        $this->info('Bangladeshi Taka (BDT) has been successfully set as your default currency!');
        return 0;
    }
}
