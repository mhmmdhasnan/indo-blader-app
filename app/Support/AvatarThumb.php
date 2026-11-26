<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Thumbnail foto rider untuk lingkaran avatar kecil (leaderboard /live dll.).
 *
 * Foto asli (hingga 2 MB) tetap dipakai untuk tampilan besar; avatar 36-100 px cukup
 * memakai JPEG persegi 160 px (~5-15 KB). Output JPEG karena GD di image produksi
 * tidak dikompilasi dengan WebP. Kalau thumbnail belum ada, url() jatuh ke foto asli.
 */
class AvatarThumb
{
    public const SIZE = 160;
    public const DIR  = 'avatars/thumbs';

    /** Batas piksel yang masih aman di-decode GD (memori ~4 byte/piksel). */
    private const MAX_PIXELS = 40_000_000;

    public static function thumbPath(string $path): string
    {
        return self::DIR . '/' . pathinfo($path, PATHINFO_FILENAME) . '.jpg';
    }

    /** URL thumbnail kalau ada; kalau belum, URL foto asli. Path http(s) dikembalikan apa adanya. */
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $thumb = self::thumbPath($path);

        return asset('storage/' . (Storage::disk('public')->exists($thumb) ? $thumb : $path));
    }

    /**
     * Buat thumbnail dari foto di disk public. Tidak pernah melempar exception
     * (upload tidak boleh gagal karena thumbnail).
     *
     * @return string created|exists|missing|skipped
     */
    public static function make(string $path, bool $force = false): string
    {
        if (str_starts_with($path, 'http')) {
            return 'skipped';
        }

        try {
            $disk  = Storage::disk('public');
            $thumb = self::thumbPath($path);

            if (!$force && $disk->exists($thumb)) {
                return 'exists';
            }
            if (!$disk->exists($path)) {
                return 'missing';
            }

            $bytes = $disk->get($path);
            $info  = @getimagesizefromstring($bytes);
            if (!$info || ($info[0] * $info[1]) > self::MAX_PIXELS) {
                return 'skipped';
            }

            $src = @imagecreatefromstring($bytes);   // WebP/PNG/JPEG/GIF sesuai dukungan GD
            if (!$src) {
                return 'skipped';
            }

            $src = self::applyOrientation($src, self::jpegOrientation($bytes));

            $w    = imagesx($src);
            $h    = imagesy($src);
            $side = min($w, $h);
            $x    = intdiv($w - $side, 2);   // tengah horizontal, atas vertikal = object-position: top center di UI

            $dst = imagecreatetruecolor(self::SIZE, self::SIZE);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 26, 26, 29));   // latar untuk PNG transparan
            imagecopyresampled($dst, $src, 0, 0, $x, 0, self::SIZE, self::SIZE, $side, $side);

            ob_start();
            imagejpeg($dst, null, 82);
            $jpeg = (string) ob_get_clean();

            $disk->put($thumb, $jpeg);

            return 'created';
        } catch (Throwable $e) {
            report($e);

            return 'skipped';
        }
    }

    private static function applyOrientation(\GdImage $img, int $orientation): \GdImage
    {
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,   // perlu diputar 90° searah jarum jam
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $img;
        }

        return imagerotate($img, $angle, 0) ?: $img;
    }

    /**
     * Baca tag EXIF Orientation (0x0112) dari JPEG tanpa ekstensi exif
     * (tidak terpasang di image produksi). 1 = normal / tidak ada.
     */
    private static function jpegOrientation(string $b): int
    {
        try {
            if (substr($b, 0, 2) !== "\xFF\xD8") {
                return 1;
            }

            $len = strlen($b);
            $i   = 2;
            while ($i + 4 <= $len && $b[$i] === "\xFF") {
                $marker = ord($b[$i + 1]);
                $size   = unpack('n', substr($b, $i + 2, 2))[1];

                if ($marker === 0xE1 && substr($b, $i + 4, 6) === "Exif\0\0") {
                    $t  = substr($b, $i + 10, max(0, $size - 8));   // mulai dari header TIFF
                    $le = substr($t, 0, 2) === 'II';
                    $u16 = fn (int $o) => unpack($le ? 'v' : 'n', substr($t, $o, 2))[1];
                    $u32 = fn (int $o) => unpack($le ? 'V' : 'N', substr($t, $o, 4))[1];

                    $ifd = $u32(4);
                    $n   = $u16($ifd);
                    for ($k = 0; $k < $n; $k++) {
                        $e = $ifd + 2 + $k * 12;
                        if ($u16($e) === 0x0112) {
                            return $u16($e + 8);
                        }
                    }

                    return 1;
                }

                if ($marker === 0xDA) {   // mulai data gambar: tidak ada EXIF lagi
                    break;
                }
                $i += 2 + $size;
            }
        } catch (Throwable) {
            // EXIF rusak: anggap normal
        }

        return 1;
    }
}
