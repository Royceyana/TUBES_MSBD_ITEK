/* Kafe Kaleng — interaksi UI umum (vanilla JS, tanpa dependency) */

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* ---------- format rupiah ---------- */
window.rupiah = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

/* ---------- toast ---------- */
window.toast = (msg) => {
    let box = $('#toasts');
    if (!box) {
        box = document.createElement('div');
        box.id = 'toasts';
        box.setAttribute('role', 'status');
        box.setAttribute('aria-live', 'polite');
        document.body.appendChild(box);
    }
    const t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#eda24a" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span></span>';
    t.lastElementChild.textContent = msg;
    box.appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 220); }, 2600);
};

/* ---------- modal: tumbuh dari tombol pemicu, tutup via backdrop / Esc ---------- */
const openModal = (dlg, trigger) => {
    if (trigger) {
        const r = trigger.getBoundingClientRect();
        dlg.style.setProperty('--ox', (r.left + r.width / 2) + 'px');
        dlg.style.setProperty('--oy', (r.top + r.height / 2) + 'px');
    }
    dlg.classList.remove('closing');
    dlg.showModal();
};
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
        if (dlg) {
            // Isi form dari atribut data-fill-*  (mis. data-fill-name="Latte" -> input[name=name])
            Object.entries(open.dataset).forEach(([k, v]) => {
                if (!k.startsWith('fill')) return;
                const name = k.slice(4).replace(/^./, (c) => c.toLowerCase());
                const el = dlg.querySelector(`[name="${name}"]`);
                if (el) el.value = v;
            });
            const title = dlg.querySelector('[data-modal-title]');
            if (title && open.dataset.title) title.textContent = open.dataset.title;
            openModal(dlg, open);
        }
        return;
    }
    if (e.target.closest('[data-modal-close]')) { closeModal(e.target.closest('dialog')); return; }
    if (e.target.matches('dialog.modal')) { closeModal(e.target); return; } // klik backdrop

    const tg = e.target.closest('[data-toggle]');
    if (tg) {
        const target = document.getElementById(tg.dataset.toggle);
        if (target) target.setAttribute('data-open', String(target.getAttribute('data-open') !== 'true'));
    }
});
document.addEventListener('cancel', (e) => {
    if (e.target.matches('dialog.modal')) { e.preventDefault(); closeModal(e.target); }
}, true);

/* ---------- submit: konfirmasi hapus + mode demo ---------- */
document.addEventListener('submit', (e) => {
    const f = e.target;

    // Aksi destruktif: minta konfirmasi, dengan bahasa jelas dan bisa dibatalkan
    if (f.dataset.confirm && !f.dataset.confirmed) {
        e.preventDefault();
        const dlg = $('#confirm-dialog');
        if (!dlg) return;
        $('[data-confirm-text]', dlg).textContent = f.dataset.confirm;
        openModal(dlg, e.submitter);
        $('[data-confirm-yes]', dlg).onclick = () => {
            f.dataset.confirmed = '1';
            closeModal(dlg);
            if (f.hasAttribute('data-demo')) { f.dataset.confirmed = ''; toast(f.dataset.demo || 'Dihapus (mode demo)'); }
            else f.requestSubmit ? f.requestSubmit() : f.submit();
        };
        return;
    }

    // MODE DEMO: form ber-atribut data-demo tidak dikirim ke server.
    // Backend dev: hapus atribut data-demo lalu isi action & method.
    if (f.hasAttribute('data-demo')) {
        e.preventDefault();
        closeModal(f.closest('dialog'));
        if (f.dataset.redirect) { window.location.href = f.dataset.redirect; return; }
        toast(f.dataset.demo || 'Tersimpan (mode demo)');
    }
});

/* ---------- pencarian sisi-klien: <input data-search=".selector"> ---------- */
document.addEventListener('input', (e) => {
    const s = e.target.closest('[data-search]');
    if (!s) return;
    const q = s.value.trim().toLowerCase();
    $$(s.dataset.search).forEach((el) => { el.hidden = !!q && !el.textContent.toLowerCase().includes(q); });
});

/* ---------- tampil/sembunyi password ---------- */
document.addEventListener('click', (e) => {
    const b = e.target.closest('[data-pw-toggle]');
    if (!b) return;
    const i = document.getElementById(b.dataset.pwToggle);
    i.type = i.type === 'password' ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(i.type === 'text'));
});

/* ---------- count-up pada kartu statistik ---------- */
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
$$('[data-count]').forEach((el) => {
    const to = Number(el.dataset.count), money = el.hasAttribute('data-money');
    const fmt = (v) => (money ? rupiah(v) : Math.round(v).toLocaleString('id-ID'));
    if (reduce) { el.textContent = fmt(to); return; }
    const t0 = performance.now(), d = 900;
    const tick = (t) => {
        const p = Math.min((t - t0) / d, 1);
        el.textContent = fmt(to * (1 - Math.pow(1 - p, 4)));
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
});
