/* Sistem HC Catering — interaksi kecil tanpa build step: menu HP, drawer, modal, toast, tooltip grafik, chatbot. */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name="csrf-token"]')?.content;

  /* ---------- toast ---------- */
  let tt;
  function toast(msg) {
    const el = $('#toast');
    if (!el || !msg) return;
    $('#toastTxt').textContent = msg;
    el.classList.add('on');
    clearTimeout(tt);
    tt = setTimeout(() => el.classList.remove('on'), 2800);
  }
  window.HC = { toast };
  if (document.body.dataset.toastFlash) toast(document.body.dataset.toastFlash);

  /* ---------- sidebar (HP) ---------- */
  $('#menuBtn')?.addEventListener('click', () => $('#side').classList.toggle('open'));

  /* ---------- overlay ---------- */
  const scrim = $('#scrim'), drawerEl = $('#drawer'), modalEl = $('#modal');
  function closeAll() {
    scrim?.classList.remove('on');
    drawerEl?.classList.remove('on');
    drawerEl?.setAttribute('aria-hidden', 'true');
    modalEl?.classList.remove('on');
  }
  scrim?.addEventListener('click', closeAll);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
  document.addEventListener('click', (e) => { if (e.target.closest('[data-close]')) closeAll(); });

  function openDrawer(html) {
    drawerEl.innerHTML = html;
    scrim.classList.add('on');
    drawerEl.classList.add('on');
    drawerEl.setAttribute('aria-hidden', 'false');
    $('.x', drawerEl)?.focus();
  }
  async function loadDrawer(url) {
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error(res.status);
      openDrawer(await res.text());
    } catch (err) {
      toast('Gagal memuat detail');
    }
  }
  function openModal(id) {
    const tpl = document.getElementById(id);
    if (!tpl || !modalEl) return;
    modalEl.innerHTML = tpl.innerHTML;
    scrim.classList.add('on');
    modalEl.classList.add('on');
    $('input:not([type=hidden]),select,textarea', modalEl)?.focus();
  }

  document.addEventListener('click', (e) => {
    const d = e.target.closest('[data-drawer-url]');
    if (d) { e.preventDefault(); loadDrawer(d.dataset.drawerUrl); return; }
    const m = e.target.closest('[data-modal]');
    if (m) { e.preventDefault(); closeAll(); openModal(m.dataset.modal); return; }
    const t = e.target.closest('[data-toast]');
    if (t) toast(t.dataset.toast);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const d = e.target.closest('[data-drawer-url]');
    if (d) loadDrawer(d.dataset.drawerUrl);
  });
  if (document.body.dataset.openDrawer) loadDrawer(document.body.dataset.openDrawer);

  /* ---------- konfirmasi & auto-submit ---------- */
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
  });
  document.addEventListener('change', (e) => {
    if (e.target.matches('[data-autosubmit]')) e.target.form.requestSubmit();
  });

  /* ---------- tooltip grafik ---------- */
  $$('.chart').forEach((ch) => {
    const tip = $('.tip', ch);
    if (!tip) return;
    $$('.hit', ch).forEach((h) => {
      h.addEventListener('mouseenter', () => { tip.textContent = h.dataset.tip; tip.classList.add('on'); });
      h.addEventListener('mousemove', (e) => {
        const r = ch.getBoundingClientRect();
        tip.style.left = (e.clientX - r.left) + 'px';
        tip.style.top = (e.clientY - r.top - 6) + 'px';
      });
      h.addEventListener('mouseleave', () => tip.classList.remove('on'));
    });
  });

  /* ---------- simulasi chatbot WhatsApp ---------- */
  const waForm = $('#waForm');
  if (waForm) {
    const chat = $('#chat');
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const now = () => new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(':', '.');
    const scroll = () => { chat.parentElement.scrollTop = 1e6; };
    waForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = $('#waMsg');
      const text = input.value.trim();
      if (!text) return;
      chat.insertAdjacentHTML('beforeend', `<div class="bub out">${esc(text)}<span class="t">${now()} ✓✓</span></div>`);
      input.value = '';
      scroll();
      try {
        const res = await fetch(waForm.action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify({ message: text }),
        });
        const data = await res.json();
        const btn = data.button ? `<span class="qbtn">${esc(data.button)}</span>` : '';
        chat.insertAdjacentHTML('beforeend', `<div class="bub in">${esc(data.reply)}${btn}<span class="t">${now()}</span></div>`);
      } catch (err) {
        toast('Gagal menghubungi bot');
      }
      scroll();
    });
  }
})();
