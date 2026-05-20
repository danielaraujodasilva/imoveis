<?php
$pageTitle = 'Contato';
require_once __DIR__ . '/../includes/header.php';
$email = app_setting('SITE_CONTACT_EMAIL');
?>
<div class="auth-card legal-card">
    <span class="brand-mark">P</span>
    <h1 class="h4 mt-3">Contato</h1>
    <p class="text-muted">Use este canal para falar sobre privacidade, sugestoes, problemas no site ou parcerias.</p>

    <?php if ($email): ?>
        <a class="btn btn-accent w-100" href="mailto:<?= e($email) ?>"><i class="bi bi-envelope"></i> <?= e($email) ?></a>
    <?php else: ?>
        <div class="alert alert-info mb-0">Configure <code>SITE_CONTACT_EMAIL</code> em <code>config/local.php</code> para exibir um e-mail de contato publico.</div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
