<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed dropdown lists (processors, charger models, detailed-condition tags, ...).
 */
class Lookup extends Model
{
    protected $fillable = ['type', 'name', 'code', 'match_key', 'is_active'];

    public const TYPES = [
        'processor' => [
            'label' => 'Processor List',
            'singular' => 'Processor',
            'has_code' => false,
            'help' => 'Processors offered when adding a product. A typed processor that matches one of these is auto-formatted exactly as listed.',
        ],
        'charger_model' => [
            'label' => 'Charger / Adapter Models',
            'singular' => 'Charger Model',
            'has_code' => true,
            'help' => 'Each laptop is linked to the charger model it uses; the Need List compares laptops with chargers per model.',
        ],
        'cargo_company' => [
            'label' => 'Cargo Companies',
            'singular' => 'Cargo Company',
            'has_code' => true,
            'help' => 'Cargo companies used on shipments. Code can hold a phone number.',
        ],
        'hand_carry_person' => [
            'label' => 'Hand Carry Persons',
            'singular' => 'Hand Carry Person',
            'has_code' => true,
            'help' => 'People who carry goods by hand (staff or outside). Code can hold a phone number. A different name can still be typed on a shipment.',
        ],
        'damage_type' => [
            'label' => 'Damage Types',
            'singular' => 'Damage Type',
            'has_code' => false,
            'help' => 'Reasons offered when a unit is logged as damaged. Add or remove types as needed; the cost of a damaged unit is booked as a loss.',
        ],
        'condition_tag' => [
            'label' => 'Detailed Condition Tags',
            'singular' => 'Condition Tag',
            'has_code' => false,
            'help' => 'Every Detailed Condition text you type on a serial is saved here and offered as a suggestion next time.',
        ],
    ];

    protected static function booted(): void
    {
        static::saving(function (Lookup $lookup) {
            if ($lookup->type === 'processor') {
                $lookup->match_key = self::processorKey($lookup->name);
            }
        });
    }

    public static function processorKey(?string $raw): string
    {
        $s = mb_strtolower((string) $raw);
        $s = str_replace(['®', '™', '(r)', '(tm)'], '', $s);
        $s = preg_replace('/\b(intel|amd)\b/u', '', $s);
        return preg_replace('/[^a-z0-9]/', '', $s);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Save a free-text value as a reusable entry (used for detailed-condition tags).
     */
    public static function remember(string $type, ?string $name): void
    {
        $name = trim((string) $name);
        if ($name === '') {
            return;
        }
        static::firstOrCreate(['type' => $type, 'name' => $name], ['is_active' => true]);
    }
}
