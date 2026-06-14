@props(['status'])
@php
$map = [
    'OPEN'    => ['variant' => 'lime', 'label' => 'REG OPEN',     'dot' => false],
    'CLOSING' => ['variant' => 'red',  'label' => 'CLOSING SOON', 'dot' => false],
    'SOON'    => ['variant' => 'out',  'label' => 'COMING SOON',  'dot' => false],
    'FULL'    => ['variant' => 'solid','label' => 'FULL',         'dot' => false],
    'LIVE'    => ['variant' => 'red',  'label' => 'LIVE NOW',     'dot' => true],
];
$m = $map[$status] ?? $map['SOON'];
@endphp
<span class="badge badge-{{ $m['variant'] }}">
    @if($m['dot'])<span class="live-dot"></span>@endif
    {{ $m['label'] }}
</span>
