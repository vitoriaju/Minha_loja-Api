document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const mobileButton = document.querySelector('[data-menu-toggle]');
    const closeButton = document.querySelector('[data-menu-close]');
    const closeMenu = () => {
        body.classList.remove('menu-open');
        mobileButton?.setAttribute('aria-expanded', 'false');
    };

    mobileButton?.addEventListener('click', () => {
        const open = body.classList.toggle('menu-open');
        mobileButton.setAttribute('aria-expanded', String(open));
    });
    closeButton?.addEventListener('click', closeMenu);
    window.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
    window.addEventListener('resize', () => { if (window.innerWidth > 900) closeMenu(); });

    document.querySelectorAll('[data-menu-group] > .menu-group-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const group = button.closest('[data-menu-group]');
            const open = group.classList.toggle('is-open');
            button.setAttribute('aria-expanded', String(open));
        });
    });
});
