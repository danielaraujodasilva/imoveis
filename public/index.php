<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$stats = ['buscas' => 0, 'imoveis' => 0, 'interessantes' => 0, 'media' => 0];
$stmt = $pdo->prepare('SELECT COUNT(*) FROM buscas_imoveis WHERE user_id = ? AND ativa = 1');
$stmt->execute([$userId]);
$stats['buscas'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM imoveis WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['imoveis'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM imoveis WHERE user_id = ? AND status = 'interessante'");
$stmt->execute([$userId]);
$stats['interessantes'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COALESCE(AVG(nota_oportunidade), 0) FROM imoveis WHERE user_id = ?');
$stmt->execute([$userId]);
$stats['media'] = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT * FROM imoveis WHERE user_id = ? ORDER BY id DESC LIMIT 8');
$stmt->execute([$userId]);
$ultimos = $stmt->fetchAll();
?>
<section class="start-panel mb-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <span class="eyebrow">Rastreamento imobiliario</span>
            <h2 class="h3 mb-2">Oportunidades em um so painel</h2>
            <p class="text-muted mb-0">Cadastre buscas por cidade, bairro, faixa de preco e tipo de imovel. O radar agrega anuncios e deixa tudo filtravel.</p>
        </div>
        <a class="btn btn-accent btn-lg start-button" href="<?= url('configuracoes.php') ?>">
            <i class="bi bi-sliders"></i> Configurar busca
        </a>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Buscas ativas</div><div class="stat-value"><?= $stats['buscas'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Imoveis</div><div class="stat-value"><?= $stats['imoveis'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Interessantes</div><div class="stat-value"><?= $stats['interessantes'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted">Media</div><div class="stat-value"><?= $stats['media'] ?>%</div></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Ultimos imoveis encontrados</h2>
            <a href="<?= url('imoveis.php') ?>" class="btn btn-sm btn-outline-light">Ver todos</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Nota</th><th>Titulo</th><th>Preco</th><th>Local</th><th>Fonte</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($ultimos as $imovel): ?>
                    <tr>
                        <td><span class="score-pill"><?= (int)$imovel['nota_oportunidade'] ?>%</span></td>
                        <td><?= e($imovel['titulo']) ?></td>
                        <td><?= e($imovel['preco_texto'] ?: ($imovel['preco'] ? 'R$ ' . number_format((float)$imovel['preco'], 2, ',', '.') : '')) ?></td>
                        <td><?= e(trim(($imovel['bairro'] ? $imovel['bairro'] . ' - ' : '') . ($imovel['cidade'] ?? ''))) ?></td>
                        <td><?= e($imovel['fonte']) ?></td>
                        <td><span class="badge <?= badge_class($imovel['status']) ?>"><?= e($imovel['status']) ?></span></td>
                        <td><a class="btn btn-sm btn-outline-light" href="<?= url('imovel.php?id=' . (int)$imovel['id']) ?>">Detalhes</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$ultimos): ?><tr><td colspan="7" class="text-muted">Nenhum imovel ainda. Configure uma busca e clique em rastrear.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
