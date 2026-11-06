<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return static::find($key)?->value ?? $default;
    }

    /** Marquee homepage: [enabled, speed (detik per putaran), items[]]. */
    public static function marquee(): array
    {
        $items = json_decode(static::get('marquee_items', ''), true);

        return [
            'enabled' => static::get('marquee_enabled', '1') === '1',
            'speed'   => max(5, (int) static::get('marquee_speed', 30)),
            'items'   => is_array($items) ? $items : [
                "FRAMEBLADESCORE NATIONALS '26", 'AUG 22–24 · JAKARTA', 'STREET · PARK · VERT',
                'Rp 250 JT PRIZE POOL', 'REGISTRATION OPEN',
            ],
        ];
    }

    /** Role yang bisa dipilih untuk akses /live. 'guest' = pengunjung yang belum login. */
    public const LIVE_ACCESS_ROLES = [
        'guest'      => 'Publik (belum login)',
        'rider'      => 'Rider',
        'judge'      => 'Judge',
        'head_judge' => 'Head Judge',
        'operator'   => 'Operator',
        'registrar'  => 'Registrar',
        'admin'      => 'Admin',
    ];

    /** Role yang boleh membuka /live. Default = perilaku lama (staf saja); admin selalu boleh. */
    public static function liveAccessRoles(): array
    {
        $roles = json_decode(static::get('live_access_roles', ''), true);
        $roles = is_array($roles) ? $roles : ['admin', 'judge', 'head_judge', 'operator'];

        return array_values(array_unique(array_merge(['admin'], $roles)));
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
