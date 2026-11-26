<?php

namespace App\Console\Commands;

use App\Models\Registration;
use App\Models\Rider;
use App\Support\AvatarThumb;
use Illuminate\Console\Command;

class GenerateAvatarThumbs extends Command
{
    protected $signature = 'avatars:thumbs {--force : Buat ulang meski thumbnail sudah ada}';

    protected $description = 'Buat thumbnail 160px untuk semua foto rider/registrasi (dipakai avatar leaderboard /live)';

    public function handle(): int
    {
        $paths = Registration::whereNotNull('photo')->pluck('photo')
            ->merge(Rider::whereNotNull('avatar')->pluck('avatar'))
            ->filter()->unique()->values();

        $this->info("Foto unik: {$paths->count()}");

        $count = ['created' => 0, 'exists' => 0, 'missing' => 0, 'skipped' => 0];
        $bar   = $this->output->createProgressBar($paths->count());
        foreach ($paths as $path) {
            $count[AvatarThumb::make($path, (bool) $this->option('force'))]++;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['Hasil', 'Jumlah'], collect($count)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->line('created = thumbnail baru, exists = sudah ada, missing = file asli tidak ada, skipped = tidak bisa diproses (mis. WebP di GD tanpa WebP, atau URL luar)');

        return self::SUCCESS;
    }
}
