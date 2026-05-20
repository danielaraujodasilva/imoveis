<?php
$pageTitle = 'Detalhe do imovel';
require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare('SELECT * FROM imoveis WHERE id = ? AND user_id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0), current_user_id()]);
$imovel = $stmt->fetch();
?>
<?php if (!$imovel): ?>
    <div class="alert alert-warning">Imovel nao encontrado.</div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
                <div>
                    <h2 class="h4"><?= e($imovel['titulo']) ?></h2>
                    <div class="text-muted"><?= e(trim(($imovel['bairro'] ? $imovel['bairro'] . ' - ' : '') . ($imovel['cidade'] ?? ''))) ?></div>
                </div>
                <span class="score-pill"><?= (int)$imovel['nota_oportunidade'] ?>%</span>
            </div>
            <p>
                <span class="badge <?= badge_class($imovel['status']) ?>"><?= e($imovel['status']) ?></span>
                <span class="badge text-bg-dark border"><?= e($imovel['fonte']) ?></span>
                <span class="text-muted"><?= e($imovel['preco_texto'] ?: ($imovel['preco'] ? 'R$ ' . number_format((float)$imovel['preco'], 2, ',', '.') : '')) ?></span>
            </p>
            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="text-muted small">Quartos</div><strong><?= e((string)$imovel['quartos']) ?></strong></div>
                <div class="col-md-3"><div class="text-muted small">Banheiros</div><strong><?= e((string)$imovel['banheiros']) ?></strong></div>
                <div class="col-md-3"><div class="text-muted small">Garagem</div><strong><?= e((string)$imovel['vagas_garagem']) ?></strong></div>
                <div class="col-md-3"><div class="text-muted small">Area</div><strong><?= e((string)$imovel['area_m2']) ?> m2</strong></div>
            </div>
            <p><a class="btn btn-accent" target="_blank" rel="noopener" href="<?= e($imovel['url']) ?>"><i class="bi bi-box-arrow-up-right"></i> Abrir anuncio original</a></p>
            <h3 class="h6 mt-4">Descricao</h3>
            <div class="description-box"><?= nl2br(e(strip_tags((string)$imovel['descricao']))) ?></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h3 class="h6">Oportunidade</h3>
            <p class="text-muted"><?= nl2br(e($imovel['resumo_oportunidade'])) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (status_options() as $s): ?><button class="btn btn-sm btn-outline-light" data-status-id="<?= (int)$imovel['id'] ?>" data-status="<?= $s ?>"><?= $s ?></button><?php endforeach; ?>
            </div>
        </div></div>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
