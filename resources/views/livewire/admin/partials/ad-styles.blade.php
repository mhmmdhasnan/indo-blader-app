{{-- CSS halaman admin (acuan: design system FrameBladeScore — AdminRegistrations / AdminPayments / AdminEvents) --}}
@once
<style>
.ad-lbl{font:700 12px/16px var(--font-mono,monospace);letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3,#a3a39b)}
.ad-tool{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.ad-sp{margin-left:auto}
.ad-tabs{display:flex;flex-wrap:wrap}
.ad-tabs button{min-height:48px;padding:0 16px;background:transparent;border:2px solid var(--ink-3,#8a8a90);margin-left:-2px;color:inherit;font:700 13px var(--font-mono,monospace);letter-spacing:.06em;cursor:pointer;display:flex;align-items:center;gap:8px}
.ad-tabs button b{font:400 22px/1 var(--font-display,'Bebas Neue',sans-serif);color:var(--ink-3,#a3a39b)}
.ad-tabs button.on{background:var(--ink);border-color:var(--ink);color:var(--bg)}.ad-tabs button.on b{color:var(--bg)}
.ad-inp{min-height:48px;padding:0 12px;border:2px solid var(--ink-3,#8a8a90);background:var(--bg-2);color:inherit;font:400 14px var(--font-mono,monospace);min-width:220px}
.ad-btn{min-height:48px;padding:0 16px;border:2px solid var(--ink);background:transparent;color:inherit;font:700 13px var(--font-mono,monospace);letter-spacing:.08em;cursor:pointer;white-space:nowrap}
.ad-btn.pri{background:var(--lime);border-color:var(--lime);color:#0a0a0a}
.ad-btn.dis,.ad-btn:disabled{background:#2b3314;border-color:#2b3314;color:#a3a39b;cursor:not-allowed}
.ad-btn.dng{border-color:#ff6b5e;color:#ff6b5e}
.ad-btn.sm{min-height:44px;padding:0 12px;font-size:12px}
.ad-card{border:2px solid var(--ink);background:var(--bg-1,var(--bg));overflow:hidden}
.ad-scroll{overflow-x:auto}
.ad-gh{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--bg-2);border-bottom:1px solid var(--line);flex-wrap:wrap}
.ad-gh b{font:700 13px var(--font-mono,monospace);letter-spacing:.1em;color:var(--lime)}
.ad-gh span{margin-left:auto}
.ad-tbl{width:100%;border-collapse:collapse;min-width:640px}
.ad-tbl th{font:700 12px var(--font-mono,monospace);letter-spacing:.12em;color:var(--ink-3,#a3a39b);text-align:left;padding:8px 16px;text-transform:uppercase;border-bottom:1px solid var(--line)}
.ad-tbl td{padding:8px 16px;min-height:60px;border-bottom:1px solid var(--line);vertical-align:middle}
.ad-tbl th.c,.ad-tbl td.c{width:44px;padding-right:0}
.ad-tbl td.r{text-align:right}
.ad-nm{display:flex;align-items:center;gap:12px;min-width:0}
.ad-nm b{font:700 16px/20px var(--font-sans,sans-serif);display:block}
.ad-nm small{font:400 12px var(--font-mono,monospace);color:var(--ink-3,#a3a39b);letter-spacing:.04em;text-transform:uppercase}
.ad-chip{display:inline-block;font:700 12px var(--font-mono,monospace);letter-spacing:.08em;padding:2px 10px;border:2px solid var(--ink-3,#8a8a90);color:var(--ink-3,#a3a39b);white-space:nowrap}
.ad-chip.ok{color:var(--lime);border-color:var(--lime)}.ad-chip.wt{color:#ffb020;border-color:#ffb020}.ad-chip.no{color:#ff6b5e;border-color:#ff6b5e}
.ad-check{width:24px;height:24px;accent-color:var(--lime);cursor:pointer}
.ad-more{width:48px;height:48px;border:2px solid var(--ink-3,#8a8a90);background:transparent;color:inherit;font:700 18px var(--font-mono,monospace);cursor:pointer}
.ad-menu{background:var(--bg-2);border:2px solid var(--ink);display:flex;flex-direction:column;box-shadow:0 12px 32px rgba(0,0,0,.5)}
.ad-mi{min-height:48px;padding:0 16px;background:transparent;border:0;border-bottom:1px solid var(--line);color:inherit;text-align:left;font:700 13px var(--font-mono,monospace);letter-spacing:.06em;cursor:pointer;width:100%}
.ad-mi:hover{background:color-mix(in srgb,var(--ink) 10%,transparent)}
.ad-mi.d{color:#ff6b5e}
.ad-ml{padding:8px 16px;font:700 12px var(--font-mono,monospace);letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3,#a3a39b)}
.ad-bulk{position:sticky;bottom:12px;z-index:30;min-height:88px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 16px;background:var(--bg-1,var(--bg));border:2px solid var(--lime)}
.ad-bulk b{font:700 16px var(--font-sans,sans-serif);margin-right:auto}
.ad-sum{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.ad-sum>div{border:2px solid var(--ink);padding:12px 16px;display:flex;flex-direction:column;gap:4px}
.ad-sum .v{font:400 40px/40px var(--font-display,'Bebas Neue',sans-serif);letter-spacing:.02em}
.ad-note{font:400 14px/22px var(--font-sans,sans-serif)}
.ad-err{font-size:12px;color:var(--red)}
.ad-ok{font:700 12px var(--font-mono,monospace);color:var(--lime)}
.ad-body button:focus-visible,.ad-body select:focus-visible,.ad-body input:focus-visible,.ad-menu button:focus-visible{outline:3px solid var(--lime);outline-offset:2px}
</style>
@endonce
