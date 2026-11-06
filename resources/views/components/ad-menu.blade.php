@props(['label' => 'Aksi'])
{{-- Menu ⋯ (design system FrameBladeScore, halaman admin). Panel memakai position:fixed supaya tidak terpotong tabel yang overflow.
     Isi menu = slot: <button class="ad-mi">, <span class="ad-ml"> untuk label bagian, <button class="ad-mi d"> untuk zona berbahaya. --}}
<div x-data="{ open: false, top: 0, left: 0,
        toggle() { const r = this.$refs.btn.getBoundingClientRect(); this.top = Math.max(8, Math.min(r.bottom + 4, window.innerHeight - 380)); this.left = Math.max(8, r.right - 264); this.open = !this.open; } }"
     @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window.passive="open = false"
     style="display:inline-block;">
    <button type="button" x-ref="btn" @click="toggle()" class="ad-more" aria-haspopup="menu" :aria-expanded="open" aria-label="{{ $label }}">⋯</button>
    <div x-show="open" x-cloak :style="`position:fixed;top:${top}px;left:${left}px;width:264px;z-index:400;`" class="ad-menu" role="menu" @click="open = false">
        {{ $slot }}
    </div>
</div>
