function showToast(message) {
    const el = document.getElementById('appToast');
    if (!el) return alert(message);
    el.querySelector('.toast-body').textContent = message;
    bootstrap.Toast.getOrCreateInstance(el).show();
}

function renderSourceWarning() {
    const raw = sessionStorage.getItem('imoveisSourceWarnings');
    if (!raw) return;
    sessionStorage.removeItem('imoveisSourceWarnings');
    let warnings = [];
    try {
        warnings = JSON.parse(raw);
    } catch {
        warnings = [];
    }
    if (!Array.isArray(warnings) || !warnings.length) return;
    const main = document.querySelector('.app-main');
    const topbar = document.querySelector('.topbar');
    if (!main || !topbar) return;
    const count = warnings.length;
    const preview = warnings.slice(0, 4).map(escapeHtml).join('<br>');
    const extra = count > 4 ? `<br><span class="text-muted">+${count - 4} outros alertas registrados no log.</span>` : '';
    topbar.insertAdjacentHTML('afterend', `
        <div class="alert alert-warning source-warning-alert" role="alert" data-source-warning>
            <div>
                <strong>${count} fonte(s) nao responderam ou vieram sem resultado.</strong>
                <div class="small mt-1">${preview}${extra}</div>
            </div>
            <button type="button" class="btn-close" aria-label="Fechar" data-source-warning-close></button>
        </div>
    `);
}

const searchProgressSteps = [
    { at: 6, text: 'Preparando as buscas salvas e conectando ao banco...' },
    { at: 14, text: 'Lendo termos, cidade, bairro e filtros de preco...' },
    { at: 24, text: 'Consultando fontes abertas e sitemaps imobiliarios...' },
    { at: 38, text: 'Filtrando anuncios por tipo, localizacao e palavras-chave...' },
    { at: 52, text: 'Comparando precos, quartos e metragem com suas preferencias...' },
    { at: 68, text: 'Salvando oportunidades novas e ignorando duplicadas...' },
    { at: 82, text: 'Registrando alertas de fontes que nao responderam...' },
    { at: 93, text: 'Finalizando a lista para atualizar a tela...' }
];

function ensureSearchProgress() {
    let el = document.querySelector('[data-search-progress]');
    if (el) return el;
    document.body.insertAdjacentHTML('beforeend', `
        <div class="search-progress d-none" data-search-progress role="status" aria-live="polite">
            <div class="search-progress-head">
                <strong>Rastreando imoveis</strong>
                <span data-search-progress-percent>0%</span>
            </div>
            <div class="search-progress-bar" aria-hidden="true">
                <span data-search-progress-bar></span>
            </div>
            <p data-search-progress-text>Preparando busca...</p>
        </div>
    `);
    return document.querySelector('[data-search-progress]');
}

function startSearchProgress() {
    const el = ensureSearchProgress();
    const bar = el.querySelector('[data-search-progress-bar]');
    const percentEl = el.querySelector('[data-search-progress-percent]');
    const textEl = el.querySelector('[data-search-progress-text]');
    let percent = 3;
    let stepIndex = 0;

    function render(value, text) {
        const next = Math.max(0, Math.min(100, Math.round(value)));
        percent = next;
        bar.style.width = `${next}%`;
        percentEl.textContent = `${next}%`;
        if (text) textEl.textContent = text;
    }

    el.classList.remove('d-none');
    render(percent, searchProgressSteps[0].text);

    const timer = setInterval(() => {
        const targetStep = searchProgressSteps[stepIndex + 1];
        if (targetStep && percent >= targetStep.at) {
            stepIndex += 1;
            render(percent, searchProgressSteps[stepIndex].text);
            return;
        }
        const ceiling = searchProgressSteps[Math.min(stepIndex + 1, searchProgressSteps.length - 1)].at;
        const increment = percent < 30 ? 3 : percent < 70 ? 2 : 1;
        render(Math.min(percent + increment, Math.max(ceiling - 1, 95)));
    }, 850);

    return {
        finish(message = 'Busca concluida. Atualizando resultados...') {
            clearInterval(timer);
            render(100, message);
            setTimeout(() => el.classList.add('d-none'), 1000);
        },
        fail(message = 'Nao foi possivel concluir a busca agora.') {
            clearInterval(timer);
            el.classList.add('is-error');
            render(Math.max(percent, 100), message);
            setTimeout(() => {
                el.classList.add('d-none');
                el.classList.remove('is-error');
                render(0, 'Preparando busca...');
            }, 2400);
        }
    };
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload || {})
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'Falha na requisicao');
    return data;
}

