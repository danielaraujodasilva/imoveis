<?php
$pageTitle = 'Imoveis';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$params = [$userId];
$where = ['user_id = ?'];
foreach (['fonte', 'status', 'cidade', 'bairro', 'tipo_negocio', 'tipo_imovel'] as $field) {
    if (!empty($_GET[$field])) { $where[] = "$field = ?"; $params[] = $_GET[$field]; }
}
if (!empty($_GET['q'])) {
    $where[] = '(titulo LIKE ? OR anunciante LIKE ? OR descricao LIKE ? OR cidade LIKE ? OR bairro LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    array_push($params, $term, $term, $term, $term, $term);
}
foreach (['preco_min' => 'preco >= ?', 'preco_max' => 'preco <= ?', 'quartos_min' => 'quartos >= ?', 'area_min' => 'area_m2 >= ?', 'nota_min' => 'nota_oportunidade >= ?'] as $key => $sql) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') { $where[] = $sql; $params[] = (float)$_GET[$key]; }
}
$orderMap = ['nota' => 'nota_oportunidade', 'recentes' => 'id', 'preco' => 'preco', 'cidade' => 'cidade', 'bairro' => 'bairro', 'quartos' => 'quartos', 'area' => 'area_m2', 'fonte' => 'fonte', 'status' => 'status'];
$sortKey = $_GET['ordem'] ?? 'recentes';
$sortDir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderColumn = $orderMap[$sortKey] ?? 'id';
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM imoveis WHERE ' . implode(' AND ', $where));
$countStmt->execute($params);
$totalImoveis = (int)$countStmt->fetchColumn();

function distinct_options(PDO $pdo, int $userId, string $field): array
{
    $allowed = ['fonte', 'status', 'cidade', 'bairro', 'tipo_negocio', 'tipo_imovel'];
    if (!in_array($field, $allowed, true)) { return []; }
    $stmt = $pdo->prepare("SELECT DISTINCT {$field} AS value FROM imoveis WHERE user_id = ? AND {$field} IS NOT NULL AND {$field} <> '' ORDER BY {$field} ASC LIMIT 500");
    $stmt->execute([$userId]);
    return array_map('strval', array_column($stmt->fetchAll(), 'value'));
}

function filter_select(string $name, string $label, array $options): void
{
    $current = (string)($_GET[$name] ?? '');
    echo '<div class="col-md-2"><label class="form-label">' . e($label) . '</label><select name="' . e($name) . '" class="form-select">';
    echo '<option value="">Todos</option>';
    foreach ($options as $option) {
        echo '<option value="' . e($option) . '"' . ($current === $option ? ' selected' : '') . '>' . e($option) . '</option>';
    }
    echo '</select></div>';
}

function sort_link(string $key, string $label): string
{
    $params = $_GET;
    $params['ordem'] = $key;
    $active = ($_GET['ordem'] ?? 'recentes') === $key;
    $dir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $params['dir'] = $active && $dir === 'asc' ? 'desc' : 'asc';
    $icon = $active ? ($dir === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    return '<a class="link-light text-decoration-none" href="?' . http_build_query($params) . '">' . e($label) . $icon . '</a>';
}

$opcoes = [
    'fonte' => distinct_options($pdo, $userId, 'fonte'),
    'status' => distinct_options($pdo, $userId, 'status') ?: status_options(),
    'cidade' => distinct_options($pdo, $userId, 'cidade'),
    'bairro' => distinct_options($pdo, $userId, 'bairro'),
    'tipo_negocio' => distinct_options($pdo, $userId, 'tipo_negocio'),
    'tipo_imovel' => distinct_options($pdo, $userId, 'tipo_imovel'),
];
?>
<form class="card mb-4"><div class="card-body row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label">Texto livre</label><input name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>"></div>
    <?php filter_select('cidade', 'Cidade', $opcoes['cidade']); ?>
    <?php filter_select('bairro', 'Bairro', $opcoes['bairro']); ?>
    <?php filter_select('tipo_negocio', 'Negocio', $opcoes['tipo_negocio']); ?>
    <?php filter_select('tipo_imovel', 'Tipo', $opcoes['tipo_imovel']); ?>
    <?php filter_select('fonte', 'Fonte', $opcoes['fonte']); ?>
    <?php filter_select('status', 'Status', $opcoes['status']); ?>
    <div class="col-md-2"><label class="form-label">Preco min</label><input name="preco_min" type="number" class="form-control" value="<?= e($_GET['preco_min'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Preco max</label><input name="preco_max" type="number" class="form-control" value="<?= e($_GET['preco_max'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Quartos min</label><input name="quartos_min" type="number" class="form-control" value="<?= e($_GET['quartos_min'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Area min</label><input name="area_min" type="number" class="form-control" value="<?= e($_GET['area_min'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label">Ordenar</label><select name="ordem" class="form-select"><?php foreach (['recentes' => 'Mais recentes', 'nota' => 'Oportunidade', 'preco' => 'Preco', 'cidade' => 'Cidade', 'bairro' => 'Bairro', 'quartos' => 'Quartos', 'area' => 'Area', 'fonte' => 'Fonte', 'status' => 'Status'] as $k => $v): ?><option value="<?= $k ?>" <?= ($_GET['ordem'] ?? 'recentes') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><label class="form-label">Direcao</label><select name="dir" class="form-select"><option value="desc" <?= ($_GET['dir'] ?? 'desc') === 'desc' ? 'selected' : '' ?>>Decrescente</option><option value="asc" <?= ($_GET['dir'] ?? '') === 'asc' ? 'selected' : '' ?>>Crescente</option></select></div>
    <div class="col-md-2"><button class="btn btn-accent w-100" type="submit"><i class="bi bi-filter"></i> Filtrar</button></div>
    <div class="col-md-2"><a class="btn btn-outline-light w-100" href="imoveis.php"><i class="bi bi-x-circle"></i> Limpar</a></div>
</div></form>
<div class="card"><div class="card-body">
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="h5 mb-0">Resultados</h2>
    <span class="text-muted small"><span data-properties-loaded>0</span> de <span data-properties-total><?= $totalImoveis ?></span> imoveis exibidos.</span>
</div>
<div class="table-responsive"><table class="table align-middle" data-properties-table data-query="<?= e(http_build_query($_GET)) ?>">
<thead><tr><th><?= sort_link('nota', 'Nota') ?></th><th>Titulo</th><th><?= sort_link('preco', 'Preco') ?></th><th>Local</th><th>Tipo</th><th><?= sort_link('quartos', 'Quartos') ?></th><th><?= sort_link('area', 'Area') ?></th><th><?= sort_link('fonte', 'Fonte') ?></th><th><?= sort_link('status', 'Status') ?></th><th>Acoes</th></tr></thead>
<tbody data-properties-body>
<tr data-properties-empty><td colspan="10" class="text-muted">Carregando imoveis...</td></tr>
</tbody></table></div>
<div class="d-grid mt-3">
    <button class="btn btn-outline-light d-none" type="button" data-load-more-properties>Carregar mais imoveis</button>
    <div class="text-muted small text-center py-2 d-none" data-properties-loading>Carregando mais imoveis...</div>
    <div class="text-muted small text-center py-2 d-none" data-properties-end>Fim dos resultados.</div>
</div>
<div data-properties-sentinel></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
