/* Kafe Kaleng — interaksi UI umum (vanilla JS, tanpa dependency) */

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ---------- format rupiah ---------- */
window.rupiah = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

/* ---------- toast ---------- */
window.toast = (msg, error = false) => {
    let box = $('#toasts');
    if (!box) {
        box = document.createElement('div');
        box.id = 'toasts';
        box.setAttribute('role', 'status');
        box.setAttribute('aria-live', 'polite');
        document.body.appendChild(box);
    }
    const t = document.createElement('div');
    t.className = 'toast' + (error ? ' err' : '');
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 220); }, error ? 4800 : 2800);
};

/* ---------- modal ---------- */
const openModal = (dlg) => { dlg.classList.remove('closing'); dlg.showModal(); };
const closeModal = (dlg) => {
    if (!dlg || !dlg.open) return;
    dlg.classList.add('closing');
    setTimeout(() => { dlg.close(); dlg.classList.remove('closing'); }, 150);
};
window.openModal = openModal;
window.closeModal = closeModal;

document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-modal-open]');
    if (open) {
        const dlg = document.getElementById(open.dataset.modalOpen);
        if (!dlg) return;
        const form = dlg.querySelector('form');
        // Isi form dari data-fill-* (mis. data-fill-name="Latte" -> input[name=name])
        Object.entries(open.dataset).forEach(([k, v]) => {
            if (!k.startsWith('fill')) return;
            const name = k.slice(4).replace(/^./, (c) => c.toLowerCase());
            const el = dlg.querySelector(`[name="${name}"]`);
            if (el) el.value = v;
        });
        // Mode ubah: arahkan form ke URL update dan pakai method PUT
        if (form && 'action' in open.dataset) {
            form.action = open.dataset.action;
            const m = form.querySelector('input[name=_method]');
            if (m) m.value = open.dataset.method || 'POST';
        }
        const title = dlg.querySelector('[data-modal-title]');
        if (title && open.dataset.title) title.textContent = open.dataset.title;
        openModal(dlg);
        return;
    }
    if (e.target.closest('[data-modal-close]')) { closeModal(e.target.closest('dialog')); return; }
    if (e.target.matches('dialog.modal')) { closeModal(e.target); return; }

    const tg = e.target.closest('[data-toggle]');
    if (tg) {
        const target = document.getElementById(tg.dataset.toggle);
        if (target) {
            const next = target.getAttribute('data-open') !== 'true';
            target.setAttribute('data-open', String(next));
            document.querySelectorAll(`[data-toggle="${tg.dataset.toggle}"]`).forEach((b) => b.setAttribute('aria-expanded', String(next)));
        }
    }
});
document.addEventListener('cancel', (e) => {
    if (e.target.matches('dialog.modal')) { e.preventDefault(); closeModal(e.target); }
}, true);

/* ---------- konfirmasi aksi destruktif: <form data-confirm="teks" data-confirm-yes="Ya, hapus"> ---------- */
document.addEventListener('submit', (e) => {
    const f = e.target;
    if (!f.dataset.confirm || f.dataset.confirmed) return;
    e.preventDefault();
    const dlg = $('#confirm-dialog');
    if (!dlg) { f.dataset.confirmed = '1'; f.requestSubmit(); return; }
    $('[data-confirm-text]', dlg).textContent = f.dataset.confirm;
    $('[data-confirm-yes]', dlg).textContent = f.dataset.confirmYes || 'Ya, lanjutkan';
    openModal(dlg);
    $('[data-confirm-yes]', dlg).onclick = () => { f.dataset.confirmed = '1'; closeModal(dlg); f.requestSubmit(); };
});

/* ---------- pencarian sisi-klien: <input data-search=".selector"> ---------- */
document.addEventListener('input', (e) => {
    const s = e.target.closest('[data-search]');
    if (!s) return;
    const q = s.value.trim().toLowerCase();
    $$(s.dataset.search).forEach((el) => { el.hidden = !!q && !el.textContent.toLowerCase().includes(q); });
    const empty = s.dataset.empty && document.querySelector(s.dataset.empty);
    if (empty) empty.hidden = $$(s.dataset.search).some((el) => !el.hidden);
});

/* ---------- tampil/sembunyi password ---------- */
document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-pw-toggle]');
    if (!b) return;
    const i = document.getElementById(b.dataset.pwToggle);
    i.type = i.type === 'password' ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(i.type === 'text'));
    b.textContent = i.type === 'text' ? 'Sembunyikan' : 'Tampilkan';
});

/* ---------- filter kategori generik: <div data-filter="#target > *"> chip data-cat ---------- */
document.addEventListener('click', (e) => {
    const chip = e.target.closest('[data-filter] .chip');
    if (!chip) return;
    const group = chip.closest('[data-filter]');
    $$('.chip', group).forEach((c) => c.setAttribute('aria-pressed', String(c === chip)));
    let shown = 0;
    $$(group.dataset.filter).forEach((el) => {
        el.hidden = chip.dataset.cat !== 'all' && el.dataset.cat !== chip.dataset.cat;
        if (!el.hidden) shown++;
    });
    const empty = group.dataset.empty && document.querySelector(group.dataset.empty);
    if (empty) empty.hidden = shown > 0;
});
