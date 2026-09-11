(() => {
    const form = document.getElementById('booking-form');
    const step1 = document.getElementById('booking-step-1');
    const step2 = document.getElementById('booking-step-2');
    const cont = document.getElementById('step-continue');
    const back = document.getElementById('step-back');
    if (!form || !step1 || !step2) return;

    const progress = document.querySelectorAll('.booking-progress li');

    function go(step) {
        const first = step === 1;
        step1.hidden = !first;
        step2.hidden = first;
        progress.forEach((li) => {
            const n = Number(li.dataset.stepLabel);
            li.classList.toggle('is-current', n === step);
            li.classList.toggle('is-done', n < step);
        });
        (first ? step1 : step2).scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    cont?.addEventListener('click', () => {
        if (!document.getElementById('check_in').value || !document.getElementById('check_out').value) return;
        const quote = document.getElementById('quote');
        const summary = document.getElementById('quote-summary');
        if (quote && summary && quote.innerHTML) {
            const title = window.VMB?.i18n?.summary || '';
            summary.innerHTML = (title ? `<h2 class="panel-title">${title}</h2>` : '') + `<div class="quote">${quote.innerHTML}</div>`;
        }
        go(2);
    });

    back?.addEventListener('click', () => go(1));
})();
