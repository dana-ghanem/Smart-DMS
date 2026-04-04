// ── Documents Index: Autocomplete + Live Filters ──

document.addEventListener('DOMContentLoaded', () => {

    const searchInput    = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const authorFilter   = document.getElementById('authorFilter');
    const clearBtn       = document.getElementById('clearBtn');
    const resultCount    = document.getElementById('resultCount');
    const noResultsRow   = document.getElementById('noResultsRow');
    const acDropdown     = document.getElementById('acDropdown');
    const rows           = document.querySelectorAll('#tableBody tr[data-title]');

    if (!searchInput) return; // guard — only run on index page

    // ── Build suggestion pool from table rows ──
    const pool = [];
    rows.forEach(row => {
        const title  = row.querySelector('td.td-title')?.textContent.trim() || '';
        const author = row.querySelector('td.td-muted')?.textContent.trim() || '';
        const cat    = row.dataset.category || '';
        if (title)                    pool.push({ type: 'title',  label: title,  icon: 'fa-file-alt', sub: cat });
        if (author && author !== '—') pool.push({ type: 'author', label: author, icon: 'fa-user',     sub: '' });
    });

    // Deduplicate by type + label
    const seen = new Set();
    const suggestions = pool.filter(s => {
        const key = s.type + '::' + s.label.toLowerCase();
        if (seen.has(key)) return false;
        seen.add(key); return true;
    });

    // ── Highlight matched portion ──
    function highlight(text, query) {
        if (!query) return text;
        const idx = text.toLowerCase().indexOf(query.toLowerCase());
        if (idx === -1) return text;
        return text.slice(0, idx)
            + '<mark>' + text.slice(idx, idx + query.length) + '</mark>'
            + text.slice(idx + query.length);
    }

    // ── Render autocomplete dropdown ──
    let activeIdx = -1;

    function renderDropdown(q) {
        acDropdown.innerHTML = '';
        activeIdx = -1;

        if (!q) { acDropdown.classList.remove('open'); return; }

        const matched = suggestions
            .filter(s => s.label.toLowerCase().includes(q.toLowerCase()))
            .slice(0, 8);

        if (matched.length === 0) {
            acDropdown.innerHTML = '<div class="ac-empty">No suggestions found</div>';
            acDropdown.classList.add('open');
            return;
        }

        const titles  = matched.filter(s => s.type === 'title');
        const authors = matched.filter(s => s.type === 'author');

        function renderGroup(label, items, iconClass) {
            if (!items.length) return;
            const grp = document.createElement('div');
            grp.className = 'ac-group-label';
            grp.textContent = label;
            acDropdown.appendChild(grp);

            items.forEach(item => {
                const el = document.createElement('div');
                el.className = 'ac-item';
                el.innerHTML = `<i class="fas ${iconClass}"></i>`
                    + `<span>${highlight(item.label, q)}</span>`
                    + (item.sub ? `<span class="ac-sub">${item.sub}</span>` : '');
                el.addEventListener('mousedown', e => {
                    e.preventDefault();
                    selectSuggestion(item.label);
                });
                acDropdown.appendChild(el);
            });
        }

        renderGroup('Documents', titles,  'fa-file-alt');
        renderGroup('Authors',   authors, 'fa-user');
        acDropdown.classList.add('open');
    }

    function selectSuggestion(value) {
        searchInput.value = value;
        acDropdown.classList.remove('open');
        applyFilters();
        searchInput.focus();
    }

    // ── Keyboard navigation ──
    searchInput.addEventListener('keydown', e => {
        const items = acDropdown.querySelectorAll('.ac-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, items.length - 1);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
            items.forEach((el, i) => el.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'Enter') {
            if (activeIdx >= 0 && items[activeIdx]) {
                e.preventDefault();
                selectSuggestion(items[activeIdx].querySelector('span').textContent);
            } else {
                acDropdown.classList.remove('open');
                applyFilters();
            }
        } else if (e.key === 'Escape') {
            acDropdown.classList.remove('open');
        }
    });

    // Close dropdown on outside click
    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !acDropdown.contains(e.target)) {
            acDropdown.classList.remove('open');
        }
    });

    // ── Filter table rows ──
    function applyFilters() {
        const q    = searchInput.value.toLowerCase().trim();
        const cat  = categoryFilter.value;
        const auth = authorFilter.value;

        let visible = 0;
        rows.forEach(row => {
            const matchQ    = !q   || row.dataset.title.includes(q) || row.dataset.author.includes(q) || row.dataset.description.includes(q);
            const matchCat  = !cat  || row.dataset.category === cat;
            const matchAuth = !auth || row.dataset.author === auth.toLowerCase();
            const show = matchQ && matchCat && matchAuth;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        noResultsRow.style.display = visible === 0 ? '' : 'none';

        const total = rows.length;
        resultCount.textContent = visible < total
            ? `Showing ${visible} of ${total} documents`
            : `${total} document${total !== 1 ? 's' : ''}`;

        clearBtn.style.display = (q || cat || auth) ? 'inline-flex' : 'none';
    }

    window.clearFilters = function() {
        searchInput.value    = '';
        categoryFilter.value = '';
        authorFilter.value   = '';
        acDropdown.classList.remove('open');
        applyFilters();
    };

    searchInput.addEventListener('input', () => {
        renderDropdown(searchInput.value.trim());
        applyFilters();
    });
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim()) renderDropdown(searchInput.value.trim());
    });

    categoryFilter.addEventListener('change', applyFilters);
    authorFilter.addEventListener('change', applyFilters);

    applyFilters();
});
