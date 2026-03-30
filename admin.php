<?php
/**
 * CÍVICA EQUIPAMENTOS — Painel de Administração
 * Autenticação por sessão PHP (servidor, não JavaScript)
 */
session_start();
require_once __DIR__ . '/api/config.php';

// ── Logout ────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Login ─────────────────────────────────────────────────────
$loginError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['civica_admin'] = true;
        $_SESSION['civica_admin_time'] = time();
        header('Location: admin.php');
        exit;
    }
    $loginError = true;
    // Small delay to slow brute-force
    sleep(1);
}

// ── Session timeout (8h) ──────────────────────────────────────
if (isset($_SESSION['civica_admin_time']) && time() - $_SESSION['civica_admin_time'] > 28800) {
    session_destroy();
    header('Location: admin.php?timeout=1');
    exit;
}

$loggedIn = isset($_SESSION['civica_admin']) && $_SESSION['civica_admin'] === true;
?>
<!DOCTYPE html>
<html lang="pt-PT" <?= $loggedIn ? '' : '' ?>>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Admin — Cívica Equipamentos</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#F5C000">
  <link rel="icon" type="image/png" href="images/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap">
  <link rel="stylesheet" href="css/style.css">
  <script>(function(){const t=localStorage.getItem('civica_theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ══════════ LOGIN SCREEN ══════════ -->
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:var(--bg)">
  <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:44px;width:100%;max-width:380px">
    <img src="images/logo.png" alt="Cívica" style="height:32px;margin-bottom:32px">
    <div style="font-family:'Barlow Condensed',sans-serif;font-size:26px;font-weight:800;text-transform:uppercase;margin-bottom:4px">Painel Admin</div>
    <div style="font-size:14px;color:var(--muted);margin-bottom:28px">Acesso reservado à equipa Cívica.</div>

    <?php if ($loginError): ?>
    <div style="background:rgba(255,60,60,0.1);border:1px solid rgba(255,60,60,0.3);border-radius:5px;color:#f87171;font-size:13px;padding:10px 14px;margin-bottom:18px">
      Senha incorreta. Tente novamente.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['timeout'])): ?>
    <div style="background:rgba(245,192,0,0.1);border:1px solid rgba(245,192,0,0.3);border-radius:5px;color:var(--yellow);font-size:13px;padding:10px 14px;margin-bottom:18px">
      Sessão expirada. Faça login novamente.
    </div>
    <?php endif; ?>

    <form method="POST" action="admin.php">
      <div class="form-group">
        <label class="form-label" for="password">Senha de Acesso</label>
        <input type="password" id="password" name="password" class="form-input"
          placeholder="••••••••" required autocomplete="current-password" autofocus>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
        Entrar no Painel
      </button>
    </form>
    <div style="text-align:center;margin-top:20px">
      <a href="index.html" style="font-size:13px;color:var(--muted)">← Voltar ao site</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════ ADMIN PANEL ══════════ -->
<div class="admin-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <img src="images/logo.png" alt="Cívica Equipamentos">
    </div>
    <nav class="sidebar-nav">
      <div class="sb-item active" id="tabDash"><i data-lucide="layout-dashboard"></i> Dashboard</div>
      <div class="sb-item" id="tabMachines"><i data-lucide="package"></i> Equipamentos</div>
      <div class="sb-item" id="tabExport"><i data-lucide="database"></i> Base de Dados</div>
      <div class="sb-item" id="tabSite"><i data-lucide="globe"></i> Ver Site</div>
    </nav>
    <div class="sidebar-bottom">
      <button class="sb-item theme-toggle" style="width:100%;border:none;border-radius:5px" title="Mudar tema">
        <i data-lucide="sun"></i> <span>Mudar Tema</span>
      </button>
      <a href="admin.php?logout=1" class="sb-item" style="color:var(--muted)">
        <i data-lucide="log-out"></i> Sair
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">

    <!-- DASHBOARD -->
    <div id="pageDash">
      <div class="admin-topbar">
        <h1 class="admin-title">Dashboard</h1>
        <button class="btn btn-primary" onclick="openAdd()">
          <i data-lucide="plus"></i> Adicionar Equipamento
        </button>
      </div>
      <div class="stat-row" id="dashStats">
        <div class="stat-box"><div class="stat-box-label">Total</div><div class="stat-box-num" id="statTotal">—</div></div>
        <div class="stat-box"><div class="stat-box-label">Aluguer</div><div class="stat-box-num" id="statRent">—</div></div>
        <div class="stat-box"><div class="stat-box-label">Venda</div><div class="stat-box-num" id="statSale">—</div></div>
        <div class="stat-box"><div class="stat-box-label">Disponíveis</div><div class="stat-box-num" id="statAvail" style="color:#4ade80">—</div></div>
      </div>
      <p style="font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">Adicionados recentemente</p>
      <div class="atbl" id="recentTbl"><div class="loading"><i data-lucide="loader"></i> A carregar...</div></div>
    </div>

    <!-- MACHINES -->
    <div id="pageMachines" style="display:none">
      <div class="admin-topbar">
        <h1 class="admin-title">Equipamentos</h1>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <div class="search-wrap">
            <i data-lucide="search"></i>
            <input type="search" class="search-input" id="adminSearch" placeholder="Pesquisar..." style="width:200px">
          </div>
          <button class="btn btn-primary" onclick="openAdd()"><i data-lucide="plus"></i> Adicionar</button>
        </div>
      </div>
      <div class="atbl" id="machineTbl"><div class="loading"><i data-lucide="loader"></i> A carregar...</div></div>
    </div>

    <!-- DB INFO -->
    <div id="pageExport" style="display:none">
      <div class="admin-topbar"><h1 class="admin-title">Base de Dados</h1></div>
      <div class="info-box">
        <h4><i data-lucide="database"></i> Dados na Base de Dados MySQL</h4>
        <div class="step"><div class="step-num">✓</div>
          <div class="step-text">Os equipamentos estão guardados na sua base de dados MySQL (phpMyAdmin). Qualquer alteração aqui aparece <strong>imediatamente no site</strong> — sem necessidade de exportar ou fazer upload de ficheiros.</div>
        </div>
        <div class="step"><div class="step-num">1</div>
          <div class="step-text"><strong>Fotos dos equipamentos:</strong> carregue para <code>images/maquinas/</code> via cPanel e use o caminho <code>images/maquinas/nome.jpg</code> no campo Imagem.</div>
        </div>
        <div class="step" style="margin-bottom:0"><div class="step-num">2</div>
          <div class="step-text"><strong>Ver dados brutos:</strong> aceda ao <a href="#" style="color:var(--yellow)">phpMyAdmin</a> no cPanel → tabela <code>civica_machines</code>.</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="export-grid">
        <div class="atbl" style="padding:24px">
          <p style="font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:700;text-transform:uppercase;margin-bottom:8px">Exportar JSON</p>
          <p style="font-size:14px;color:var(--muted);margin-bottom:16px">Descarregue um ficheiro JSON com todos os equipamentos (para backup ou migração).</p>
          <button class="btn btn-primary" onclick="doExport()" style="width:100%;justify-content:center"><i data-lucide="download"></i> Exportar JSON</button>
        </div>
        <div class="atbl" style="padding:24px">
          <p style="font-family:'Barlow Condensed',sans-serif;font-size:18px;font-weight:700;text-transform:uppercase;margin-bottom:8px">Importar JSON</p>
          <p style="font-size:14px;color:var(--muted);margin-bottom:16px">Insira equipamentos a partir de um ficheiro JSON (adiciona aos existentes).</p>
          <label class="btn btn-outline" style="width:100%;justify-content:center;cursor:pointer">
            <i data-lucide="upload"></i> Selecionar ficheiro
            <input type="file" accept=".json" id="importFile" style="display:none">
          </label>
        </div>
      </div>
      <div class="danger-box">
        <h4><i data-lucide="alert-triangle"></i> Zona de Perigo</h4>
        <p>Isto elimina todos os equipamentos da base de dados e insere os exemplos. Ação irreversível.</p>
        <button class="btn-danger" onclick="resetToSample()"><i data-lucide="rotate-ccw"></i> Restaurar exemplos</button>
      </div>
    </div>

  </main>
</div>

<!-- FORM MODAL -->
<div class="form-overlay" id="formModal">
  <div class="form-modal">
    <div class="form-modal-title" id="formTitle">Adicionar Equipamento</div>
    <input type="hidden" id="editId">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Nome *</label>
        <input id="fName" type="text" class="form-input" placeholder="Ex: Escavadora CAT 320" maxlength="200">
      </div>
      <div class="form-group">
        <label class="form-label">Categoria *</label>
        <select id="fCat" class="form-input">
          <option value="">Selecione...</option>
          <?php
          $cats = ['Escavadoras','Plataformas Elevatórias','Compactadores','Geradores','Empilhadores','Mini-Retroescavadoras','Betoneiras','Outros'];
          foreach ($cats as $c) echo "<option value=\"$c\">$c</option>";
          ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select id="fType" class="form-input">
          <option value="aluguer">Aluguer</option>
          <option value="venda">Venda</option>
          <option value="ambos">Aluguer &amp; Venda</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Disponível</label>
        <select id="fAvail" class="form-input">
          <option value="true">Disponível</option>
          <option value="false">Indisponível</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Preço (€) *</label>
        <input id="fPrice" type="number" class="form-input" placeholder="0" min="0" step="0.01">
      </div>
      <div class="form-group">
        <label class="form-label">Unidade</label>
        <select id="fUnit" class="form-input">
          <option value="dia">Por dia</option>
          <option value="semana">Por semana</option>
          <option value="mes">Por mês</option>
          <option value="total">Preço total</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Descrição *</label>
      <textarea id="fDesc" class="form-input" rows="3" placeholder="Descreva o equipamento..." maxlength="2000"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">URL da Imagem</label>
      <input id="fImg" type="text" class="form-input" placeholder="https://... ou images/maquinas/foto.jpg" oninput="previewImg(this.value)">
      <div style="font-size:12px;color:var(--muted);margin-top:5px">Carregue a foto via cPanel para <code>images/maquinas/</code> e use: <code>images/maquinas/nome.jpg</code></div>
      <div class="img-preview" id="imgPreview">
        <div class="img-preview-empty"><i data-lucide="image"></i><span>Pré-visualização</span></div>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Especificações técnicas <span style="color:var(--muted);font-weight:400;text-transform:none;letter-spacing:0">(opcional)</span></label>
      <div class="specs-list" id="specsList"></div>
      <button type="button" onclick="addSpec()" class="btn btn-ghost" style="width:100%;justify-content:center;margin-top:8px;font-size:12px">
        <i data-lucide="plus"></i> Adicionar especificação
      </button>
    </div>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
      <input type="checkbox" id="fFeat" style="width:16px;height:16px;accent-color:var(--yellow)">
      <label for="fFeat" style="font-size:14px;cursor:pointer">Mostrar em destaque na página inicial</label>
    </div>
    <div class="form-footer">
      <button type="button" class="btn btn-ghost" onclick="closeForm()">Cancelar</button>
      <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveMachine()">
        <i data-lucide="save"></i> Guardar
      </button>
    </div>
  </div>
</div>

<!-- CONFIRM DELETE -->
<div class="form-overlay" id="confirmModal">
  <div class="form-modal" style="max-width:380px">
    <div class="form-modal-title" style="font-size:18px">Eliminar equipamento?</div>
    <p style="color:var(--muted);font-size:14px;margin-bottom:24px">Tem a certeza que quer eliminar <strong id="delName" style="color:var(--white)"></strong>? Esta ação não pode ser desfeita.</p>
    <div class="form-footer">
      <button class="btn btn-ghost" onclick="closeConfirm()">Cancelar</button>
      <button class="btn-danger" id="confirmDelBtn"><i data-lucide="trash-2"></i> Eliminar</button>
    </div>
  </div>
</div>

<?php endif; // logged in ?>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js"></script>
<script src="js/machines.js"></script>
<script src="js/form-security.js"></script>
<script src="js/main.js"></script>

<?php if ($loggedIn): ?>
<script>
lucide.createIcons();

let pendingDelId = null;

// ── Navigation ────────────────────────────────────────────────
const pages = { dash: 'pageDash', machines: 'pageMachines', export: 'pageExport' };
function showPage(name) {
  Object.values(pages).forEach(id => document.getElementById(id).style.display = 'none');
  document.getElementById(pages[name]).style.display = 'block';
  document.querySelectorAll('.sb-item').forEach(el => el.classList.remove('active'));
  const tab = document.getElementById('tab' + name.charAt(0).toUpperCase() + name.slice(1));
  if (tab) tab.classList.add('active');
  if (name === 'dash')     loadDash();
  if (name === 'machines') loadTable();
}
document.getElementById('tabDash').addEventListener('click',     () => showPage('dash'));
document.getElementById('tabMachines').addEventListener('click', () => showPage('machines'));
document.getElementById('tabExport').addEventListener('click',   () => showPage('export'));
document.getElementById('tabSite').addEventListener('click',     () => window.open('index.html', '_blank'));

// ── Dashboard ─────────────────────────────────────────────────
async function loadDash() {
  const machines = await getMachines();
  document.getElementById('statTotal').textContent = machines.length;
  document.getElementById('statRent').textContent  = machines.filter(m => m.type === 'aluguer' || m.type === 'ambos').length;
  document.getElementById('statSale').textContent  = machines.filter(m => m.type === 'venda'   || m.type === 'ambos').length;
  document.getElementById('statAvail').textContent = machines.filter(m => m.available !== false).length;
  const recent = [...machines].sort((a,b) => (b.createdAt||0)-(a.createdAt||0)).slice(0, 5);
  renderTableIn('recentTbl', recent);
}

// ── Machine table ─────────────────────────────────────────────
let allCached = [];
async function loadTable(filter = '') {
  if (!allCached.length) allCached = await getMachines();
  let ms = allCached;
  if (filter) { const q = filter.toLowerCase(); ms = ms.filter(m => m.name.toLowerCase().includes(q) || m.category.toLowerCase().includes(q)); }
  renderTableIn('machineTbl', ms);
}
function renderTableIn(id, ms) {
  const wrap = document.getElementById(id);
  if (!ms.length) {
    wrap.innerHTML = `<div style="text-align:center;padding:40px;color:var(--muted)">Nenhum equipamento. <button onclick="openAdd()" class="btn btn-primary" style="margin-left:12px"><i data-lucide="plus"></i> Adicionar</button></div>`;
    lucide.createIcons({nodes:[wrap]}); return;
  }
  wrap.innerHTML = `<table><thead><tr>
    <th>Imagem</th><th>Nome</th><th>Categoria</th><th>Tipo</th><th>Preço</th><th>Estado</th><th>Ações</th>
  </tr></thead><tbody>${ms.map(m => `<tr>
    <td>${m.image ? `<img class="atbl-thumb" src="${m.image}" alt="" loading="lazy">` : '<i data-lucide="image" style="opacity:.3;width:32px;height:32px"></i>'}</td>
    <td><strong>${sanitize(m.name)}</strong></td>
    <td style="color:var(--muted)">${sanitize(m.category)}</td>
    <td><span class="badge ${typeClass(m.type)}">${typeLabel(m.type)}</span></td>
    <td style="font-family:'Barlow Condensed',sans-serif;font-size:17px">${priceFormat(m.price)}<span style="font-size:12px;color:var(--muted)"> ${priceLabel(m.priceUnit)}</span></td>
    <td style="font-size:12px;color:${m.available!==false?'#4ade80':'#f87171'}">${m.available!==false?'Disponível':'Indisponível'}</td>
    <td><div class="tbl-actions">
      <button class="btn-icon btn-edit" onclick="openEdit('${m.id}')" title="Editar"><i data-lucide="pencil"></i></button>
      <button class="btn-icon btn-del"  onclick="openConfirm('${m.id}','${m.name.replace(/'/g,"\\'")}')" title="Eliminar"><i data-lucide="trash-2"></i></button>
    </div></td>
  </tr>`).join('')}</tbody></table>`;
  lucide.createIcons({nodes:[wrap]});
}
document.getElementById('adminSearch').addEventListener('input', e => loadTable(e.target.value));

