<?php

namespace App\Services;

use App\Models\KgNotification;
use App\Models\User;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * One place for "tell the right people". Notifications go to roles (never to a named person):
 * an in-app entry that opens the exact record, plus a WhatsApp text when WhatsApp is configured.
 *
 * Global roles (Admin, Manager) are told about everything they are listed for; branch roles only
 * when they work in the branch the event belongs to.
 */
class KgNotifier
{
    /**
     * @param  string[]  $roleNames    e.g. ['Admin', 'Manager', 'Branch Manager']
     * @param  int|null  $warehouseId  branch the event belongs to (limits non-global roles)
     * @param  string    $kind         sale | pre_order | pre_booked | cash_transfer | stock | warranty | request
     * @return int number of people told
     */
    public static function toRoles(array $roleNames, string $message, ?string $url = null, string $kind = 'info', ?int $warehouseId = null, ?int $exceptUserId = null): int
    {
        try {
            $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->all();
            if (!$roleIds) {
                return 0;
            }

            $users = User::where('is_active', true)->where('is_deleted', false)
                ->whereIn('role_id', $roleIds)
                ->when($exceptUserId, fn($q) => $q->where('id', '!=', $exceptUserId))
                ->get();

            if ($warehouseId) {
                $users = $users->filter(function (User $u) use ($warehouseId) {
                    if ((int) $u->role_id <= 2) {
                        return true;
                    }
                    $ids = $u->branches()->pluck('warehouses.id')->map(fn($i) => (int) $i)->all();
                    if ($u->warehouse_id) {
                        $ids[] = (int) $u->warehouse_id;
                    }
                    return in_array($warehouseId, $ids, true);
                });
            }

            return self::toUsers($users->all(), $message, $url, $kind);
        } catch (\Throwable $e) {
            Log::warning('KgNotifier::toRoles failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param  User[]  $users
     */
    public static function toUsers(array $users, string $message, ?string $url = null, string $kind = 'info'): int
    {
        $rows = [];
        $phones = [];
        $now = now();
        foreach ($users as $u) {
            $rows[] = ['user_id' => $u->id, 'kind' => $kind, 'message' => mb_substr($message, 0, 500), 'url' => $url, 'created_at' => $now, 'updated_at' => $now];
            if ($phone = self::waNumber($u->phone)) {
                $phones[$phone] = true;
            }
        }
        if (!$rows) {
            return 0;
        }
        DB::table('kg_notifications')->insert($rows);

        if ($phones) {
            $text = $message . ($url ? "\n" . url($url) : '');
            dispatch(function () use ($phones, $text) {
                self::whatsapp(array_keys($phones), $text);
            })->afterResponse();
        }
        return count($rows);
    }

    private static function waNumber($phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 11 && str_starts_with($digits, '01')) {
            return '88' . $digits; // Bangladesh mobile
        }
        return strlen($digits) >= 10 ? $digits : null;
    }

    private static function whatsapp(array $numbers, string $text): void
    {
        try {
            $settings = WhatsappSetting::first();
            if (!$settings || empty($settings->phone_number_id) || empty($settings->permanent_access_token)) {
                return;
            }
            $settings->sendMessage($numbers, 'text', $text);
        } catch (\Throwable $e) {
            Log::warning('KgNotifier WhatsApp failed: ' . $e->getMessage());
        }
    }
}
