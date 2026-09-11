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
