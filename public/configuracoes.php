<?php
$pageTitle = 'Buscas';
require_once __DIR__ . '/../includes/header.php';

$userId = current_user_id();
$fontes = ['Mercado Livre', 'OLX', 'Zap Imoveis', 'Viva Real', 'Imovelweb', 'Chaves na Mao', 'QuintoAndar', 'Loft', 'Netimoveis', 'Lugar Certo', 'Wimoveis', 'Casa Mineira', 'Properati'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $acao = $_POST['acao'] ?? 'salvar';
    $id = (int)($_POST['id'] ?? 0);
    if ($acao === 'excluir' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM buscas_imoveis WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        header('Location: ' . url('configuracoes.php'));
        exit;
    }
    $payload = [
        trim((string)($_POST['nome'] ?? 'Busca principal')),
        trim((string)($_POST['termos'] ?? 'apartamento')),
        trim((string)($_POST['cidade'] ?? '')),
        trim((string)($_POST['bairro'] ?? '')),
        $_POST['tipo_negocio'] ?? 'venda',
        trim((string)($_POST['tipo_imovel'] ?? '')),
        $_POST['preco_min'] !== '' ? $_POST['preco_min'] : null,
        $_POST['preco_max'] !== '' ? $_POST['preco_max'] : null,
        $_POST['quartos_min'] !== '' ? $_POST['quartos_min'] : null,
        $_POST['area_min'] !== '' ? $_POST['area_min'] : null,
        trim((string)($_POST['palavras_obrigatorias'] ?? '')),
        trim((string)($_POST['palavras_proibidas'] ?? '')),
        json_encode(array_values($_POST['fontes'] ?? $fontes), JSON_UNESCAPED_UNICODE),
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE buscas_imoveis SET nome = ?, termos = ?, cidade = ?, bairro = ?, tipo_negocio = ?, tipo_imovel = ?, preco_min = ?, preco_max = ?, quartos_min = ?, area_min = ?, palavras_obrigatorias = ?, palavras_proibidas = ?, fontes = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([...$payload, $id, $userId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO buscas_imoveis (user_id, nome, termos, cidade, bairro, tipo_negocio, tipo_imovel, preco_min, preco_max, quartos_min, area_min, palavras_obrigatorias, palavras_proibidas, fontes, ativa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$userId, ...$payload]);
    }
    header('Location: ' . url('configuracoes.php'));
    exit;
}

$editBusca = null;
if (!empty($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM buscas_imoveis WHERE id = ? AND user_id = ?');
    $stmt->execute([(int)$_GET['editar'], $userId]);
    $editBusca = $stmt->fetch() ?: null;
}
$stmt = $pdo->prepare('SELECT * FROM buscas_imoveis WHERE user_id = ? ORDER BY id DESC');
$stmt->execute([$userId]);
$buscas = $stmt->fetchAll();
$selectedSources = $editBusca && $editBusca['fontes'] ? array_values(array_unique(array_merge(json_decode($editBusca['fontes'], true) ?: [], $fontes))) : $fontes;
?>
<div class="row g-4">
    <div class="col-lg-5">
        <form class="card" method="post">
            <div class="card-body">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)($editBusca['id'] ?? 0) ?>">
                <h2 class="h5 mb-3"><?= $editBusca ? 'Editar busca' : 'Nova busca' ?></h2>
                <label class="form-label">Nome</label><input name="nome" class="form-control mb-3" value="<?= e($editBusca['nome'] ?? 'Busca principal') ?>">
                <label class="form-label">Termos</label><textarea name="termos" class="form-control mb-3" rows="3"><?= e($editBusca['termos'] ?? "apartamento\ncasa") ?></textarea>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Cidade</label><input name="cidade" class="form-control" value="<?= e($editBusca['cidade'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Bairro</label><input name="bairro" class="form-control" value="<?= e($editBusca['bairro'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Negocio</label><select name="tipo_negocio" class="form-select"><option value="venda" <?= ($editBusca['tipo_negocio'] ?? 'venda') === 'venda' ? 'selected' : '' ?>>Venda</option><option value="aluguel" <?= ($editBusca['tipo_negocio'] ?? '') === 'aluguel' ? 'selected' : '' ?>>Aluguel</option></select></div>
                    <div class="col-md-6"><label class="form-label">Tipo</label><input name="tipo_imovel" class="form-control" placeholder="apartamento, casa..." value="<?= e($editBusca['tipo_imovel'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label">Preco min</label><input name="preco_min" type="number" class="form-control" value="<?= e((string)($editBusca['preco_min'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Preco max</label><input name="preco_max" type="number" class="form-control" value="<?= e((string)($editBusca['preco_max'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Quartos min</label><input name="quartos_min" type="number" class="form-control" value="<?= e((string)($editBusca['quartos_min'] ?? '')) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Area min</label><input name="area_min" type="number" class="form-control" value="<?= e((string)($editBusca['area_min'] ?? '')) ?>"></div>
                </div>
                <label class="form-label mt-3">Palavras obrigatorias</label><textarea name="palavras_obrigatorias" class="form-control mb-3" rows="2"><?= e($editBusca['palavras_obrigatorias'] ?? '') ?></textarea>
                <label class="form-label">Palavras proibidas</label><textarea name="palavras_proibidas" class="form-control mb-3" rows="2"><?= e($editBusca['palavras_proibidas'] ?? '') ?></textarea>
                <div class="mb-3">
                    <label class="form-label">Fontes</label>
                    <?php foreach ($fontes as $fonte): ?>
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="fontes[]" value="<?= e($fonte) ?>" <?= in_array($fonte, $selectedSources, true) ? 'checked' : '' ?>> <?= e($fonte) ?></label>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-accent" type="submit"><i class="bi bi-save"></i> Salvar busca</button>
            </div>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h2 class="h5 mb-3">Buscas salvas</h2>
            <div class="table-responsive"><table class="table align-middle">
                <thead><tr><th>Nome</th><th>Local</th><th>Faixa</th><th>Fontes</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($buscas as $busca): ?>
                    <tr>
                        <td><?= e($busca['nome']) ?></td>
                        <td><?= e(trim(($busca['bairro'] ? $busca['bairro'] . ' - ' : '') . ($busca['cidade'] ?? ''))) ?></td>
                        <td><?= e(($busca['preco_min'] ?: '0') . ' ate ' . ($busca['preco_max'] ?: 'sem teto')) ?></td>
                        <td><?= e(implode(', ', json_decode($busca['fontes'] ?: '[]', true) ?: [])) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-light" href="?editar=<?= (int)$busca['id'] ?>"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Excluir esta busca? Os imoveis ja salvos continuam no sistema.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int)$busca['id'] ?>"><button class="btn btn-sm btn-danger" title="Excluir"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$buscas): ?><tr><td colspan="5" class="text-muted">Nenhuma busca configurada ainda.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
