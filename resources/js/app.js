import '../css/app.css';

// Template layout behaviour (theme, sidebar, dropdowns, settings panel).
// Markup is server-rendered by Blade, so this file only wires up interactions.
const H = document.documentElement;

const syncSettings = () => {
    const dark = H.classList.contains('dark');
    const small = H.classList.contains('sm-nav');
    document.querySelectorAll('[data-th]').forEach((el) => {
        el.className = 'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 ' + ((el.dataset.th === 'dark') === dark ? 'bg-tint text-primary' : 'bg-soft text-head hover:bg-line');
    });
    document.querySelectorAll('[data-sn]').forEach((el) => {
        el.className = 'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 ' + ((el.dataset.sn === '1') === small ? 'bg-tint text-primary' : 'bg-soft text-head hover:bg-line');
    });
};

const setTheme = (dark) => {
    H.classList.toggle('dark', dark);
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) { }
    syncSettings();
};

const setSmallNav = (on) => {
    H.classList.toggle('sm-nav', on);
    try { localStorage.setItem('sn', on ? '1' : '0'); } catch (e) { }
    syncSettings();
};

const closeMenus = (except = null) => {
    document.querySelectorAll('.dd-wrap.open').forEach((el) => {
        if (el !== except) el.classList.remove('open');
    });
};

document.addEventListener('click', (e) => {
    const t = e.target;

    const dd = t.closest('[data-dd]');
    if (dd) {
        const wrap = dd.closest('.dd-wrap');
        closeMenus(wrap);
        wrap.classList.toggle('open');
        return;
    }

    if (t.closest('.dd')) {
        if (t.closest('.dd a, .dd button')) closeMenus();
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
        return document.fullscreenElement
            ? document.exitFullscreen()
            : document.documentElement.requestFullscreen?.();
    }

    if (t.closest('#ov')) return H.classList.remove('sb-open', 'st-open');
    if (t.closest('#gr')) return H.classList.add('st-open');
    if (t.closest('#stx')) return H.classList.remove('st-open');
    if (t.closest('#up')) return window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Destructive actions: <form data-confirm="...">
document.addEventListener('submit', (e) => {
    const message = e.target.dataset.confirm;
    if (message && !window.confirm(message)) e.preventDefault();
});

// Role form: "এই গ্রুপের সব" checkbox
document.addEventListener('change', (e) => {
    if (!e.target.matches('[data-check-all]')) return;
    e.target.closest('fieldset')?.querySelectorAll('.perm').forEach((c) => {
        c.checked = e.target.checked;
    });
});

addEventListener('scroll', () => {
    document.getElementById('up')?.classList.toggle('show', window.scrollY > 300);
}, { passive: true });

syncSettings();