// ── Specs ─────────────────────────────────────────────────────
function addSpec(label = '', value = '') {
  const list = document.getElementById('specsList');
  const row  = document.createElement('div');
  row.className = 'spec-row';
  row.innerHTML = `<input type="text" class="form-input spec-lbl" placeholder="Ex: Peso" value="${label}"><input type="text" class="form-input spec-val" placeholder="Ex: 21 t" value="${value}"><button type="button" onclick="this.parentNode.remove()"><i data-lucide="x"></i></button>`;
  list.appendChild(row);
  lucide.createIcons({nodes:[row]});
}

// ── Image preview ─────────────────────────────────────────────
function previewImg(url) {
  const p = document.getElementById('imgPreview');
  p.innerHTML = url
    ? `<img src="${url}" alt="preview" onerror="this.parentNode.innerHTML='<div class=img-preview-empty><i data-lucide=image-off></i><span>Não encontrada</span></div>';lucide.createIcons();">`
    : '<div class="img-preview-empty"><i data-lucide="image"></i><span>Pré-visualização</span></div>';
  lucide.createIcons({nodes:[p]});
}

// ── Form open/close ───────────────────────────────────────────
function openAdd() {
  document.getElementById('formTitle').textContent = 'Adicionar Equipamento';
  document.getElementById('editId').value = '';
  ['fName','fDesc','fImg'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('fCat').value = '';
  document.getElementById('fType').value = 'aluguer';
  document.getElementById('fAvail').value = 'true';
  document.getElementById('fPrice').value = '';
  document.getElementById('fUnit').value = 'dia';
  document.getElementById('fFeat').checked = false;
  document.getElementById('specsList').innerHTML = '';
  previewImg('');
  document.getElementById('formModal').classList.add('open');
  lucide.createIcons();
}
function openEdit(id) {
  const machine = allCached.find(m => m.id === id);
  if (!machine) return;
  document.getElementById('formTitle').textContent = 'Editar Equipamento';
  document.getElementById('editId').value  = id;
  document.getElementById('fName').value   = machine.name;
  document.getElementById('fCat').value    = machine.category;
  document.getElementById('fType').value   = machine.type;
  document.getElementById('fAvail').value  = machine.available !== false ? 'true' : 'false';
  document.getElementById('fPrice').value  = machine.price;
  document.getElementById('fUnit').value   = machine.priceUnit || 'dia';
  document.getElementById('fDesc').value   = machine.description;
  document.getElementById('fImg').value    = machine.image || '';
  document.getElementById('fFeat').checked = machine.featured || false;
  document.getElementById('specsList').innerHTML = '';
  (machine.specs || []).forEach(s => addSpec(s.label, s.value));
  previewImg(machine.image || '');
  document.getElementById('formModal').classList.add('open');
}
function closeForm() { document.getElementById('formModal').classList.remove('open'); }
document.getElementById('formModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeForm(); });

// ── Save ──────────────────────────────────────────────────────
async function saveMachine() {
  const name  = document.getElementById('fName').value.trim();
  const cat   = document.getElementById('fCat').value;
  const desc  = document.getElementById('fDesc').value.trim();
  const price = parseFloat(document.getElementById('fPrice').value);
  if (!name || !cat || !desc || isNaN(price)) { showToast('Preencha todos os campos obrigatórios (*).', 'error'); return; }

  const btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.innerHTML = '<i data-lucide="loader"></i> A guardar...';
  lucide.createIcons({nodes:[btn]});

  const specs = [...document.querySelectorAll('#specsList .spec-row')].map(r => ({
    label: r.querySelector('.spec-lbl').value.trim(),
    value: r.querySelector('.spec-val').value.trim()
  })).filter(s => s.label && s.value);

  const data = {
    name, category: cat,
    type: document.getElementById('fType').value,
    available: document.getElementById('fAvail').value === 'true',
    price, priceUnit: document.getElementById('fUnit').value,
    description: desc,
    image: document.getElementById('fImg').value.trim(),
    featured: document.getElementById('fFeat').checked, specs,
  };

  try {
    const eid = document.getElementById('editId').value;
    if (eid) { await updateMachine(eid, data); showToast('Equipamento atualizado!', 'success'); }
    else     { await addMachine(data);         showToast('Equipamento adicionado!', 'success'); }
    closeForm();
    allCached = [];  // invalidate cache
    loadDash();
    if (document.getElementById('pageMachines').style.display !== 'none') { allCached = []; loadTable(); }
  } catch (e) {
    showToast('Erro: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="save"></i> Guardar';
    lucide.createIcons({nodes:[btn]});
  }
}

// ── Delete ────────────────────────────────────────────────────
function openConfirm(id, name) {
  pendingDelId = id;
  document.getElementById('delName').textContent = name;
  document.getElementById('confirmModal').classList.add('open');
}
function closeConfirm() { document.getElementById('confirmModal').classList.remove('open'); pendingDelId = null; }
document.getElementById('confirmDelBtn').addEventListener('click', async () => {
  if (!pendingDelId) return;
  try {
    await deleteMachine(pendingDelId);
    closeConfirm();
    showToast('Equipamento eliminado.', 'success');
    allCached = [];
    loadDash();
    if (document.getElementById('pageMachines').style.display !== 'none') { allCached = []; loadTable(); }
  } catch(e) { showToast('Erro: ' + e.message, 'error'); }
});
document.getElementById('confirmModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeConfirm(); });

// ── Export / Import ───────────────────────────────────────────
async function doExport() {
  const ms = await getMachines();
  const blob = new Blob([JSON.stringify(ms, null, 2)], {type:'application/json'});
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a'); a.href = url; a.download = 'civica-machines-backup.json'; a.click();
  URL.revokeObjectURL(url);
  showToast('Ficheiro exportado!', 'success');
}
document.getElementById('importFile').addEventListener('change', async e => {
  const f = e.target.files[0]; if (!f) return;
  try {
    const text = await f.text();
    const data = JSON.parse(text);
    if (!Array.isArray(data)) throw new Error('Formato inválido');
    let added = 0;
    for (const m of data) {
      await addMachine(m);
      added++;
    }
    showToast(`${added} equipamentos importados!`, 'success');
    allCached = []; loadDash();
  } catch(err) { showToast('Erro: ' + err.message, 'error'); }
  e.target.value = '';
});
async function resetToSample() {
  if (!confirm('Eliminar TODOS os equipamentos e inserir os exemplos?')) return;
  const ms = await getMachines();
  for (const m of ms) await deleteMachine(m.id);
  // Insert samples via setup endpoint
  showToast('A restaurar...', 'success');
  setTimeout(() => { window.location.href = 'api/setup.php'; }, 500);
}

// ── Init ──────────────────────────────────────────────────────
loadDash();
</script>
<?php endif; ?>
</body>
</html>
