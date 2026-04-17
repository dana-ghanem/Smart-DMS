/**
 * ai-search.js
 * ============
 * Handles AI-powered semantic search via /ui-api/search.
 *
 * How to use:
 *   import AiSearch from './ai-search';   (or it auto-inits via window.AiSearch)
 *
 * What it does:
 *   - Watches the existing #searchInput for an "AI Search" trigger (Enter key
 *     or the AI button we inject next to the search box)
 *   - POSTs to /ui-api/search with the query
 *   - Hides the regular document table and shows ranked AI results panel
 *   - "Clear" or empty query restores the original table
 */

export default class AiSearch {

    constructor(options = {}) {
        this.apiUrl    = options.apiUrl    || '/ui-api/search';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.topK      = options.topK      || 10;
        this.minScore  = options.minScore  || 0.0;

        // DOM references (set in init)
        this.searchInput  = null;
        this.tableWrap    = null;
        this.resultsPanel = null;
        this.aiBtn        = null;
        this.statusBadge  = null;

        this._debounceTimer = null;
        this._lastQuery     = '';
        this._active        = false;   // true while AI results are showing
    }

    // ─────────────────────────────────────────────────────────────────
    // Bootstrap
    // ─────────────────────────────────────────────────────────────────

    init() {
        this.searchInput = document.getElementById('searchInput');
        this.tableWrap   = document.querySelector('.table-wrap');

        if (!this.searchInput || !this.tableWrap) return; // guard

        this._injectUI();
        this._bindEvents();
        this._checkHealth();
    }

    // ─────────────────────────────────────────────────────────────────
    // Inject AI button + results panel into the DOM
    // ─────────────────────────────────────────────────────────────────

    _injectUI() {
        // ── AI Search button (injected right after the search input wrap) ──
        const searchWrap = this.searchInput.closest('.search-wrap') || this.searchInput.parentElement;

        this.aiBtn = document.createElement('button');
        this.aiBtn.id        = 'aiSearchBtn';
        this.aiBtn.type      = 'button';
        this.aiBtn.className = 'ai-search-btn';
        this.aiBtn.innerHTML = `
            <span class="ai-btn-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </span>
            <span class="ai-btn-label">AI Search</span>
            <span class="ai-btn-badge" id="aiBadge" style="display:none;">
                <span class="ai-badge-dot"></span>
            </span>
        `;
        searchWrap.after(this.aiBtn);

        this.statusBadge = document.getElementById('aiBadge');

        // ── Results panel (injected right before the table) ──
        this.resultsPanel = document.createElement('div');
        this.resultsPanel.id        = 'aiResultsPanel';
        this.resultsPanel.className = 'ai-results-panel';
        this.resultsPanel.style.display = 'none';
        this.tableWrap.before(this.resultsPanel);

        // ── Inject styles ──
        this._injectStyles();
    }

    // ─────────────────────────────────────────────────────────────────
    // Event wiring
    // ─────────────────────────────────────────────────────────────────

