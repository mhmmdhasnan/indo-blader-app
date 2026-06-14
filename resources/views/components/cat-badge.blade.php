@props(['cat', 'sm' => false])
@php
$variants = ['STREET' => 'lime', 'PARK' => 'solid', 'VERT' => 'red', 'FLAT' => 'out'];
$labels   = ['STREET' => 'Street', 'PARK' => 'Park', 'VERT' => 'Vert', 'FLAT' => 'Flatland'];
$v = $variants[$cat] ?? 'out';
$l = $labels[$cat] ?? $cat;
@endphp
<span class="badge badge-{{ $v }}" style="{{ $sm ? 'font-size:9px;' : '' }}">{{ $l }}</span>
