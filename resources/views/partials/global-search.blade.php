<div id="global-search-overlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; backdrop-filter:blur(4px); align-items:flex-start; justify-content:center; padding-top:80px;"
     onclick="if(event.target===this) closeGlobalSearch()">

    <div style="width:100%; max-width:640px; margin:0 20px; border-radius:20px; background:#0f172a; border:1px solid rgba(99,102,241,0.3); box-shadow:0 30px 80px rgba(0,0,0,0.5); overflow:hidden;">

        {{-- Input --}}
        <div style="display:flex; align-items:center; gap:14px; padding:18px 22px; border-bottom:1px solid rgba(255,255,255,0.06);">
            <i class="fas fa-magnifying-glass" style="color:#818cf8; font-size:1.1rem;"></i>
            <input id="global-search-input"
                   type="text"
                   placeholder="Buscar projetos, editais, doadores, transações..."
                   autocomplete="off"
                   style="flex:1; background:transparent; border:none; outline:none; color:white; font-size:1rem; font-family:inherit; font-weight:500;"
                   oninput="handleSearchInput(this.value)"
                   onkeydown="handleSearchKeydown(event)">
            <kbd style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:3px 8px; font-size:0.65rem; color:rgba(255,255,255,0.4); font-family:monospace;">ESC</kbd>
        </div>

        {{-- Resultados --}}
        <div id="global-search-results" style="max-height:440px; overflow-y:auto; padding:8px;">
            {{-- Estado inicial --}}
            <div id="search-empty-state" style="padding:40px 20px; text-align:center;">
                <i class="fas fa-magnifying-glass" style="font-size:2rem; color:rgba(255,255,255,0.06); display:block; margin-bottom:12px;"></i>
                <p style="color:rgba(255,255,255,0.25); font-size:0.85rem; font-weight:600; margin:0;">Digite ao menos 2 caracteres para buscar</p>
            </div>
        </div>

        {{-- Rodapé --}}
        <div style="padding:10px 18px; border-top:1px solid rgba(255,255,255,0.05); display:flex; gap:16px; align-items:center;">
            <span style="font-size:0.65rem; color:rgba(255,255,255,0.25); font-weight:600;">
                <kbd style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:2px 6px; font-family:monospace;">↑↓</kbd> navegar &nbsp;
                <kbd style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:2px 6px; font-family:monospace;">Enter</kbd> abrir &nbsp;
                <kbd style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:2px 6px; font-family:monospace;">Esc</kbd> fechar
            </span>
        </div>
    </div>
</div>

<style>
#global-search-results::-webkit-scrollbar { width: 4px; }
#global-search-results::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
.gs-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 11px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none;
}
.gs-item:hover, .gs-item.gs-active {
    background: rgba(99,102,241,0.12);
}
.gs-category-label {
    font-size: 0.6rem;
    font-weight: 900;
    color: rgba(255,255,255,0.25);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    padding: 10px 14px 4px;
}
</style>

<script>
let _gsDebounce = null;
let _gsSelected = -1;
let _gsItems = [];

function openGlobalSearch() {
    const overlay = document.getElementById('global-search-overlay');
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('global-search-input').focus(), 50);
    _gsSelected = -1;
}

function closeGlobalSearch() {
    document.getElementById('global-search-overlay').style.display = 'none';
    document.getElementById('global-search-input').value = '';
    resetResults();
}

function resetResults() {
    document.getElementById('global-search-results').innerHTML = `
        <div id="search-empty-state" style="padding:40px 20px; text-align:center;">
            <i class="fas fa-magnifying-glass" style="font-size:2rem; color:rgba(255,255,255,0.06); display:block; margin-bottom:12px;"></i>
            <p style="color:rgba(255,255,255,0.25); font-size:0.85rem; font-weight:600; margin:0;">Digite ao menos 2 caracteres para buscar</p>
        </div>`;
    _gsSelected = -1;
    _gsItems = [];
}

function handleSearchInput(val) {
    clearTimeout(_gsDebounce);
    if (val.length < 2) { resetResults(); return; }
    _gsDebounce = setTimeout(() => doSearch(val), 280);
}

function doSearch(q) {
    const container = document.getElementById('global-search-results');
    container.innerHTML = `<div style="padding:30px; text-align:center; color:rgba(255,255,255,0.2); font-size:0.85rem;"><i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Buscando...</div>`;

    fetch(`/search?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => renderResults(data, q))
    .catch(() => {
        container.innerHTML = `<div style="padding:30px; text-align:center; color:rgba(239,68,68,0.7); font-size:0.85rem;">Erro ao buscar. Tente novamente.</div>`;
    });
}

function renderResults(data, q) {
    const container = document.getElementById('global-search-results');
    _gsItems = [];
    _gsSelected = -1;

    if (!data.length) {
        container.innerHTML = `
            <div style="padding:40px 20px; text-align:center;">
                <i class="fas fa-face-frown-open" style="font-size:2rem; color:rgba(255,255,255,0.06); display:block; margin-bottom:12px;"></i>
                <p style="color:rgba(255,255,255,0.25); font-size:0.85rem; font-weight:600; margin:0;">Nenhum resultado para "<em>${q}</em>"</p>
            </div>`;
        return;
    }

    // Group by category
    const grouped = {};
    data.forEach(item => {
        if (!grouped[item.category]) grouped[item.category] = [];
        grouped[item.category].push(item);
    });

    let html = '';
    let idx = 0;
    for (const [cat, items] of Object.entries(grouped)) {
        html += `<div class="gs-category-label">${cat}</div>`;
        items.forEach(item => {
            html += `
            <a href="${item.url}" class="gs-item" data-idx="${idx}"
               onmouseenter="setActive(${idx})"
               onclick="closeGlobalSearch()">
                <div style="width:34px; height:34px; background:rgba(255,255,255,0.05); border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas ${item.icon}" style="color:${item.color}; font-size:0.85rem;"></i>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-size:0.88rem; font-weight:700; color:white; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.label}</div>
                    <div style="font-size:0.72rem; color:rgba(255,255,255,0.35); font-weight:600;">${item.sub}</div>
                </div>
                <i class="fas fa-arrow-right" style="color:rgba(255,255,255,0.15); font-size:0.7rem;"></i>
            </a>`;
            _gsItems.push(item);
            idx++;
        });
    }

    container.innerHTML = html;
}

function setActive(idx) {
    _gsSelected = idx;
    document.querySelectorAll('.gs-item').forEach((el, i) => {
        el.classList.toggle('gs-active', i === idx);
    });
}

function handleSearchKeydown(e) {
    const total = _gsItems.length;
    if (e.key === 'Escape') { closeGlobalSearch(); return; }
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(_gsSelected + 1, total - 1)); }
    if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(_gsSelected - 1, 0)); }
    if (e.key === 'Enter' && _gsSelected >= 0 && _gsItems[_gsSelected]) {
        closeGlobalSearch();
        window.location.href = _gsItems[_gsSelected].url;
    }
}

// Ativar com Ctrl+K / Cmd+K
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openGlobalSearch();
    }
});
</script>