    _bindEvents() {
        // Click AI button
        this.aiBtn.addEventListener('click', () => {
            const q = this.searchInput.value.trim();
            if (q) this._run(q);
        });

        // Enter key in search box triggers AI search
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = this.searchInput.value.trim();
                if (q) this._run(q);
                else   this._clearResults();
            }
        });

        // If user clears the input, restore the table
        this.searchInput.addEventListener('input', () => {
            if (this.searchInput.value.trim() === '' && this._active) {
                this._clearResults();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // Health check — dim the button if Python is down
    // ─────────────────────────────────────────────────────────────────

    async _checkHealth() {
        try {
            const r = await fetch('/ui-api/ai-health', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken },
                credentials: 'include',
            });
            const d = await r.json();
            const pythonOk = d?.python?.status === 'healthy';

            if (pythonOk) {
                this.aiBtn.title = 'AI search is ready';
                if (this.statusBadge) this.statusBadge.style.display = 'inline-flex';
            } else {
                this.aiBtn.classList.add('ai-btn-offline');
                this.aiBtn.title = 'AI service offline — using local filter only';
            }
        } catch (_) {
            this.aiBtn.classList.add('ai-btn-offline');
            this.aiBtn.title = 'AI service unreachable';
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Core search
    // ─────────────────────────────────────────────────────────────────

    async _run(query) {
        if (query === this._lastQuery && this._active) return;
        this._lastQuery = query;

        this._showLoading(query);

        try {
            const response = await fetch(this.apiUrl, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                credentials: 'include',
                body: JSON.stringify({
                    query:     query,
                    top_k:     this.topK,
                    min_score: this.minScore,
                }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                this._showError(data?.error || data?.message || `HTTP ${response.status}`);
                return;
            }

            this._showResults(query, data.results || [], data.execution_time);

        } catch (err) {
            this._showError(err.message || 'Network error');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Render states
    // ─────────────────────────────────────────────────────────────────

    _showLoading(query) {
        this._active = true;
        this.tableWrap.style.display = 'none';
        this.resultsPanel.style.display = 'block';
        this.resultsPanel.innerHTML = `
            <div class="ai-results-header">
                <div class="ai-header-left">
                    <span class="ai-icon-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 6v6l4 2"/>
                        </svg>
                    </span>
                    <span class="ai-header-title">AI Search</span>
                    <span class="ai-query-chip">${this._escape(query)}</span>
                </div>
                <button class="ai-close-btn" onclick="window.__aiSearch?._clearResults()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Back to list
                </button>
            </div>
            <div class="ai-loading">
                <div class="ai-spinner"></div>
                <span>Ranking documents by relevance…</span>
            </div>
        `;
    }

    _showResults(query, results, execTime) {
        const count = results.length;
        const timeStr = execTime ? ` · ${(execTime * 1000).toFixed(0)}ms` : '';

        const cards = count === 0
            ? `<div class="ai-empty">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".35">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <p>No documents matched <strong>${this._escape(query)}</strong></p>
                    <p class="ai-empty-sub">Try different keywords or upload more documents.</p>
               </div>`
            : results.map((r, i) => this._cardHTML(r, i)).join('');

        this.resultsPanel.innerHTML = `
            <div class="ai-results-header">
                <div class="ai-header-left">
                    <span class="ai-icon-wrap">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9.663 17h4.673M12 3v1m6.364 1.636-.707.707M21 12h-1M4 12H3m3.343-5.657-.707-.707m2.828 9.9a5 5 0 1 1 7.072 0l-.548.547A3.374 3.374 0 0 0 14 18.469V19a2 2 0 1 1-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </span>
                    <span class="ai-header-title">AI Search</span>
                    <span class="ai-query-chip">${this._escape(query)}</span>
                    <span class="ai-result-meta">${count} result${count !== 1 ? 's' : ''}${timeStr}</span>
                </div>
                <button class="ai-close-btn" onclick="window.__aiSearch?._clearResults()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Back to list
                </button>
            </div>
            <div class="ai-cards">${cards}</div>
        `;
    }

    _showError(message) {
        this.resultsPanel.innerHTML = `
            <div class="ai-results-header">
                <div class="ai-header-left">
                    <span class="ai-icon-wrap ai-icon-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
                        </svg>
                    </span>
                    <span class="ai-header-title">Search Error</span>
                </div>
                <button class="ai-close-btn" onclick="window.__aiSearch?._clearResults()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                    Back to list
                </button>
            </div>
            <div class="ai-error">
                <p>${this._escape(message)}</p>
                <p class="ai-empty-sub">Make sure the AI service is running, then try again.</p>
            </div>
        `;
    }

    // ─────────────────────────────────────────────────────────────────
    // Result card HTML
    // ─────────────────────────────────────────────────────────────────

    _cardHTML(result, index) {
        const score      = result.score ?? 0;
        const pct        = Math.round(score * 100);
        const rankLabel  = index === 0 ? 'Top match' : `#${index + 1}`;
        const title      = result.title      || result.document || `Document #${result.document_id}`;
        const author     = result.author     || '—';
        const category   = result.category   || '';
        const excerpt    = result.content    || result.description || '';
        const docId      = result.document_id;
        const filePath   = result.file_path  || '';

        // Score bar colour: green → amber → red
        const barColor = pct >= 60 ? '#22c55e' : pct >= 30 ? '#f59e0b' : '#94a3b8';

        return `
        <div class="ai-card" style="--rank-delay:${index * 60}ms">
            <div class="ai-card-rank ${index === 0 ? 'ai-rank-top' : ''}">${rankLabel}</div>

            <div class="ai-card-body">
                <div class="ai-card-title">${this._escape(title)}</div>

                <div class="ai-card-meta">
                    ${author !== '—' ? `<span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>${this._escape(author)}</span>` : ''}
                    ${category          ? `<span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 7 10 10M7 17 17 7"/></svg>${this._escape(category)}</span>` : ''}
                </div>

                ${excerpt ? `<p class="ai-card-excerpt">${this._escape(excerpt)}</p>` : ''}
            </div>

            <div class="ai-card-side">
                <div class="ai-score-wrap">
                    <div class="ai-score-pct" style="color:${barColor}">${pct}%</div>
                    <div class="ai-score-label">relevance</div>
                    <div class="ai-score-bar">
                        <div class="ai-score-fill" style="width:${pct}%;background:${barColor}"></div>
                    </div>
                </div>

                <div class="ai-card-actions">
                    ${docId ? `<a href="/documents/${docId}" class="ai-action-btn ai-action-view">View</a>` : ''}
                    ${docId ? `<a href="/documents/${docId}/edit" class="ai-action-btn ai-action-edit">Edit</a>` : ''}
                    ${filePath ? `<a href="/storage/${filePath}" target="_blank" class="ai-action-btn ai-action-file">File</a>` : ''}
                </div>
            </div>
        </div>`;
    }

    // ─────────────────────────────────────────────────────────────────
    // Clear — restore original table
    // ─────────────────────────────────────────────────────────────────

    _clearResults() {
        this._active    = false;
        this._lastQuery = '';
        this.resultsPanel.style.display = 'none';
        this.tableWrap.style.display    = '';
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    _escape(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ─────────────────────────────────────────────────────────────────
    // Styles (scoped, injected once)
    // ─────────────────────────────────────────────────────────────────

    _injectStyles() {
        if (document.getElementById('ai-search-styles')) return;

        const style = document.createElement('style');
        style.id = 'ai-search-styles';
        style.textContent = `
        /* ── AI Search Button ─────────────────────── */
        .ai-search-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 0 16px;
            height: 38px;
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
            position: relative;
        }
        .ai-search-btn:hover {
            border-color: #6366f1;
            color: #6366f1;
            background: #f5f3ff;
        }
        .ai-search-btn.ai-btn-offline {
            opacity: .5;
            cursor: not-allowed;
        }
        .ai-btn-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ai-badge-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 2px #dcfce7;
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 2px #dcfce7; }
            50%       { box-shadow: 0 0 0 4px #bbf7d0; }
        }

        /* ── Results panel ────────────────────────── */
        .ai-results-panel {
            margin-bottom: 20px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .ai-results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 8px;
        }
        .ai-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ai-icon-wrap {
            width: 28px; height: 28px;
            border-radius: 7px;
            background: #6366f1;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .ai-icon-error { background: #ef4444; }
        .ai-header-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: .01em;
        }
        .ai-query-chip {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 12px;
            font-weight: 500;
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .ai-result-meta {
            font-size: 12px;
            color: #94a3b8;
        }
        .ai-close-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 7px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
        }
        .ai-close-btn:hover { border-color: #94a3b8; color: #1e293b; }

        /* ── Loading ──────────────────────────────── */
        .ai-loading {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 40px 24px;
            color: #64748b;
            font-size: 14px;
        }
        .ai-spinner {
            width: 22px; height: 22px;
            border: 2.5px solid #e2e8f0;
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Cards ────────────────────────────────── */
        .ai-cards {
            padding: 12px 16px 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .ai-card {
            display: flex;
            align-items: stretch;
            gap: 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
            transition: box-shadow .18s, border-color .18s;
            animation: card-in .3s ease both;
            animation-delay: var(--rank-delay, 0ms);
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .ai-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 2px 12px rgba(99,102,241,.1);
        }
        .ai-card-rank {
            writing-mode: vertical-lr;
            text-orientation: mixed;
            transform: rotate(180deg);
            padding: 12px 9px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #94a3b8;
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .ai-rank-top {
            background: #ede9fe;
            color: #6366f1;
            border-right-color: #c7d2fe;
        }
        .ai-card-body {
            flex: 1;
            padding: 14px 16px;
            min-width: 0;
        }
        .ai-card-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .ai-card-meta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 7px;
        }
        .ai-card-meta span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            color: #64748b;
        }
        .ai-card-excerpt {
            font-size: 12.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.55;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .ai-card-side {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            padding: 14px 16px;
            border-left: 1px solid #f1f5f9;
            gap: 10px;
            flex-shrink: 0;
        }
        .ai-score-wrap { text-align: center; }
        .ai-score-pct {
            font-size: 20px;
            font-weight: 700;
            line-height: 1;
        }
        .ai-score-label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-top: 2px;
        }
        .ai-score-bar {
            width: 64px;
            height: 4px;
            background: #f1f5f9;
            border-radius: 4px;
            margin-top: 6px;
            overflow: hidden;
        }
        .ai-score-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .4s ease;
        }
        .ai-card-actions {
            display: flex;
            gap: 5px;
        }
        .ai-action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all .15s;
        }
        .ai-action-view  { background:#ede9fe; color:#6366f1; border-color:#c7d2fe; }
        .ai-action-view:hover  { background:#6366f1; color:#fff; }
        .ai-action-edit  { background:#fef9c3; color:#a16207; border-color:#fde68a; }
        .ai-action-edit:hover  { background:#f59e0b; color:#fff; }
        .ai-action-file  { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
        .ai-action-file:hover  { background:#22c55e; color:#fff; }

        /* ── Empty / Error ────────────────────────── */
        .ai-empty, .ai-error {
            text-align: center;
            padding: 48px 24px;
            color: #94a3b8;
        }
        .ai-empty p, .ai-error p { margin: 6px 0; font-size: 14px; }
        .ai-empty strong { color: #475569; }
        .ai-empty-sub { font-size: 12.5px !important; }
        .ai-error p { color: #ef4444; }
        `;

        document.head.appendChild(style);
    }
}

// Auto-init and expose globally so blade onclick handlers can reach it
window.addEventListener('DOMContentLoaded', () => {
    const ai = new AiSearch();
    ai.init();
    window.__aiSearch = ai;
});

window.AiSearch = AiSearch;
