<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (current_user_id() > 0) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');
    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($senha) < 6) {
        $error = 'Preencha nome, e-mail valido e senha com pelo menos 6 caracteres.';
    } else {
        $total = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        try {
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $userId = (int)$pdo->lastInsertId();
            if ($total === 0) {
                claim_legacy_rows($pdo, $userId);
            }
            header('Location: ' . url('login.php?criado=1'));
            exit;
        } catch (PDOException $e) {
            $error = 'Nao foi possivel criar a conta. Talvez esse e-mail ja exista.';
        }
    }
}
$pageTitle = 'Criar conta';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <div class="mb-4">
        <span class="brand-mark">P</span>
        <h1 class="h4 mt-3 mb-1">Criar conta</h1>
        <p class="text-muted mb-0">Cada pessoa tera suas proprias buscas, buscas e status de imoveis.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label class="form-label">Nome</label>
        <input class="form-control mb-3" name="nome" required autocomplete="name" value="<?= e($_POST['nome'] ?? '') ?>">
        <label class="form-label">E-mail</label>
        <input class="form-control mb-3" type="email" name="email" required autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
        <label class="form-label">Senha</label>
        <input class="form-control mb-3" type="password" name="senha" required minlength="6" autocomplete="new-password">
        <button class="btn btn-accent w-100" type="submit"><i class="bi bi-person-plus"></i> Criar conta</button>
    </form>
    <p class="text-muted small mt-3 mb-0">Ja tem conta? <a href="<?= url('login.php') ?>">Entrar</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
