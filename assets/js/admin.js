(() => {
    document.querySelectorAll('tr[data-href]').forEach((row) => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('a, button, form, input, label, select, textarea')) return;
            window.location.href = row.dataset.href;
        });
    });
})();

(() => {
    const root = document.querySelector('[data-email-editor]');
    if (!root) return;
    const tabs = [...root.querySelectorAll('[data-email-tab]')];
    const panes = [...root.querySelectorAll('[data-email-pane]')];
    const langInput = root.querySelector('[data-email-test-lang]');
    let lastField = root.querySelector('.email-pane.is-active textarea')
        || root.querySelector('.email-pane.is-active input');

    function show(lang) {
        tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.emailTab === lang));
        panes.forEach((pane) => pane.classList.toggle('is-active', pane.dataset.emailPane === lang));
        if (langInput) langInput.value = lang;
        lastField = root.querySelector('.email-pane.is-active textarea')
            || root.querySelector('.email-pane.is-active input');
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => show(tab.dataset.emailTab));
    });
    root.addEventListener('focusin', (e) => {
        if (e.target.matches('textarea, input:not([type="hidden"])')) {
            lastField = e.target;
        }
    });
    root.querySelectorAll('[data-insert-token]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.dataset.insertToken || '';
            const field = lastField || root.querySelector('.email-pane.is-active textarea');
            if (!field) return;
            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;
            field.value = field.value.slice(0, start) + token + field.value.slice(end);
            field.focus();
            const pos = start + token.length;
            field.setSelectionRange(pos, pos);
        });
    });
})();

(() => {
    document.querySelectorAll('[data-deposit-sync]').forEach((root) => {
        const pct = root.querySelector('[name="deposit_percent"]');
        const amt = root.querySelector('[name="deposit_amount"]');
        const source = root.querySelector('[data-deposit-source]');
        const rental = parseFloat(root.dataset.rental || '0');
        if (!pct || !amt) return;
        const euro = (n) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(n);
        const refreshDepositUi = () => {
            const a = parseFloat(String(amt.value).replace(',', '.')) || 0;
            const p = parseFloat(String(pct.value).replace(',', '.')) || 0;
            const total = parseFloat(root.dataset.total || '0');
            const depositLine = document.querySelector('[data-deposit-line]');
            const balanceLine = document.querySelector('[data-balance-line]');
            const pctLabel = document.querySelector('[data-deposit-percent-label]');
            const mailBtn = document.querySelector('[data-deposit-mail-btn]');
            const balBtn = document.querySelector('[data-balance-mail-btn]');
            if (depositLine) depositLine.textContent = euro(a);
            if (pctLabel) pctLabel.textContent = String(p);
            if (total > 0 && balanceLine) balanceLine.textContent = euro(Math.max(0, total - a));
            if (mailBtn) mailBtn.textContent = 'Relance acompte 1 (' + euro(a) + ')';
            if (balBtn && total > 0) balBtn.textContent = 'Demande de solde (' + euro(Math.max(0, total - a)) + ')';
        };
        pct.addEventListener('input', () => {
            if (source) source.value = 'percent';
            const p = parseFloat(String(pct.value).replace(',', '.')) || 0;
            if (rental > 0) amt.value = (rental * p / 100).toFixed(2);
            refreshDepositUi();
        });
        amt.addEventListener('input', () => {
            if (source) source.value = 'amount';
            const a = parseFloat(String(amt.value).replace(',', '.')) || 0;
            if (rental > 0) pct.value = ((a / rental) * 100).toFixed(2);
            refreshDepositUi();
        });
    });
})();

(() => {
    document.querySelectorAll('[data-autosave-flags] input[type="checkbox"]').forEach((box) => {
        box.addEventListener('change', () => {
            const form = box.closest('form');
            if (form) form.requestSubmit();
        });
    });
})();

(() => {
    const dialog = document.getElementById('confirm-stay-dialog');
    if (!dialog) return;
    const copy = dialog.querySelector('[data-confirm-stay-copy]');
    const amount = dialog.querySelector('[data-confirm-stay-amount]');
    let pending = null;

    document.querySelectorAll('[data-confirm-stay]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            if (btn.dataset.stayReady === '1') {
                delete btn.dataset.stayReady;
                return;
            }
            e.preventDefault();
            pending = btn;
            const email = btn.dataset.email || '';
            const liveAmt = document.querySelector('[data-deposit-sync] [name="deposit_amount"]');
            const current = liveAmt ? liveAmt.value : (btn.dataset.amount || '0');
            if (copy) {
                copy.textContent = 'Envoyer l’e-mail d’acompte à ' + email + ' et bloquer les dates ?';
            }
            if (amount) amount.value = Number(current).toFixed(2);
            dialog.showModal();
        });
    });

    dialog.addEventListener('close', () => {
        if (!pending || dialog.returnValue !== 'ok') {
            pending = null;
            return;
        }
        const btn = pending;
        pending = null;
        const form = btn.closest('form');
        if (!form) return;
        const value = amount ? amount.value : btn.dataset.amount;
        const field = form.querySelector('[name="deposit_amount"]');
        if (field) {
            field.value = value;
        } else {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'deposit_amount';
            hidden.value = value;
            form.appendChild(hidden);
        }
        const source = form.querySelector('[data-deposit-source], [name="deposit_source"]');
        if (source) source.value = 'amount';
        btn.dataset.stayReady = '1';
        form.requestSubmit(btn);
    });
})();
