<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Heat {{ $division->name }} — {{ $division->event?->title }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font: 14px/1.4 system-ui, -apple-system, "Segoe UI", sans-serif; color: #111; background: #fff; }
        .bar { display: flex; gap: 8px; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .bar button, .bar a { font: 600 14px system-ui; padding: 10px 18px; border: 2px solid #111; background: #111; color: #fff; cursor: pointer; text-decoration: none; }
        .bar a { background: #fff; color: #111; }
        header { border-bottom: 3px solid #111; padding-bottom: 8px; margin-bottom: 18px; }
        header h1 { margin: 0; font-size: 22px; letter-spacing: .02em; text-transform: uppercase; }
        header p { margin: 2px 0 0; color: #444; }
        .heat { break-inside: avoid; page-break-inside: avoid; margin-bottom: 26px; }
        .heat h2 { margin: 0 0 6px; font-size: 18px; text-transform: uppercase; display: flex; justify-content: space-between; align-items: baseline; }
        .heat h2 small { font-size: 12px; font-weight: 500; color: #555; text-transform: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 7px 8px; text-align: left; }
        th { background: #eee; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; }
        td.no, th.no { width: 38px; text-align: center; }
        td.sc, th.sc { width: 74px; text-align: center; }
        td.sc { height: 34px; }
        .empty { color: #777; font-style: italic; }
        @media print {
            body { padding: 0; }
            .bar { display: none; }
            @page { margin: 14mm; }
            th { background: #eee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <a href="javascript:history.length > 1 ? history.back() : window.close()">← Kembali</a>
        <button type="button" onclick="window.print()">🖨 Cetak</button>
    </div>

    <header>
        <h1>{{ $division->event?->title }}</h1>
        <p>
            {{ $division->name }} · {{ $stage === 'FINAL' ? 'Final' : 'Kualifikasi' }}
            @if($isJam) · Jamming Season ({{ $division->jam_duration }} dtk per heat) @endif
            @if($isJam && $criteria->isNotEmpty()) · Nilai juri 1–5 per kriteria @endif
        </p>
    </header>

    @foreach($heats as $heat)
        <section class="heat">
            <h2>{{ $heat['name'] }} <small>{{ $heat['riders']->count() }} rider</small></h2>
            @if($heat['riders']->isEmpty())
                <p class="empty">Belum ada rider di heat ini.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th>Nama</th>
                            <th>Kota</th>
                            @if($isJam)
                                @foreach($criteria as $crit)<th class="sc">{{ $crit->name }}</th>@endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($heat['riders'] as $i => $reg)
                            <tr>
                                <td class="no">{{ $i + 1 }}</td>
                                <td><b>{{ $reg->name }}</b></td>
                                <td>{{ $reg->city }}</td>
                                @if($isJam)
                                    @foreach($criteria as $crit)<td class="sc"></td>@endforeach
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach

    <p style="font-size:11px;color:#777;">Dicetak {{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
</body>
</html>
