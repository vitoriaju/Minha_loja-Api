document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-financeiro]');
    if (!page) return;

    const toggle = page.querySelector('[data-form-toggle]');
    const panel = page.querySelector('[data-form-panel]');
    const label = toggle?.querySelector('[data-toggle-label]');
    const icon = toggle?.querySelector('[data-toggle-icon]');

    toggle?.addEventListener('click', () => {
        const opening = panel.hasAttribute('hidden');
        panel.toggleAttribute('hidden', !opening);
        toggle.setAttribute('aria-expanded', String(opening));
        if (label) label.textContent = opening ? 'Fechar lançamento' : 'Novo lançamento';
        if (icon) icon.textContent = opening ? '−' : '+';
        if (opening) panel.querySelector('select, input:not([type="hidden"])')?.focus();
    });

    const rows = Array.from(page.querySelectorAll('[data-movement-row]'));
    const filters = Array.from(page.querySelectorAll('[data-filter]'));
    const empty = page.querySelector('[data-filter-empty]');

    const applyFilter = value => {
        const selected = value === 'clear' ? 'all' : value;
        let visible = 0;
        rows.forEach(row => {
            const show = selected === 'all' || row.dataset.tipo === selected || row.dataset.forma === selected;
            row.hidden = !show;
            if (show) visible++;
        });
        filters.forEach(button => button.classList.toggle('ativo', button.dataset.filter === selected));
        if (empty) empty.hidden = visible !== 0;
    };

    filters.forEach(button => button.addEventListener('click', () => applyFilter(button.dataset.filter)));
});
