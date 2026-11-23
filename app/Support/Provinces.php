<?php

namespace App\Support;

/**
 * Daftar 38 provinsi Indonesia (pemekaran Papua 2022 sudah termasuk), dipakai form pendaftaran
 * event yang meminta provinsi (bukan kota) dan seeder konversi datanya.
 */
class Provinces
{
    public const ALL = [
        'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi',
        'Sumatera Selatan', 'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung',
        'DKI Jakarta', 'Banten', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur',
        'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
        'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
        'Sulawesi Utara', 'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara',
        'Maluku', 'Maluku Utara',
        'Papua', 'Papua Barat', 'Papua Barat Daya', 'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan',
    ];

    /** Event (slug) yang pendaftarannya meminta PROVINSI, bukan kota. */
    public const EVENT_SLUGS = ['nationals-2026'];

    public static function eventUsesProvince(?string $slug): bool
    {
        return $slug !== null && in_array($slug, self::EVENT_SLUGS, true);
    }
}
