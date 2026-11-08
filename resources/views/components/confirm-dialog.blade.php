{{-- Dialog konfirmasi bergaya aplikasi: menggantikan confirm() bawaan browser untuk semua wire:confirm.
     Dipasang sekali di layout, sebelum @livewireScripts. Prompt (wire:confirm.prompt) tetap memakai bawaan browser. --}}
<style>
.ibc-back{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.72);backdrop-filter:blur(3px);animation:ibc-fade .12s ease-out}
.ibc-box{--ibc-c:var(--lime,#c6ff3a);width:min(440px,100%);background:var(--bg-2,#111113);color:var(--ink,#f4f1e8);border:2px solid var(--ibc-c);border-radius:4px;padding:22px 22px 18px;box-shadow:0 24px 70px rgba(0,0,0,.6);animation:ibc-pop .14s ease-out}
.ibc-box.danger{--ibc-c:var(--red,#ff2e22)}
.ibc-kick{display:flex;align-items:center;gap:8px;font:700 11px/1 var(--font-mono,monospace);letter-spacing:.16em;color:var(--ibc-c);margin-bottom:12px}
.ibc-kick i{width:9px;height:9px;border-radius:50%;background:var(--ibc-c)}
.ibc-msg{font-size:17px;line-height:1.45;font-weight:600;white-space:pre-line;margin:0 0 20px;overflow-wrap:anywhere}
.ibc-act{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}
.ibc-act button{font:700 13px/1 var(--font-mono,monospace);letter-spacing:.1em;padding:12px 18px;min-height:44px;border-radius:3px;cursor:pointer;border:2px solid var(--line-strong,rgba(255,255,255,.2));background:transparent;color:var(--ink,#f4f1e8)}
.ibc-act button:hover{border-color:var(--ink,#f4f1e8)}
.ibc-act .ok{background:var(--ibc-c);border-color:var(--ibc-c);color:#0a0a0b}
.ibc-act .ok:hover{filter:brightness(1.1)}
.ibc-act button:focus-visible{outline:3px solid var(--ink,#f4f1e8);outline-offset:2px}
@keyframes ibc-fade{from{opacity:0}to{opacity:1}}
@keyframes ibc-pop{from{opacity:0;transform:translateY(8px) scale(.97)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.ibc-back,.ibc-box{animation:none}}
</style>
<script>
(function () {
    if (window.__ibConfirmInstalled) return;
    window.__ibConfirmInstalled = true;

    // Pesan yang menyangkut penghapusan / pembatalan / reset tampil merah.
    const DANGER = /hapus|tolak|reset|batalkan|tidak bisa dibatalkan|dibatalkan/i;

    window.ibConfirm = function (message) {
        return new Promise(function (resolve) {
            const danger = DANGER.test(message);
            const prev = document.activeElement;
            const back = document.createElement('div');
            back.className = 'ibc-back';
            back.innerHTML =
                '<div class="ibc-box ' + (danger ? 'danger' : '') + '" role="alertdialog" aria-modal="true" aria-labelledby="ibc-msg">' +
                '<div class="ibc-kick"><i></i><span>' + (danger ? 'PERHATIAN' : 'KONFIRMASI') + '</span></div>' +
                '<p class="ibc-msg" id="ibc-msg"></p>' +
                '<div class="ibc-act"><button type="button" class="no">BATAL</button><button type="button" class="ok">' + (danger ? 'YA, LANJUTKAN' : 'YA') + '</button></div>' +
                '</div>';
            back.querySelector('.ibc-msg').textContent = message;   // textContent: aman dari HTML di pesan

            const done = function (ok) {
                document.removeEventListener('keydown', onKey, true);
                back.remove();
                if (prev && prev.focus) { try { prev.focus({ preventScroll: true }); } catch (e) {} }
                resolve(ok);
            };
            const onKey = function (e) {
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); done(false); }
                else if (e.key === 'Tab') {
                    const b = back.querySelectorAll('button');
                    const first = b[0], last = b[b.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            };
            back.querySelector('.no').onclick = function () { done(false); };
            back.querySelector('.ok').onclick = function () { done(true); };
            back.addEventListener('mousedown', function (e) { if (e.target === back) done(false); });
            document.addEventListener('keydown', onKey, true);
            document.body.appendChild(back);
            // Aksi berisiko: fokus awal di BATAL supaya Enter tidak langsung menghapus.
            back.querySelector(danger ? '.no' : '.ok').focus();
        });
    };

    // Livewire memasang el.__livewire_confirm lewat directive bawaan, dan Livewire.directive('confirm')
    // diabaikan karena namanya sudah terdaftar. Jadi properti itu dicegat di prototype: apa pun yang
    // dipasang Livewire diganti pembungkus yang menampilkan dialog ini (pesan dibaca dari atribut wire:confirm).
    Object.defineProperty(HTMLElement.prototype, '__livewire_confirm', {
        configurable: true,
        set: function (orig) { this.__ibConfirmOrig = orig; },
        get: function () {
            const orig = this.__ibConfirmOrig;
            if (!orig) return undefined;
            const el = this;
            return function (action, instead) {
                const raw = el.getAttribute('wire:confirm');
                if (raw === null) return orig(action, instead);   // wire:confirm.prompt → bawaan browser
                const message = raw.replaceAll('\\n', '\n') || 'Yakin?';
                window.ibConfirm(message).then(function (ok) { ok ? action() : instead(); });
            };
        },
    });
})();
</script>
