/* js/form-security.js — Cívica Equipamentos */

function sanitize(s) {
  if (typeof s !== 'string') return '';
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;').trim();
}
function isValidEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(e).toLowerCase()); }
function isValidPhone(p) { if (!p) return true; return /^\+?[\d\s\-().]{7,20}$/.test(p); }
function isBot(f) { const h = f.querySelector('.hp-trap input'); return h && h.value.length > 0; }

const PAGE_T = Date.now();
const RATE_K = 'civica_form_ts';
function checkRate() {
  try {
    const d = JSON.parse(localStorage.getItem(RATE_K) || '[]').filter(t => Date.now() - t < 600000);
    if (d.length >= 3) return false;
    d.push(Date.now());
    localStorage.setItem(RATE_K, JSON.stringify(d));
    return true;
  } catch { return true; }
}

function showErr(input, msg) {
  input.style.borderColor = '#f87171';
  let e = input.parentNode.querySelector('.field-error');
  if (!e) { e = document.createElement('div'); e.className = 'field-error'; input.after(e); }
  e.textContent = msg;
}
function clearErr(input) {
  input.style.borderColor = '';
  const e = input.parentNode.querySelector('.field-error');
  if (e) e.remove();
}

function initContactForm(id, onSuccess) {
  const form = document.getElementById(id);
  if (!form) return;

  form.querySelectorAll('.form-input').forEach(inp => {
    inp.addEventListener('blur', () => validateOne(inp));
    inp.addEventListener('input', () => { if (inp.style.borderColor) validateOne(inp); });
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    if (isBot(form))                           { form.reset(); onSuccess && onSuccess(); return; }
    if (Date.now() - PAGE_T < 3000)            { showToast('Por favor aguarde antes de enviar.', 'error'); return; }
    if (!checkRate())                           { showToast('Muitas tentativas. Aguarde alguns minutos.', 'error'); return; }

    let ok = true;
    const nome  = form.querySelector('[name=nome]');
    const email = form.querySelector('[name=email]');
    const tel   = form.querySelector('[name=telefone]');
    const msg   = form.querySelector('[name=mensagem]');

    if (nome  && nome.value.trim().length < 2)  { showErr(nome, 'Introduza o seu nome.'); ok = false; }
    if (email && !isValidEmail(email.value))     { showErr(email, 'Email inválido (ex: nome@dominio.pt).'); ok = false; }
    if (tel   && !isValidPhone(tel.value))       { showErr(tel, 'Número de telefone inválido.'); ok = false; }
    if (msg   && msg.value.trim().length < 10)   { showErr(msg, 'Mensagem muito curta (mín. 10 caracteres).'); ok = false; }

    if (!ok) return;
    form.reset();
    onSuccess && onSuccess();
  });
}

function validateOne(inp) {
  clearErr(inp);
  const n = inp.getAttribute('name');
  if (n === 'email'    && inp.value && !isValidEmail(inp.value))    showErr(inp, 'Email inválido.');
  if (n === 'nome'     && inp.value && inp.value.trim().length < 2) showErr(inp, 'Nome muito curto.');
  if (n === 'telefone' && inp.value && !isValidPhone(inp.value))    showErr(inp, 'Número inválido.');
  if (n === 'mensagem' && inp.value && inp.value.trim().length < 10) showErr(inp, 'Mínimo 10 caracteres.');
}
