<?php

namespace App\Services;

use App\Models\Event;
use App\Models\DivisionGroup;
use App\Models\EventDivision;
use App\Models\Registration;
use Carbon\Carbon;
use App\Models\Rider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Imports rider registrations from the Google Form "Responses" spreadsheet
 * (columns: Timestamp, Nama, Usia, Domisili, Komunitas, No Handphone, Gender,
 * Kategori, Kategori Usia, Saya Bersedia, Photo, Bukti Transfer).
 *
 * Two steps so the admin can fix division mapping before anything is written:
 * parse() reads the file into plain rows, import() persists the confirmed rows.
 */
class RegistrationImportService
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * Read the first sheet of an .xlsx into rows ready for preview/import.
     * Columns are found by header name (see mapHeaders), so only "Nama" is mandatory.
     * Rows without a name (Google Sheets pads the sheet with blanks) are dropped.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $path, Event $event): array
    {
        $divisions = $event->divisions()->get();
        $rows = [];

        $map = [];
        foreach ($this->readSheet($path) as $i => $cells) {
            if ($i === 0) {
                $map = $this->mapHeaders($cells);
                if (!isset($map['name'])) {
                    throw new RuntimeException('Kolom "Nama" tidak ditemukan di baris header.');
                }
                continue;
            }
            $get = fn (string $key) => isset($map[$key]) ? trim((string) ($cells[$map[$key]] ?? '')) : '';

            $name = $get('name');
            if ($name === '') {
                continue; // Google Sheets pads the sheet with blank rows
            }

            $kategori     = $get('kategori');
            $kategoriUsia = $get('kategori_usia');
            $age          = (int) $get('age');

            $rows[] = [
                'name'        => preg_replace('/\s+/', ' ', $name),
                'age'         => $age ?: null,
                'city'        => $get('city'),
                'club'        => $get('club'),
                'phone'       => $this->normalizePhone($get('phone')),
                'gender'      => $get('gender'),
                'heat'        => $get('heat'),
                'kategori'    => $kategori,
                'kategori_usia' => $kategoriUsia,
                'photo_url'   => $get('photo'),
                'proof_url'   => $get('proof'),
                'submitted_at' => $this->excelDate($get('timestamp') ?: null)?->toDateTimeString(),
                'division_id' => $this->matchDivision($kategori, $kategoriUsia, $divisions)?->id,
                // Name for a division to create when nothing matches (e.g. "Street Veteran (Jam Session)").
                'new_division' => $kategoriUsia !== '' ? $this->newDivisionName($kategori, $kategoriUsia) : null,
            ];
        }

        return $rows;
    }

    /**
     * Persist confirmed rows as PENDING registrations.
     * Rows without a division, or already registered in the same division (same
     * name, case-insensitive), are skipped and reported back. The same name in a
     * different division is a separate entry.
     *
     * Options:
     *  - auto_divisions: create a division for rows whose Kategori Usia matched none
     *  - split_gender:   put Men/Women in separate divisions ("Street Groms Men")
     *  - heat_size:      >0 = fill riders without a heat into heats of ~N (riders with a "Heat" column value keep theirs)
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{auto_divisions?:bool, split_gender?:bool, heat_size?:int}  $options
     * @return array{created:int, divisions_created:int, heats_created:int, skipped:array<int, array{name:string, reason:string}>}
     */
    public function import(Event $event, array $rows, array $options = []): array
    {
        $created = 0;
        $skipped = [];
        $divisionsCreated = 0;
        $touched = [];
        $heatsCreated = 0;
        $heatSize = (int) ($options['heat_size'] ?? 0);
        $photoJobs = [];

        // A rider may enter several divisions, so a duplicate is the same name in the same division.
        $seen = Registration::where('event_id', $event->id)->get(['name', 'division_id'])
            ->mapWithKeys(fn ($r) => [Str::lower(trim($r->name)) . '|' . $r->division_id => true])->all();
        $validDivisions = $event->divisions()->pluck('id')->all();

        DB::transaction(function () use ($event, $rows, $options, $heatSize, &$created, &$skipped, &$seen, $validDivisions, &$divisionsCreated, &$touched, &$heatsCreated, &$photoJobs) {
            foreach ($rows as $row) {
                $splitGender = !empty($options['split_gender']) && in_array($row['gender'] ?? '', ['Men', 'Women'], true);
                $division = null;
                if (!empty($row['division_id']) && in_array((int) $row['division_id'], $validDivisions, true)) {
                    $division = EventDivision::find($row['division_id']);
                } elseif (!empty($options['auto_divisions']) && !empty($row['new_division'])) {
                    // Create the gendered division directly so no empty "base" division is left behind.
                    $name = $row['new_division'] . ($splitGender ? ' ' . $row['gender'] : '');
                    $division = $this->firstOrCreateDivision($event, $name, 'STREET', $this->levelFromName($row['new_division']), null, $divisionsCreated);
                    $splitGender = false;
                }

                if (!$division) {
                    $skipped[] = ['name' => $row['name'], 'reason' => 'divisi belum dipilih'];
                    continue;
                }
                if ($splitGender) {
                    $division = $this->firstOrCreateDivision(
                        $event, $division->name . ' ' . $row['gender'], $division->discipline,
                        $division->level, $division->slots, $divisionsCreated, $division->is_active
                    );
                }
                $key = Str::lower(trim($row['name'])) . '|' . $division->id;
                if (isset($seen[$key])) {
                    $skipped[] = ['name' => $row['name'], 'reason' => 'sudah terdaftar di divisi ' . $division->name];
                    continue;
                }
                $seen[$key] = true;
                $touched[$division->id] = true;

                $groupId = null;
                if (!empty($row['heat'])) {
                    $group = DivisionGroup::firstOrCreate([
                        'event_division_id' => $division->id,
                        'name'              => Str::limit(Str::upper(trim($row['heat'])), 60, ''),
                    ]);
                    $heatsCreated += (int) $group->wasRecentlyCreated;
                    $groupId = $group->id;
                }

                $reg = new Registration([
                    'entry_code'           => $this->entryCode(),
                    'name'                 => $row['name'],
                    'email'                => '',
                    'phone'                => $row['phone'] ?: '-',
                    // The form only asks for age; approximate DOB (1 Jan of birth year).
                    'dob'                  => Carbon::create(($event->date ?? now())->year - ($row['age'] ?? 0), 1, 1)->toDateString(),
                    'city'                 => $row['city'] ?: '-',
                    'event_id'             => $event->id,
                    'division_id'          => $division->id,
                    'division_group_id'    => $groupId,
                    'category'             => $division->discipline ?? $division->name,
                    'competition_category' => $division->level,
                    'ec_name'              => '-',
                    'ec_phone'             => '-',
                    'ec_relation'          => '-',
                    'payment_method'       => 'Transfer',
                    'payment_proof'        => $row['proof_url'] ?: null,
                    'payment_status'       => 'PENDING',
                    'status'               => 'PENDING',
                ]);
                if (!empty($row['submitted_at'])) {
                    $reg->created_at = $row['submitted_at'];
                }
                $reg->save();
                $division->increment('filled');
                $created++;
                if (!empty($row['photo_url'])) {
                    $photoJobs[] = [$reg, $row['photo_url']];
                }
            }

            if ($heatSize > 0) {
                foreach (array_keys($touched) as $divisionId) {
                    $heatsCreated += $this->buildHeats($divisionId, $heatSize);
                }
            }
        });

        // Downloaded after the transaction so slow/failed Drive requests never hold the DB or roll back registrations.
        $photosSaved = 0;
        $photosFailed = [];
        foreach ($photoJobs as [$reg, $url]) {
            if ($this->saveRiderPhoto($reg, $url)) {
                $photosSaved++;
            } else {
                $photosFailed[] = $reg->name;
            }
        }

        return [
            'created' => $created, 'divisions_created' => $divisionsCreated,
            'heats_created' => $heatsCreated, 'skipped' => $skipped,
            'photos_saved' => $photosSaved, 'photos_failed' => $photosFailed,
        ];
    }

    /**
     * Download the photo link (Google Drive "anyone with the link") into the rider's avatar,
     * creating the Rider profile if needed. Returns false when the link isn't a readable image.
     */
    private function saveRiderPhoto(Registration $reg, string $url): bool
    {
        $url = $this->directPhotoUrl($url);
        try {
            $res = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($url);
            $body = $res->successful() ? $res->body() : '';
        } catch (\Throwable) {
            return false;
        }

        // Drive returns an HTML page for private files, so verify it is really an image.
        $info = $body !== '' && strlen($body) <= 5 * 1024 * 1024 ? @getimagesizefromstring($body) : false;
        $ext = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', default => null,
        };
        if (!$ext) {
            return false;
        }

        $rider = Rider::where('name', $reg->name)->first() ?? Rider::create([
            'user_id'  => $reg->user_id,
            'name'     => $reg->name,
            'nick'     => $reg->name,
            'city'     => $reg->city ?? '-',
            'age'      => $reg->dob ? (int) $reg->dob->diffInYears(now()) : 0,
            'category' => in_array($reg->category, ['STREET', 'PARK', 'VERT', 'FLAT', 'MINIRAMP']) ? $reg->category : 'STREET',
            'stance'   => 'Regular',
            'slug'     => Str::slug($reg->name . '-' . $reg->id),
        ]);

        $path = 'avatars/' . Str::random(40) . '.' . $ext;
        Storage::disk('public')->put($path, $body);
        $rider->update(['avatar' => $path]);

        return true;
    }

    /** drive.google.com/open?id=X, /file/d/X/view, /uc?id=X -> direct download URL; other URLs pass through. */
    private function directPhotoUrl(string $url): string
    {
        if (preg_match('~drive\.google\.com/.*?(?:/d/|[?&]id=)([\w-]+)~', $url, $m)) {
            return 'https://drive.google.com/uc?export=download&id=' . $m[1];
        }

        return $url;
    }

    /**
     * Shuffle the division's not-yet-heated riders into evenly sized heats
     * (e.g. 14 riders at size 3 -> 5 heats of 3,3,3,3,2, never a lone rider).
     * New heats continue the existing "HEAT A, HEAT B..." lettering.
     */
    private function buildHeats(int $divisionId, int $size): int
    {
        $regIds = Registration::where('division_id', $divisionId)
            ->whereNull('division_group_id')
            ->where('status', '!=', 'REJECTED')
            ->pluck('id')->shuffle()->values();
        if ($regIds->isEmpty()) {
            return 0;
        }

        $heats = (int) ceil($regIds->count() / $size);
        $offset = DivisionGroup::where('event_division_id', $divisionId)->count();
        $base = intdiv($regIds->count(), $heats);
        $extra = $regIds->count() % $heats;

        $pos = 0;
        for ($h = 0; $h < $heats; $h++) {
            $group = DivisionGroup::create([
                'event_division_id' => $divisionId,
                'name' => 'HEAT ' . $this->letters($offset + $h),
            ]);
            $take = $base + ($h < $extra ? 1 : 0);
            Registration::whereIn('id', $regIds->slice($pos, $take)->all())->update(['division_group_id' => $group->id]);
            $pos += $take;
        }

        return $heats;
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    private function letters(int $i): string
    {
        $out = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $out = chr(65 + ($i - 1) % 26) . $out;
        }

        return $out;
    }

    private function firstOrCreateDivision(Event $event, string $name, ?string $discipline, ?string $level, ?int $slots, int &$createdCount, bool $active = true): EventDivision
    {
        $division = EventDivision::firstOrCreate(
            ['event_id' => $event->id, 'name' => Str::limit($name, 120, '')],
            ['discipline' => $discipline, 'level' => $level, 'slots' => $slots, 'is_active' => $active]
        );
        if ($division->wasRecentlyCreated) {
            $createdCount++;
        }

        return $division;
    }

    /** "Veteran (Jam Session)" + Street -> "Street Veteran (Jam Session)"; "(Men&Women)" suffixes are dropped. */
    private function newDivisionName(string $kategori, string $usia): string
    {
        $clean = trim(preg_replace('/\s*\((men|women|men\s*&\s*women)\)\s*/i', ' ', $usia));
        $prefix = Str::contains(Str::lower($kategori), 'miniramp') ? 'Miniramp' : 'Street';

        return $prefix . ' ' . $clean;
    }

    private function levelFromName(string $name): string
    {
        return trim(preg_replace('/^(Street|Miniramp)\s+/', '', $name));
    }

    /**
     * Map the free-text form answers onto the event's divisions.
     * Form labels are inconsistent ("Junior Begginer U13 (Men&Women)", "Intermediate Open (Men)"),
     * so match on keywords. "Pemula" has no division of its own and is mapped to Groms.
     */
    private function matchDivision(string $kategori, string $usia, $divisions): ?EventDivision
    {
        $u = Str::lower($usia);
        $miniramp = Str::contains(Str::lower($kategori), 'miniramp');

        $level = match (true) {
            Str::contains($u, 'pemula')       => 'Groms',
            Str::contains($u, 'starter')      => 'Starter U10',
            Str::contains($u, 'beg')          => 'Beginner U13', // matches both "Beginner" and the form's "Begginer" typo
            Str::contains($u, 'junior u16')   => 'Junior U16',
            Str::contains($u, 'intermediate') => 'Intermediate',
            Str::contains($u, 'amateur')      => 'Amateur Open',
            default                           => null,        // e.g. Veteran (Jam Session)
        };
        if ($level === null) {
            return null;
        }

        return $divisions->first(fn ($d) => $d->level === $level
            && ($level === 'Junior U16'
                ? (($d->discipline === 'MINIRAMP') === $miniramp)
                : $d->discipline === 'STREET'));
    }

    /**
     * Find columns by header text, so optional columns can be left out and order doesn't matter.
     * Only "Nama" is required; the rest (phone, gender, photo, proof...) are used when present.
     *
     * @param  array<string, string>  $header  column letter => header text
     * @return array<string, string>           field => column letter
     */
    private function mapHeaders(array $header): array
    {
        $rules = [
            'timestamp'     => fn ($h) => str_starts_with($h, 'timestamp'),
            'name'          => fn ($h) => $h === 'nama',
            'age'           => fn ($h) => $h === 'usia',
            'city'          => fn ($h) => str_starts_with($h, 'domisili'),
            'club'          => fn ($h) => str_starts_with($h, 'komunitas'),
            'phone'         => fn ($h) => str_contains($h, 'handphone') || str_contains($h, 'telepon') || $h === 'hp',
            'gender'        => fn ($h) => $h === 'gender',
            'heat'          => fn ($h) => $h === 'heat' || $h === 'group',
            'kategori'      => fn ($h) => $h === 'kategori',
            'kategori_usia' => fn ($h) => $h === 'kategori usia',
            'photo'         => fn ($h) => str_starts_with($h, 'photo') || str_starts_with($h, 'foto'),
            'proof'         => fn ($h) => str_contains($h, 'transfer') || str_contains($h, 'tranfer') || str_contains($h, 'bukti'),
        ];

        $map = [];
        foreach ($header as $letter => $text) {
            $h = Str::lower(trim($text));
            foreach ($rules as $field => $match) {
                if (!isset($map[$field]) && $match($h)) {
                    $map[$field] = $letter;
                    break;
                }
            }
        }

        return $map;
    }

    /** Local Indonesian format: digits only, leading 0. Handles "8.5891486657E10" and "+62". */
    private function normalizePhone(mixed $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?E\+?\d+$/i', $raw)) {
            $raw = number_format((float) $raw, 0, '', '');
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        } elseif ($digits !== '' && $digits[0] !== '0') {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    private function excelDate(mixed $serial): ?Carbon
    {
        if (!is_numeric($serial)) {
            return null;
        }

        return Carbon::create(1899, 12, 30)->addSeconds((int) round(((float) $serial) * 86400));
    }

    private function entryCode(): string
    {
        do {
            $code = 'IB26-' . strtoupper(Str::random(5));
        } while (Registration::where('entry_code', $code)->exists());

        return $code;
    }

    /**
     * Minimal .xlsx reader (zip + XML), so no spreadsheet package is needed.
     * Yields each row of the first worksheet as ['A' => value, 'B' => value, ...].
     *
     * @return array<int, array<string, string>>
     */
    private function readSheet(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File bukan .xlsx yang valid.');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Sheet pertama tidak ditemukan di file.');
        }

        $strings = [];
        if (($ssXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $ss = new SimpleXMLElement($ssXml);
            $ss->registerXPathNamespace('m', self::NS);
            foreach ($ss->xpath('//m:si') as $si) {
                $si->registerXPathNamespace('m', self::NS);
                $strings[] = implode('', array_map('strval', $si->xpath('.//m:t') ?: []));
            }
        }
        $zip->close();

        $sheet = new SimpleXMLElement($sheetXml);
        $sheet->registerXPathNamespace('m', self::NS);

        $rows = [];
        foreach ($sheet->xpath('//m:sheetData/m:row') as $row) {
            $row->registerXPathNamespace('m', self::NS);
            $cells = [];
            foreach ($row->xpath('m:c') as $c) {
                $col = preg_replace('/\d+/', '', (string) $c['r']);
                $type = (string) $c['t'];
                if ($type === 'inlineStr') {
                    $val = (string) $c->is->t;
                } elseif (isset($c->v)) {
                    $val = $type === 's' ? ($strings[(int) $c->v] ?? '') : (string) $c->v;
                } else {
                    continue;
                }
                $cells[$col] = $val;
            }
            $rows[] = $cells;
        }

        return $rows;
    }
}
