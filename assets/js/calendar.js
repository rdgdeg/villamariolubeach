(() => {
    const roots = document.querySelectorAll('[data-calendar]');
    if (!roots.length || !window.VMB) return;

    const i18n = VMB.i18n;
    const blockedNight = new Set(['unavailable', 'blocked', 'booked', 'pending']);

    const pad = (n) => String(n).padStart(2, '0');
    function fmtDate(iso) {
        const m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]}` : String(iso || '');
    }
    function escAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
    const iso = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parse = (s) => {
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d);
    };

    roots.forEach((root) => initCalendar(root));

    function initCalendar(root) {
        const mode = root.dataset.mode || 'range';
        const monthCount = Math.min(12, Math.max(1, parseInt(root.dataset.months || '2', 10)));
        let cursor = new Date();
        cursor.setDate(1);
        let monthsData = [];
        let start = null;
        let end = null;

        function statusFor(date) {
            for (const month of monthsData) {
                const day = month.days.find((d) => d.date === date);
                if (day) return day.status;
            }
            return 'unavailable';
        }

        function nightsBlocked(from, toExclusive) {
            let d = parse(from);
            const endDate = parse(toExclusive);
            while (d < endDate) {
                if (blockedNight.has(statusFor(iso(d)))) return true;
                d.setDate(d.getDate() + 1);
            }
            return false;
        }

        function canStart(date) {
            if (mode === 'block') return true;
            return !blockedNight.has(statusFor(date));
        }

        function canEnd(date) {
            if (date <= start) return false;
            if (mode === 'block') return true;
            return !nightsBlocked(start, date);
        }

        function syncInputs() {
            const cin = document.getElementById('check_in') || root.parentElement?.querySelector('[name="check_in"]');
            const cout = document.getElementById('check_out') || root.parentElement?.querySelector('[name="check_out"]');
            const formIn = document.querySelector('form [name="check_in"]');
            const formOut = document.querySelector('form [name="check_out"]');
            [cin, formIn].forEach((el) => { if (el) el.value = start || ''; });
            [cout, formOut].forEach((el) => { if (el) el.value = end || ''; });
        }

        async function load() {
            const month = `${cursor.getFullYear()}-${pad(cursor.getMonth() + 1)}`;
            const res = await fetch(`${VMB.api}/calendar?month=${month}&months=${monthCount}&lang=${VMB.lang}${VMB.admin ? '&details=1' : ''}`);
            const json = await res.json();
            monthsData = json.months || [];
            render();
        }

        function render() {
            root.innerHTML = `
                <div class="cal-nav">
                    <button type="button" data-cal="prev" aria-label="prev">‹</button>
                    <strong></strong>
                    <button type="button" data-cal="next" aria-label="next">›</button>
                </div>
                <div class="cal-months"></div>
            `;
            const wrap = root.querySelector('.cal-months');
            monthsData.forEach((month) => {
                const el = document.createElement('div');
                el.className = 'cal-month';
                const title = `${i18n.months[month.month - 1]} ${month.year}`;
                const weekdays = i18n.weekdays.map((w) => `<span class="cal-wd">${w}</span>`).join('');
                const pads = month.start_weekday === 7 ? 6 : month.start_weekday - 1;
                let days = '';
                for (let i = 0; i < pads; i++) days += `<span class="cal-day pad"></span>`;
                month.days.forEach((d) => {
                    const cls = ['cal-day', d.status];
                    if (start && !end && d.date === start) {
                        cls.push('start', 'in-range');
                    }
                    if (start && end) {
                        if (d.date >= start && d.date < end) cls.push('in-range');
                        if (d.date === start) cls.push('start');
                        if (d.date === end) cls.push('checkout');
                    }
                    const title = d.guest ? `${d.guest} — ${fmtDate(d.date)}` : fmtDate(d.date);
                    const bookingAttr = d.booking_id ? ` data-booking="${d.booking_id}"` : '';
                    days += `<button type="button" class="${cls.join(' ')}" data-date="${d.date}"${bookingAttr} title="${escAttr(title)}" aria-label="${escAttr(title)}">${d.day}</button>`;
                });
                el.innerHTML = `<h3>${title}</h3><div class="cal-grid">${weekdays}${days}</div>`;
                wrap.appendChild(el);
            });
        }

        async function quote() {
            if (mode !== 'booking') return;
            const box = document.getElementById('quote');
            const err = document.getElementById('quote-error');
            const submit = document.getElementById('book-submit');
            const cont = document.getElementById('step-continue');
            const cin = document.getElementById('check_in');
            const cout = document.getElementById('check_out');
            if (!box) return;
            if (!start || !end) {
                box.hidden = true;
                if (submit) submit.disabled = true;
                if (cont) cont.disabled = true;
                return;
            }
            cin.value = start;
            cout.value = end;
            const res = await fetch(`${VMB.api}/quote?lang=${encodeURIComponent(VMB.lang)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ check_in: start, check_out: end }),
            });
            const data = await res.json();
            if (!data.ok) {
                box.hidden = true;
                err.hidden = false;
                const tpl = i18n.errors[data.error] || data.error;
                err.textContent = tpl.replace(':min', data.min || '').replace(':max', data.max || '');
                if (submit) submit.disabled = true;
                if (cont) cont.disabled = true;
                return;
            }
            err.hidden = true;
            const euro = (n) => new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n);
            const nightsHtml = (data.nights_detail || []).map((n) =>
                `<li><span>${fmtDate(n.date)}</span><span>${euro(n.rate)}</span></li>`
            ).join('');
            box.hidden = false;
            box.innerHTML = `
                <div class="quote-stay">
                    <strong>${i18n.nights.replace(':n', data.nights)}</strong>
                    <span>${fmtDate(start)} → ${fmtDate(end)}</span>
                </div>
                <p class="quote-times">${i18n.checkin_from} · ${i18n.checkout_until}</p>
                ${nightsHtml ? `<details class="quote-nights"><summary>${i18n.nightly_rates}</summary><ul>${nightsHtml}</ul></details>` : ''}
                <div><span>${i18n.rental}</span><span>${euro(data.rental_subtotal)}</span></div>
                ${data.discount_percent ? `<div><span>${i18n.discount.replace(':p', data.discount_percent)}</span><span>− ${euro(data.discount_amount)}</span></div>` : ''}
                <div><span>${i18n.cleaning}</span><span>${euro(data.cleaning_fee)}</span></div>
                <div class="total"><span>${i18n.total}</span><span>${euro(data.total)}</span></div>
                <div><span>${i18n.deposit.replace(':p', data.deposit_percent)}</span><span>${euro(data.deposit_amount)}</span></div>
                <div><span>${i18n.balance}</span><span>${euro(data.balance)}</span></div>
                <div><span>${i18n.caution}</span><span>${euro(data.caution)}</span></div>
            `;
            if (submit) submit.disabled = false;
            if (cont) cont.disabled = false;
            const summary = document.getElementById('quote-summary');
            if (summary) {
                summary.innerHTML = `<h2 class="panel-title">${i18n.summary || ''}</h2><div class="quote">${box.innerHTML}</div>`;
            }
        }

        root.addEventListener('click', (e) => {
            const nav = e.target.closest('[data-cal]');
            if (nav) {
                const dir = nav.dataset.cal === 'next' ? 1 : -1;
                cursor.setMonth(cursor.getMonth() + dir);
                load();
                return;
            }
            const day = e.target.closest('[data-date]');
            if (!day) return;
            const date = day.dataset.date;
            if (mode === 'block' && VMB.adminBase && day.dataset.booking && (!start || end)) {
                window.location.href = `${VMB.adminBase}/booking/${day.dataset.booking}`;
                return;
            }
            if (!start || (start && end)) {
                if (!canStart(date)) return;
                start = date;
                end = null;
            } else if (date === start) {
                start = null;
                end = null;
            } else if (date < start) {
                if (!canStart(date)) return;
                start = date;
                end = null;
            } else if (canEnd(date)) {
                end = date;
            } else {
                return;
            }
            syncInputs();
            render();
            quote();
        });

        const form = document.getElementById('booking-form');
        if (form && mode === 'booking' && !form.dataset.calBound) {
            form.dataset.calBound = '1';
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const err = document.getElementById('step2-error') || document.getElementById('quote-error');
                const ok = document.getElementById('book-success');
                const payload = Object.fromEntries(new FormData(form).entries());
                payload.csrf = VMB.csrf;
                payload.extras = [...form.querySelectorAll('[name="extras[]"]:checked')].map((el) => el.value);
                payload.accept_terms = form.querySelector('[name="accept_terms"]')?.checked ? '1' : '';
                const res = await fetch(`${VMB.api}/booking?lang=${encodeURIComponent(VMB.lang)}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!data.ok) {
                    ok.hidden = true;
                    err.hidden = false;
                    const tpl = i18n.errors[data.error] || data.error;
                    err.textContent = tpl.replace(':min', data.min || '').replace(':max', data.max || '');
                    return;
                }
                err.hidden = true;
                ok.hidden = false;
                const pay = document.getElementById('book-payinfo');
                const letter = document.getElementById('book-letter');
                if (pay && letter && data.letter) {
                    letter.textContent = data.letter;
                    pay.hidden = false;
                }
                start = null;
                end = null;
                form.querySelectorAll('input, textarea, select, button').forEach((el) => {
                    if (el.type !== 'hidden') el.disabled = true;
                });
                load();
            });
        }

        load();
    }
})();
