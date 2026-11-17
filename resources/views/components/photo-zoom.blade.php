{{-- Modal galeri foto rider. Elemen mana pun dengan data-zoom-src (lihat <x-avatar zoom>) membukanya;
     prev/next berjalan di semua foto yang tampil di halaman, urut sesuai DOM. --}}
<div x-data="{
        items: [], i: 0, touchX: null,
        get open() { return this.items.length > 0 },
        get cur() { return this.items[this.i] || {} },
        show(el) {
            this.items = [...document.querySelectorAll('[data-zoom-src]')].map(e => ({ src: e.dataset.zoomSrc, name: e.dataset.zoomName || '' }));
            this.i = Math.max(0, this.items.findIndex(x => x.src === el.dataset.zoomSrc));
            document.body.style.overflow = 'hidden';
        },
        close() { this.items = []; document.body.style.overflow = ''; },
        go(d) { if (this.items.length > 1) this.i = (this.i + d + this.items.length) % this.items.length },
     }"
     x-on:click.window="const el = $event.target.closest('[data-zoom-src]'); if (el) { $event.preventDefault(); show(el) }"
     x-on:keydown.window="if (!open) return; if ($event.key === 'Escape') close(); else if ($event.key === 'ArrowRight') go(1); else if ($event.key === 'ArrowLeft') go(-1)"
     x-on:click.self="close()"
     :style="{ display: open ? 'flex' : 'none' }"
     style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,0.88);align-items:center;justify-content:center;padding:16px;"
     role="dialog" aria-modal="true" aria-label="Foto rider">
    <div style="position:relative;width:min(94vw,520px);max-height:94vh;display:flex;flex-direction:column;background:#0d0d0f;border:2px solid #fff;"
         x-on:touchstart.passive="touchX = $event.changedTouches[0].clientX"
         x-on:touchend.passive="if (touchX !== null) { const dx = $event.changedTouches[0].clientX - touchX; if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1); touchX = null }">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;border-bottom:2px solid #fff;">
            <span x-text="cur.name" class="mono" style="color:#fff;font-size:13px;letter-spacing:0.08em;text-transform:uppercase;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
            <button type="button" x-on:click="close()" aria-label="Tutup" style="flex:none;width:32px;height:32px;border:2px solid #fff;background:transparent;color:#fff;font-size:16px;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <div style="position:relative;flex:1;min-height:0;display:flex;align-items:center;justify-content:center;background:#000;">
            <img :src="cur.src" :alt="cur.name" style="max-width:100%;max-height:76vh;object-fit:contain;display:block;">
            <button type="button" x-show="items.length > 1" x-on:click="go(-1)" aria-label="Sebelumnya"
                    style="position:absolute;left:8px;top:50%;transform:translateY(-50%);width:40px;height:40px;border:2px solid #fff;background:rgba(0,0,0,0.6);color:#fff;font-size:20px;cursor:pointer;">&lsaquo;</button>
            <button type="button" x-show="items.length > 1" x-on:click="go(1)" aria-label="Berikutnya"
                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);width:40px;height:40px;border:2px solid #fff;background:rgba(0,0,0,0.6);color:#fff;font-size:20px;cursor:pointer;">&rsaquo;</button>
        </div>
        <div x-show="items.length > 1" class="mono" style="padding:8px 14px;border-top:2px solid #fff;color:#aaa;font-size:11px;text-align:center;letter-spacing:0.1em;">
            <span x-text="(i + 1) + ' / ' + items.length"></span>
        </div>
    </div>
</div>
