/* js/machines.js — Cívica Equipamentos
   Todas as operações usam a API PHP/MySQL em vez de localStorage. */

const API = 'api/machines.php';

// ── Public: load all machines (used by catalog + homepage) ──────────
async function loadPublicMachines() {
    try {
        const res = await fetch(API + '?v=' + Date.now());
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.json();
    } catch (e) {
        console.error('Erro ao carregar equipamentos:', e);
        return [];
    }
}

// ── Admin: get all machines ─────────────────────────────────────────
async function getMachines() {
    return await loadPublicMachines();
}

// ── Admin: add machine ──────────────────────────────────────────────
async function addMachine(data) {
    const res = await fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Erro ao adicionar');
    return json;
}

// ── Admin: update machine ───────────────────────────────────────────
async function updateMachine(id, data) {
    const res = await fetch(API + '?id=' + encodeURIComponent(id), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Erro ao atualizar');
    return json;
}

// ── Admin: delete machine ───────────────────────────────────────────
async function deleteMachine(id) {
    const res = await fetch(API + '?id=' + encodeURIComponent(id), {
        method: 'DELETE',
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || 'Erro ao eliminar');
    return json;
}

// ── Helpers ─────────────────────────────────────────────────────────
function typeLabel(t) { return { aluguer: 'Aluguer', venda: 'Venda', ambos: 'Aluguer & Venda' }[t] || t; }
function typeClass(t) { return { aluguer: 'badge-rent', venda: 'badge-sale', ambos: 'badge-rent' }[t] || ''; }
function priceLabel(u) { return { dia: '/ dia', semana: '/ semana', mes: '/ mês', total: '' }[u] || ''; }
function priceFormat(p) {
    return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(p);
}
function categoryIcon(cat) {
    return { 'Escavadoras': 'shovel', 'Plataformas Elevatórias': 'arrow-up-from-dot', 'Compactadores': 'circle-dashed', 'Geradores': 'zap', 'Empilhadores': 'forklift', 'Mini-Retroescavadoras': 'tractor', 'Betoneiras': 'rotate-cw', 'Outros': 'wrench' }[cat] || 'package';
}
function sanitize(s) {
    if (typeof s !== 'string') return '';
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(s));
    return d.innerHTML;
}

const CATEGORIES = ['Escavadoras','Plataformas Elevatórias','Compactadores','Geradores','Empilhadores','Mini-Retroescavadoras','Betoneiras','Outros'];
