{{-- CSS bersama layar Operator & Head Judge (acuan: design system FrameBladeScore) --}}
<style>
.oc{--oc-gap:16px;display:flex;flex-direction:column;min-height:calc(100vh - 150px)}
.oc-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding-bottom:var(--oc-gap)}
.oc-steps{display:flex;margin:0 auto}
.oc-st{height:32px;padding:0 12px;display:flex;align-items:center;font:700 12px var(--font-mono,monospace);letter-spacing:.1em;border:2px solid var(--ink-3,#8a8a90);margin-left:-2px;color:var(--ink-3,#a3a39b)}
.oc-st.done{background:#2b3314;border-color:#2b3314}
.oc-st.on{background:var(--lime);color:#0a0a0a;border-color:var(--lime)}
.oc-main{flex:1;display:grid;grid-template-columns:300px minmax(0,1fr) 340px;gap:var(--oc-gap);align-items:start}
@media (max-width:1100px){.oc-main{grid-template-columns:1fr}}
.oc-card{border:2px solid var(--ink);background:var(--bg-1,var(--bg));padding:16px;display:flex;flex-direction:column;gap:12px;min-width:0}
.oc-lbl{font:700 12px/16px var(--font-mono,monospace);letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3,#a3a39b)}
.oc-sel{min-height:48px;width:100%}
.oc-row{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-2);min-height:48px;border:2px solid transparent;cursor:pointer;width:100%;text-align:left;color:inherit;font:inherit}
.oc-row.on{border-color:var(--lime)}
.oc-row b{flex:1;font-size:15px}
.oc-tag{font:700 12px var(--font-mono,monospace);letter-spacing:.08em;padding:2px 8px;border:2px solid var(--ink-3,#8a8a90);color:var(--ink-3,#a3a39b)}
.oc-tag.go{border-color:var(--lime);color:var(--lime)}
.oc-seg{display:flex}
.oc-seg button{flex:1;min-height:48px;background:transparent;border:2px solid var(--ink-3,#8a8a90);margin-left:-2px;color:inherit;font:700 13px var(--font-mono,monospace);letter-spacing:.08em;cursor:pointer}
.oc-seg button.on{background:var(--ink);color:var(--bg);border-color:var(--ink)}
.oc-seg button:disabled{opacity:.45;cursor:not-allowed}
.oc-title{font-size:36px;line-height:36px;letter-spacing:.02em}
.oc-timer{font-size:var(--oc-timer,96px);line-height:var(--oc-timer-lh,88px);letter-spacing:.02em}
.oc-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;background:var(--bg-2);font-weight:700;font-size:14px}
.oc-ghost{min-height:48px;padding:0 16px;background:transparent;border:2px solid var(--ink);color:inherit;font:700 13px var(--font-mono,monospace);letter-spacing:.1em;cursor:pointer;text-align:left;width:100%}
.oc-bar{position:sticky;bottom:0;z-index:20;margin-top:var(--oc-gap);min-height:88px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border:2px solid var(--ink);background:var(--bg-1,var(--bg))}
.oc-bar p{flex:1;min-width:200px;margin:0}
.oc-btn{min-height:64px;padding:0 32px;border:2px solid var(--ink);background:transparent;color:inherit;font:700 16px var(--font-mono,monospace);letter-spacing:.1em;cursor:pointer;position:relative;overflow:hidden}
.oc-btn.pri{background:var(--lime);border-color:var(--lime);color:#0a0a0a;min-width:280px}
.oc-btn.pri:disabled{background:#2b3314;border-color:#2b3314;color:#a3a39b;cursor:not-allowed}
.oc-btn.sec{min-width:140px;font-size:14px}
.oc-btn.dng{background:#e5251a;border-color:#e5251a;color:#fff;min-width:280px}
.oc-btn.dng .fill{position:absolute;inset:0;background:rgba(0,0,0,.35);transform-origin:left;transform:scaleX(0);pointer-events:none}
.oc button:focus-visible,.oc select:focus-visible,.oc input:focus-visible{outline:3px solid var(--lime);outline-offset:2px}
.oc-err{font-size:12px;color:var(--red)}
.oc-bar .oc-btn.out{border-color:#ff6b5e;color:#ff6b5e;font-size:13px;min-width:200px;margin-right:32px}
.oc-btn .fill{position:absolute;inset:0;background:rgba(255,255,255,.18);transform-origin:left;transform:scaleX(0);pointer-events:none}
.oc-btn.dng .fill{background:rgba(0,0,0,.35)}
.oc-btn.pri.wait{background:#2b3314;border-color:#2b3314;color:#a3a39b;min-width:280px}
.oc-jr{display:grid;grid-template-columns:130px 1fr 60px 110px;align-items:center;gap:12px;padding:8px 12px;background:var(--bg-2);min-height:48px}
.oc-segs{display:flex;gap:4px}.oc-segs i{flex:1;height:12px;border:2px solid var(--ink-3,#8a8a90);min-width:8px}.oc-segs i.f{background:var(--lime);border-color:var(--lime)}
.oc-tg{font:700 12px var(--font-mono,monospace);letter-spacing:.08em;text-align:center;padding:2px 8px;border:2px solid}
.oc-tg.ok{color:var(--lime);border-color:var(--lime)}.oc-tg.wt{color:#ffb020;border-color:#ffb020}
.oc-hj{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:16px;align-items:start;flex:1}
@media (max-width:1100px){.oc-hj{grid-template-columns:1fr}.oc-jr{grid-template-columns:100px 1fr 50px 100px}}
</style>
