// Admin layout behaviour: theme, sidebar, dropdowns, settings panel, small form helpers.
// Markup and styling come from Blade + Tailwind classes; this file only toggles state.
const D = document;
const H = D.documentElement;

const BASE = 'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md px-4 py-2 text-sm font-medium';
const ON = 'bg-tint text-primary';
const OFF = 'bg-soft text-head hover:bg-line';

const sync = () => {
    const dark = H.classList.contains('dark');
    const small = H.classList.contains('sm-nav');
    D.querySelectorAll('[data-th]').forEach((el) => {
        el.className = BASE + ' ' + ((el.dataset.th === 'dark') === dark ? ON : OFF);
    });
    D.querySelectorAll('[data-sn]').forEach((el) => {
        el.className = BASE + ' ' + ((el.dataset.sn === '1') === small ? ON : OFF);
    });
};

const setTheme = (dark) => {
    H.classList.toggle('dark', dark);
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
    sync();
};

const setSmallNav = (on) => {
    H.classList.toggle('sm-nav', on);
    try { localStorage.setItem('sn', on ? '1' : '0'); } catch (e) {}
    sync();
};

const closeMenus = (except = null) => {
    D.querySelectorAll('[data-dw].open').forEach((el) => {
        if (el !== except) el.classList.remove('open');
    });
};

D.addEventListener('click', (e) => {
    const t = e.target;

    // Sidebar sub-menu toggle (collapses to icons on desktop first)
    const tg = t.closest('[data-tg]');
    if (tg) {
        if (H.classList.contains('sm-nav') && innerWidth >= 1024) setSmallNav(false);
        tg.parentElement.classList.toggle('open');
        return;
    }

    // Dropdown trigger
    const trigger = t.closest('[data-dd]');
    if (trigger) {
        const wrap = trigger.closest('[data-dw]');
        closeMenus(wrap);
        wrap.classList.toggle('open');
        return;
    }

    // Inside a dropdown: links and buttons close it, anything else keeps it open
    if (t.closest('[data-dp]')) {
        if (t.closest('[data-dp] a, [data-dp] button')) closeMenus();
        return;
    }

    closeMenus();

    if (t.closest('#th')) return setTheme(!H.classList.contains('dark'));

    const th = t.closest('[data-th]');
    if (th) return setTheme(th.dataset.th === 'dark');

    const sn = t.closest('[data-sn]');
    if (sn) return setSmallNav(sn.dataset.sn === '1');

    if (t.closest('#mt')) {
        return innerWidth >= 1024
            ? setSmallNav(!H.classList.contains('sm-nav'))
            : H.classList.toggle('sb-open');
    }

    if (t.closest('#fs')) {
        return D.fullscreenElement
            ? D.exitFullscreen()
            : H.requestFullscreen?.();
    }

    if (t.closest('#ov')) return H.classList.remove('sb-open', 'st-open');
    if (t.closest('#gr')) return H.classList.add('st-open');
    if (t.closest('#stx')) return H.classList.remove('st-open');
    if (t.closest('#up')) return window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Destructive actions: <form data-confirm="...">
D.addEventListener('submit', (e) => {
    const message = e.target.dataset.confirm;
    if (message && !window.confirm(message)) e.preventDefault();
});

// Role form: "এই গ্রুপের সব" checkbox
D.addEventListener('change', (e) => {
    if (!e.target.matches('[data-check-all]')) return;
    e.target.closest('fieldset')?.querySelectorAll('.perm').forEach((c) => {
        c.checked = e.target.checked;
    });
});

// Back-to-top button fades in after scrolling
const up = D.getElementById('up');
const onScroll = () => {
    if (!up) return;
    const show = window.scrollY > 300;
    up.classList.toggle('opacity-0', !show);
    up.classList.toggle('pointer-events-none', !show);
};
window.addEventListener('scroll', onScroll, { passive: true });

sync();
onScroll();
