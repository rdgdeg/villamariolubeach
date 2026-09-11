document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-menu]');
    if (btn) {
        const nav = document.getElementById('nav');
        const open = nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
});

(() => {
    const box = document.getElementById('lightbox');
    if (!box) return;
    const img = box.querySelector('img');
    let index = 0;

    const items = () => [...document.querySelectorAll('[data-lightbox]')];

    function show(i) {
        const list = items();
        if (!list.length) return;
        index = (i + list.length) % list.length;
        const el = list[index];
        img.src = el.getAttribute('data-lightbox');
        img.alt = el.querySelector('img')?.alt || '';
        box.hidden = false;
    }

    function close() {
        box.hidden = true;
        img.removeAttribute('src');
    }

    document.addEventListener('click', (e) => {
        const nav = e.target.closest('[data-lightbox-prev], [data-lightbox-next]');
        if (nav) {
            e.preventDefault();
            show(index + (nav.hasAttribute('data-lightbox-prev') ? -1 : 1));
            return;
        }
        if (e.target.closest('[data-lightbox-close]')) {
            close();
            return;
        }
        const item = e.target.closest('[data-lightbox]');
        if (item) {
            show(items().indexOf(item));
            return;
        }
        if (e.target === box) close();
    });

    document.addEventListener('keydown', (e) => {
        if (box.hidden) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') show(index - 1);
        if (e.key === 'ArrowRight') show(index + 1);
    });
})();

(() => {
    const root = document.querySelector('[data-hero-slider]');
    if (!root) return;
    const slides = [...root.querySelectorAll('.hero-slide')];
    const dots = [...root.querySelectorAll('[data-hero-dot]')];
    if (slides.length < 2) return;
    let index = 0;
    let timer;
    let busy = false;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fadeMs = reduced ? 350 : 1150;

    function go(n) {
        const next = (n + slides.length) % slides.length;
        if (next === index || busy) return;
        const outgoing = slides[index];
        const incoming = slides[next];
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === next));

        if (reduced) {
            outgoing.classList.remove('is-active', 'is-leaving');
            incoming.classList.add('is-active');
            index = next;
            return;
        }

        busy = true;
        outgoing.classList.remove('is-active');
        outgoing.classList.add('is-leaving');
        incoming.classList.add('is-active');
        const img = incoming.querySelector('img');
        if (img) {
            img.style.animation = 'none';
            void img.offsetWidth;
            img.style.animation = '';
        }
        index = next;
        window.setTimeout(() => {
            outgoing.classList.remove('is-leaving');
            busy = false;
        }, fadeMs);
    }
    function start() {
        stop();
        timer = window.setInterval(() => go(index + 1), 5000);
    }
    function stop() {
        window.clearInterval(timer);
    }

    root.querySelector('[data-hero-prev]')?.addEventListener('click', () => { go(index - 1); start(); });
    root.querySelector('[data-hero-next]')?.addEventListener('click', () => { go(index + 1); start(); });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            go(Number(dot.dataset.heroDot));
            start();
        });
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop();
        else start();
    });
    start();
})();

document.querySelectorAll('[data-gallery-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const wrap = btn.closest('[data-gallery]');
        if (!wrap) return;
        const open = wrap.classList.toggle('is-open');
        btn.textContent = open ? btn.dataset.less : btn.dataset.more;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open) {
            wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

(() => {
    const nodes = document.querySelectorAll('.reveal');
    if (!nodes.length) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
        nodes.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
    nodes.forEach((el) => io.observe(el));
})();

(() => {
    const btn = document.querySelector('[data-back-top]');
    if (!btn) return;
    const toggle = () => {
        btn.hidden = window.scrollY < 420;
    };
    window.addEventListener('scroll', toggle, { passive: true });
    toggle();
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });
})();

(() => {
    const banner = document.getElementById('cookie-banner');
    if (!banner) return;
    const key = 'vmb-cookies';
    if (localStorage.getItem(key)) {
        banner.hidden = true;
        return;
    }
    document.body.classList.add('has-cookie-banner');
    banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        localStorage.setItem(key, '1');
        banner.hidden = true;
        document.body.classList.remove('has-cookie-banner');
    });
})();
