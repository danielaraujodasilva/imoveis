<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (current_user_id() > 0) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $senha = (string)($_POST['senha'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        $_SESSION['usuario'] = ['id' => (int)$usuario['id'], 'nome' => $usuario['nome'], 'email' => $usuario['email']];
        header('Location: ' . url('index.php'));
        exit;
    }
    $error = 'E-mail ou senha invalidos.';
}
$pageTitle = 'Entrar';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-card">
    <div class="mb-4">
        <span class="brand-mark">R</span>
        <h1 class="h4 mt-3 mb-1">Entrar no Radar Imoveis</h1>
        <p class="text-muted mb-0">Suas buscas e imoveis ficam separados por login.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['criado'])): ?><div class="alert alert-success">Conta criada. Pode entrar.</div><?php endif; ?>
    <form method="post">
        <label class="form-label">E-mail</label>
        <input class="form-control mb-3" type="email" name="email" required autocomplete="email">
        <label class="form-label">Senha</label>
        <input class="form-control mb-3" type="password" name="senha" required autocomplete="current-password">
        <button class="btn btn-accent w-100" type="submit"><i class="bi bi-box-arrow-in-right"></i> Entrar</button>
    </form>
    <p class="text-muted small mt-3 mb-0">Ainda nao tem conta? <a href="<?= url('register.php') ?>">Criar cadastro</a></p>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
