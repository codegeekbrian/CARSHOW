/* Theme: Dark / Light Switching */
(function initTheme() {
    const saved = localStorage.getItem('site-theme');
    const prefers = matchMedia('(prefers-color-scheme:light)');
    const mode = saved || (prefers.matches ? 'light' : 'dark');
    document.documentElement.setAttribute('data-theme', mode);
})();

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;
    const setBtn = (mode) => {
        btn.setAttribute('aria-label', mode === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
        btn.dataset.mode = mode;
        const label = btn.querySelector('.theme-label');
        if (label) label.textContent = mode === 'light' ? 'Day' : 'Night';
    };
    setBtn(document.documentElement.getAttribute('data-theme'));
    btn.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('site-theme', next);
        setBtn(next);
    });
});
