<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulator — FRAMEBLADESCORE</title>
    <style>
        :root { --bg:#0e0f13; --panel:#171922; --line:#2a2d3a; --fg:#e8e9ee; --mut:#8b8fa3; --acc:#ff3d3d; }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; background: var(--bg); color: var(--fg); font: 13px system-ui, sans-serif; }
        body { display: flex; flex-direction: column; }
        header { display: flex; gap: 10px; align-items: center; padding: 8px 12px; border-bottom: 1px solid var(--line); flex-wrap: wrap; }
        header b { letter-spacing: .08em; }
        button, select { background: var(--panel); color: var(--fg); border: 1px solid var(--line); border-radius: 6px; padding: 5px 9px; font: inherit; cursor: pointer; }
        button:hover { border-color: var(--acc); }
        #grid { flex: 1; min-height: 0; display: grid; gap: 6px; padding: 6px; grid-template-columns: repeat(var(--cols,3), 1fr); grid-auto-rows: 1fr; }
        .pane { display: flex; flex-direction: column; min-height: 0; min-width: 0; background: var(--panel); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .pane.max { position: fixed; inset: 46px 6px 6px; z-index: 10; }
        .bar { display: flex; gap: 4px; padding: 4px; border-bottom: 1px solid var(--line); align-items: center; }
        .bar select { flex: 1; min-width: 0; padding: 3px 5px; }
        .bar button { padding: 3px 7px; }
        iframe { flex: 1; width: 100%; border: 0; background: #000; }
        .hint { color: var(--mut); }
    </style>
</head>
<body>
<header>
    <b>SIMULATOR</b>
    <button id="add">+ Panel</button>
    <button id="reloadAll">↻ Reload semua</button>
    <label>Kolom
        <select id="cols"><option>1</option><option>2</option><option selected>3</option><option>4</option></select>
    </label>
    <button id="reset">Reset layout</button>
    <span class="hint">Tiap panel punya sesi login sendiri. Data yang diubah = data asli di database.</span>
    <a href="{{ route('admin') }}" style="color:var(--mut);margin-left:auto">← Admin</a>
</header>
<div id="grid"></div>

<script>
const USERS = @json($users);
const PAGES = @json($pages);
const DEFAULTS = @json($defaults);
const LINK = @json(route('simulator.link'));
const KEY = 'ib-simulator-v1';

let state;
try { state = JSON.parse(localStorage.getItem(KEY)); } catch (e) {}
if (!state || !Array.isArray(state.panes)) state = { cols: 3, panes: DEFAULTS.map(p => ({ ...p })) };

const grid = document.getElementById('grid');
const save = () => { try { localStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {} };
const slotOf = (p, i) => p.user ? 'u' + p.user : 'g' + i;

async function load(frame, p, i) {
    const slot = slotOf(p, i);
    const url = new URL(LINK, location.origin);
    url.search = new URLSearchParams({ slot, user: p.user, page: p.page });
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) { frame.srcdoc = '<body style="color:#f66;font:14px sans-serif">Gagal membuat link (' + res.status + ')</body>'; return; }
    let target = (await res.json()).url;
    if (!p.user) target += (target.includes('?') ? '&' : '?') + 'sim=' + slot;
    frame.src = target;
}

function opt(v, label, sel) { return `<option value="${v}" ${sel ? 'selected' : ''}>${label}</option>`; }

function render() {
    grid.style.setProperty('--cols', state.cols);
    document.getElementById('cols').value = state.cols;
    grid.innerHTML = '';
    state.panes.forEach((p, i) => {
        const el = document.createElement('div');
        el.className = 'pane';
        const roles = [...new Set(USERS.map(u => u.role))];
        el.innerHTML = `
            <div class="bar">
                <select data-k="user">
                    ${opt(0, '(Tamu / tidak login)', !p.user)}
                    ${roles.map(r => `<optgroup label="${r}">${USERS.filter(u => u.role === r).map(u => opt(u.id, `${u.name} (${u.username})`, u.id == p.user)).join('')}</optgroup>`).join('')}
                </select>
                <select data-k="page">${Object.entries(PAGES).map(([v, l]) => opt(v, l, v === p.page)).join('')}</select>
                <button data-a="reload" title="Reload">↻</button>
                <button data-a="max" title="Perbesar">⤢</button>
                <button data-a="del" title="Hapus">✕</button>
            </div>
            <iframe></iframe>`;
        const frame = el.querySelector('iframe');
        el.querySelectorAll('select').forEach(s => s.onchange = () => {
            p[s.dataset.k] = s.dataset.k === 'user' ? Number(s.value) : s.value;
            save(); load(frame, p, i);
        });
        el.querySelector('[data-a=reload]').onclick = () => load(frame, p, i);
        el.querySelector('[data-a=max]').onclick = () => el.classList.toggle('max');
        el.querySelector('[data-a=del]').onclick = () => { state.panes.splice(i, 1); save(); render(); };
        grid.appendChild(el);
        load(frame, p, i);
    });
}

document.getElementById('add').onclick = () => { state.panes.push({ user: 0, page: '/live' }); save(); render(); };
document.getElementById('reloadAll').onclick = () => render();
document.getElementById('cols').onchange = e => { state.cols = Number(e.target.value); save(); grid.style.setProperty('--cols', state.cols); };
document.getElementById('reset').onclick = () => { localStorage.removeItem(KEY); location.reload(); };
render();
</script>
</body>
</html>
