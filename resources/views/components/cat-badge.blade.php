@props(['cat', 'sm' => false])
@php
$variants = ['STREET' => 'lime', 'PARK' => 'solid', 'VERT' => 'red', 'FLAT' => 'out', 'MINIRAMP' => 'solid'];
$labels   = ['STREET' => 'Street', 'PARK' => 'Park', 'VERT' => 'Vert', 'FLAT' => 'Flatland', 'MINIRAMP' => 'Miniramp'];
$v = $variants[$cat] ?? 'out';
$l = $labels[$cat] ?? $cat;
@endphp
<span class="badge badge-{{ $v }}" style="{{ $sm ? 'font-size:9px;' : '' }}">{{ $l }}</span>
