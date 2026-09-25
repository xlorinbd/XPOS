<?php

namespace App\Console\Commands;

use App\Http\Controllers\DailyAccountController;
use App\Models\Warehouse;
use App\Services\KgNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Day-end: saves the Daily Account PDF of every active branch (and the head office accounts)
 * to storage/app/daily-accounts/YYYY-MM-DD/ and tells the accountants and managers.
 * On cPanel run "php artisan schedule:run" every minute from cron; this command is scheduled for 23:55.
 */
class DailyAccountPdf extends Command
{
    protected $signature = 'kg:daily-account {--date= : Day to close, default today (Y-m-d)}';

    protected $description = 'Save the Daily Account PDF of every branch for the day';

    public function handle(DailyAccountController $controller): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : Carbon::today();
        $dir = storage_path('app/daily-accounts/' . $date->format('Y-m-d'));
        File::ensureDirectoryExists($dir);

        $targets = [0 => 'head-office'];
        foreach (Warehouse::where('is_active', true)->orderBy('name')->get() as $w) {
            $targets[$w->id] = \Str::slug($w->name);
        }

        $done = 0;
        foreach ($targets as $warehouseId => $slug) {
            $report = $controller->build($warehouseId, $date);
            // a branch with nothing at all today (no sales, no money moved) needs no page
            $quiet = $report['summary']['invoices'] === 0
                && (float) $report['cash_sum']['in'] === 0.0 && (float) $report['cash_sum']['out'] === 0.0
                && $report['expenses']->isEmpty() && $report['transfers']->isEmpty();
            if ($quiet) {
                continue;
            }
            $pdf = \PDF::setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'DejaVu Sans'])
                ->loadView('backend.report.daily_account_pdf', ['r' => $report, 'pdf' => true])
                ->setPaper('a4', 'portrait');
            File::put($dir . '/' . $slug . '.pdf', $pdf->output());
            $done++;
        }

        $this->info("Daily Account {$date->format('Y-m-d')}: {$done} PDF(s) saved in {$dir}");
        if ($done > 0) {
            KgNotifier::toRoles(['Accountant', 'Manager', 'Admin'],
                "Daily Account for {$date->format('d M Y')} is ready ({$done} branch" . ($done > 1 ? 'es' : '') . ').',
                '/report/daily-account?date=' . $date->format('Y-m-d'), 'request');
        }
        return self::SUCCESS;
    }
}
