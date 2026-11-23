<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Rider;
use App\Support\Provinces;
use Illuminate\Database\Seeder;

/**
 * KEJUARAAN NASIONAL PIN 2026 — kolom lokasi pendaftaran sekarang berisi PROVINSI (bukan kota).
 * Seeder ini mengubah `registrations.city` event tsb dari nama kota ke provinsinya, dan menyamakan
 * `riders.city` untuk rider yang HANYA terdaftar di event ini (rider yang juga ikut event lain tidak
 * disentuh supaya profilnya di event lain tidak berubah).
 *
 * Nilai yang sudah berupa provinsi dibiarkan; kota yang belum ada di pemetaan TIDAK diubah dan
 * dilaporkan di output supaya bisa ditambahkan ke MAP lalu seeder dijalankan ulang. Aman dijalankan berulang.
 *
 *   php artisan db:seed --class=NationalsProvinceSeeder --force
 */
class NationalsProvinceSeeder extends Seeder
{
    private const EVENT_SLUG = 'nationals-2026';

    /** kota/kabupaten (huruf kecil, tanpa awalan "kota"/"kab.") => provinsi */
    private const MAP = [
        // DKI Jakarta
        'jakarta' => 'DKI Jakarta', 'jakarta pusat' => 'DKI Jakarta', 'jakarta utara' => 'DKI Jakarta',
        'jakarta barat' => 'DKI Jakarta', 'jakarta selatan' => 'DKI Jakarta', 'jakarta timur' => 'DKI Jakarta',
        'dki jakarta' => 'DKI Jakarta',
        // Jawa Barat
        'bandung' => 'Jawa Barat', 'bandung barat' => 'Jawa Barat', 'cimahi' => 'Jawa Barat', 'bogor' => 'Jawa Barat',
        'depok' => 'Jawa Barat', 'bekasi' => 'Jawa Barat', 'cileungsi' => 'Jawa Barat', 'cibinong' => 'Jawa Barat',
        'sukabumi' => 'Jawa Barat', 'cianjur' => 'Jawa Barat', 'garut' => 'Jawa Barat', 'tasikmalaya' => 'Jawa Barat',
        'cirebon' => 'Jawa Barat', 'karawang' => 'Jawa Barat', 'purwakarta' => 'Jawa Barat', 'subang' => 'Jawa Barat',
        'sumedang' => 'Jawa Barat', 'indramayu' => 'Jawa Barat', 'banjar' => 'Jawa Barat',
        // Banten
        'tangerang' => 'Banten', 'tangerang selatan' => 'Banten', 'tangsel' => 'Banten', 'serang' => 'Banten',
        'cilegon' => 'Banten', 'lebak' => 'Banten', 'pandeglang' => 'Banten',
        // Jawa Tengah
        'semarang' => 'Jawa Tengah', 'solo' => 'Jawa Tengah', 'surakarta' => 'Jawa Tengah', 'magelang' => 'Jawa Tengah',
        'salatiga' => 'Jawa Tengah', 'pekalongan' => 'Jawa Tengah', 'tegal' => 'Jawa Tengah', 'purwokerto' => 'Jawa Tengah',
        'kudus' => 'Jawa Tengah', 'klaten' => 'Jawa Tengah', 'cilacap' => 'Jawa Tengah',
        // DI Yogyakarta
        'yogyakarta' => 'DI Yogyakarta', 'jogja' => 'DI Yogyakarta', 'sleman' => 'DI Yogyakarta', 'bantul' => 'DI Yogyakarta',
        'di yogyakarta' => 'DI Yogyakarta', 'daerah istimewa yogyakarta' => 'DI Yogyakarta',
        // Jawa Timur
        'surabaya' => 'Jawa Timur', 'malang' => 'Jawa Timur', 'sidoarjo' => 'Jawa Timur', 'gresik' => 'Jawa Timur',
        'kediri' => 'Jawa Timur', 'jember' => 'Jawa Timur', 'madiun' => 'Jawa Timur', 'mojokerto' => 'Jawa Timur',
        'banyuwangi' => 'Jawa Timur', 'batu' => 'Jawa Timur',
        // Sumatera
        'medan' => 'Sumatera Utara', 'binjai' => 'Sumatera Utara', 'padang' => 'Sumatera Barat', 'bukittinggi' => 'Sumatera Barat',
        'pekanbaru' => 'Riau', 'batam' => 'Kepulauan Riau', 'tanjung pinang' => 'Kepulauan Riau', 'jambi' => 'Jambi',
        'palembang' => 'Sumatera Selatan', 'bengkulu' => 'Bengkulu', 'bandar lampung' => 'Lampung', 'lampung' => 'Lampung',
        'pangkal pinang' => 'Kepulauan Bangka Belitung', 'banda aceh' => 'Aceh',
        // Bali & Nusa Tenggara
        'denpasar' => 'Bali', 'badung' => 'Bali', 'gianyar' => 'Bali', 'mataram' => 'Nusa Tenggara Barat', 'kupang' => 'Nusa Tenggara Timur',
        // Kalimantan
        'pontianak' => 'Kalimantan Barat', 'singkawang' => 'Kalimantan Barat', 'palangkaraya' => 'Kalimantan Tengah',
        'banjarmasin' => 'Kalimantan Selatan', 'banjarbaru' => 'Kalimantan Selatan', 'balikpapan' => 'Kalimantan Timur',
        'samarinda' => 'Kalimantan Timur', 'tarakan' => 'Kalimantan Utara',
        // Sulawesi, Maluku, Papua
        'makassar' => 'Sulawesi Selatan', 'manado' => 'Sulawesi Utara', 'palu' => 'Sulawesi Tengah', 'kendari' => 'Sulawesi Tenggara',
        'gorontalo' => 'Gorontalo', 'mamuju' => 'Sulawesi Barat', 'ambon' => 'Maluku', 'ternate' => 'Maluku Utara',
        'jayapura' => 'Papua', 'sorong' => 'Papua Barat Daya', 'manokwari' => 'Papua Barat',
    ];

