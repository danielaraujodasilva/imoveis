<?php
$pageTitle = 'Apoie';
require_once __DIR__ . '/../includes/header.php';
$hasDonationUrl = feature_enabled('DONATION_URL');
$hasPix = feature_enabled('DONATION_PIX_KEY');
?>
<div class="auth-card legal-card">
    <span class="brand-mark">P</span>
    <h1 class="h4 mt-3">Apoie o Radar Imoveis</h1>
    <p class="text-muted">A Radar Imoveis e gratuito. Apoios ajudam a pagar hospedagem, manutencao e novas fontes de imoveis.</p>

<?php if ($hasDonationUrl): ?>
        <a class="btn btn-accent w-100 mb-3" target="_blank" rel="noopener" href="<?= e(app_setting('DONATION_URL')) ?>"><i class="bi bi-heart"></i> Apoiar o projeto</a>
    <?php endif; ?>

    <?php if ($hasPix): ?>
        <label class="form-label">Chave Pix</label>
        <code class="support-code d-block mb-3"><?= e(app_setting('DONATION_PIX_KEY')) ?></code>
    <?php endif; ?>

    <?php if (!$hasDonationUrl && !$hasPix): ?>
        <div class="alert alert-info mb-0">Configure <code>DONATION_URL</code> ou <code>DONATION_PIX_KEY</code> em <code>config/local.php</code> para ativar esta pagina.</div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
