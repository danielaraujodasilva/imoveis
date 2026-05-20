function showToast(message) {
    const el = document.getElementById('appToast');
    if (!el) return alert(message);
    el.querySelector('.toast-body').textContent = message;
    bootstrap.Toast.getOrCreateInstance(el).show();
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
    const score = Math.max(0, Math.min(100, Number(item.nota_oportunidade || 0)));
    return `<tr>
        <td><span class="score-pill" title="${score}% oportunidade">${score}%</span></td>
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
            if (!offset) tbody.innerHTML = '<tr><td colspan="10" class="text-muted">Nenhum imovel encontrado.</td></tr>';
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
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Rastreando...';
    try {
        const data = await postJson(appUrl('api/rodar_busca.php'), {});
        showToast(data.message || 'Rastreamento finalizado.');
        setTimeout(() => window.location.reload(), 900);
    } catch (error) {
        showToast(error.message);
    } finally {
        button.disabled = false;
        button.innerHTML = original;
    }
});

initPropertiesTable();