    public function run(): void
    {
        $event = Event::where('slug', self::EVENT_SLUG)->first();
        if (!$event) {
            $this->command?->warn('Event "' . self::EVENT_SLUG . '" tidak ditemukan — tidak ada yang diubah.');
            return;
        }

        $changed = 0;
        $unmapped = [];

        $regs = Registration::where('event_id', $event->id)->get();
        foreach ($regs as $reg) {
            $province = $this->toProvince((string) $reg->city);
            if ($province === null) {
                $unmapped[$reg->city] = ($unmapped[$reg->city] ?? 0) + 1;
                continue;
            }
            if ($reg->city !== $province) {
                $reg->update(['city' => $province]);
                $changed++;
            }
        }

        // Rider yang hanya ikut event ini: samakan juga kota di profilnya.
        $riders = 0;
        foreach ($regs as $reg) {
            $province = $this->toProvince((string) $reg->city);
            if ($province === null) continue;

            $rider = ($reg->user_id ? Rider::where('user_id', $reg->user_id)->first() : null)
                ?? Rider::where('name', $reg->name)->first();
            if (!$rider || $rider->city === $province) continue;

            $joinedElsewhere = Registration::where('event_id', '!=', $event->id)
                ->where(fn ($q) => $q->where('name', $rider->name)->when($rider->user_id, fn ($q2) => $q2->orWhere('user_id', $rider->user_id)))
                ->exists();
            if ($joinedElsewhere) continue;

            $rider->update(['city' => $province]);
            $riders++;
        }

        $this->command?->info("Registrasi diubah ke provinsi: {$changed} (dari {$regs->count()}), profil rider: {$riders}.");
        foreach ($unmapped as $city => $n) {
            $this->command?->warn("Belum ada pemetaan provinsi untuk \"{$city}\" ({$n} data) — dibiarkan.");
        }
    }

    private function toProvince(string $city): ?string
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $city));
        foreach (Provinces::ALL as $p) {
            if (mb_strtolower($p) === mb_strtolower($raw)) return $p;
        }

        $key = mb_strtolower($raw);
        $key = trim(preg_replace('/^(kota|kab\.?|kabupaten)\s+/u', '', $key));

        return self::MAP[$key] ?? null;
    }
}