function appUrl(path) {
    return `${window.IMOVEIS_BASE_URL || ''}${path.replace(/^\/+/, '')}`;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function statusBadgeClass(status) {
    return {
        novo: 'text-bg-secondary',
        interessante: 'text-bg-info',
        contatado: 'text-bg-primary',
        visita: 'text-bg-success',
        descartado: 'text-bg-danger',
        arquivado: 'text-bg-secondary'
    }[status] || 'text-bg-warning';
}

function money(value, fallback) {
    const number = Number(value || 0);
    if (!number) return escapeHtml(fallback || '');
    return number.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function propertyRow(item) {
    const id = Number(item.id || 0);
    return `<tr>
        <td>${escapeHtml(item.titulo)}</td>
        <td>${money(item.preco, item.preco_texto)}</td>
        <td>${escapeHtml([item.bairro, item.cidade].filter(Boolean).join(' - '))}</td>
        <td>${escapeHtml(item.tipo_imovel || '')}</td>
        <td>${escapeHtml(item.quartos || '')}</td>
        <td>${escapeHtml(item.area_m2 || '')}</td>
        <td>${escapeHtml(item.fonte)}</td>
        <td><span class="badge ${statusBadgeClass(item.status)}">${escapeHtml(item.status)}</span></td>
        <td class="text-nowrap">
            <a class="btn btn-sm btn-outline-light small-action" href="${appUrl(`imovel.php?id=${id}`)}"><i class="bi bi-eye"></i></a>
            <button class="btn btn-sm btn-outline-info small-action" data-status-id="${id}" data-status="interessante"><i class="bi bi-star"></i></button>
            <button class="btn btn-sm btn-outline-primary small-action" data-status-id="${id}" data-status="contatado"><i class="bi bi-telephone"></i></button>
            <button class="btn btn-sm btn-outline-secondary small-action" data-status-id="${id}" data-status="arquivado"><i class="bi bi-archive"></i></button>
        </td>
    </tr>`;
}

function initPropertiesTable() {
    const table = document.querySelector('[data-properties-table]');
    if (!table) return;

    const tbody = document.querySelector('[data-properties-body]');
    const emptyRow = document.querySelector('[data-properties-empty]');
    const loadedEl = document.querySelector('[data-properties-loaded]');
    const totalEl = document.querySelector('[data-properties-total]');
    const loadingEl = document.querySelector('[data-properties-loading]');
    const endEl = document.querySelector('[data-properties-end]');
    const loadMoreBtn = document.querySelector('[data-load-more-properties]');
    const sentinel = document.querySelector('[data-properties-sentinel]');
    const limit = 50;
    let offset = 0;
    let loading = false;
    let hasMore = true;

    async function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        loadingEl?.classList.remove('d-none');
        loadMoreBtn?.classList.add('d-none');
        const params = new URLSearchParams(table.dataset.query || window.location.search);
        params.set('limit', String(limit));
        params.set('offset', String(offset));

        try {
            const response = await fetch(appUrl(`api/listar_imoveis.php?${params.toString()}`));
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.error || 'Falha ao carregar imoveis');
            emptyRow?.remove();
            tbody.insertAdjacentHTML('beforeend', data.data.map(propertyRow).join(''));
            offset += data.data.length;
            hasMore = Boolean(data.has_more);
            if (loadedEl) loadedEl.textContent = String(offset);
            if (totalEl) totalEl.textContent = String(data.total);
            if (!offset) tbody.innerHTML = '<tr><td colspan="9" class="text-muted">Nenhum imovel encontrado.</td></tr>';
            endEl?.classList.toggle('d-none', hasMore || !offset);
            loadMoreBtn?.classList.toggle('d-none', !hasMore);
        } catch (error) {
            showToast(error.message);
            loadMoreBtn?.classList.remove('d-none');
        } finally {
            loading = false;
            loadingEl?.classList.add('d-none');
        }
    }

    loadMoreBtn?.addEventListener('click', loadMore);
    if ('IntersectionObserver' in window && sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) loadMore();
        }, { rootMargin: '500px 0px' });
        observer.observe(sentinel);
    }
    loadMore();
}

document.addEventListener('click', async (event) => {
    const closeWarning = event.target.closest('[data-source-warning-close]');
    if (closeWarning) {
        closeWarning.closest('[data-source-warning]')?.remove();
        return;
    }

    const statusButton = event.target.closest('[data-status-id]');
    if (!statusButton) return;
    statusButton.disabled = true;
    try {
        await postJson(appUrl('api/atualizar_status_imovel.php'), {
            id: statusButton.dataset.statusId,
            status: statusButton.dataset.status
        });
        showToast('Status atualizado.');
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        showToast(error.message);
        statusButton.disabled = false;
    }
});

document.getElementById('runSearchBtn')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const original = button.innerHTML;
    const progress = startSearchProgress();
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Rastreando...';
    try {
        const data = await postJson(appUrl('api/rodar_busca.php'), {});
        progress.finish('Busca concluida. Atualizando resultados...');
        if (Array.isArray(data.warnings) && data.warnings.length) {
            sessionStorage.setItem('imoveisSourceWarnings', JSON.stringify(data.warnings));
        }
        showToast(data.message || 'Rastreamento finalizado.');
        setTimeout(() => window.location.reload(), 1200);
    } catch (error) {
        progress.fail(error.message);
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

initPropertiesTable();
renderSourceWarning();
