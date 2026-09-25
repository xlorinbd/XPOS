<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrmSetting extends Model
{
    protected $fillable =[
        "checkin", "checkout", "weekly_off", "otp_channel"
    ];

    /** The single settings row, created with sensible defaults if it does not exist yet. */
    public static function current(): self
    {
        return self::latest()->first() ?? self::create(['checkin' => '10:00:00', 'checkout' => '19:00:00', 'weekly_off' => 'fri', 'otp_channel' => 'email']);
    }

    /** @return string[] lower-case 3 letter day names, e.g. ['fri'] */
    public function weeklyOffDays(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', strtolower((string) $this->weekly_off)))));
    }
}
