<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="sidebar">
    <a class="brand" href="<?= url('index.php') ?>">
        <span class="brand-mark">R</span>
        <span>Radar Imoveis</span>
    </a>
    <nav class="nav flex-column gap-1">
        <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>" href="<?= url('index.php') ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link <?= $current === 'configuracoes.php' ? 'active' : '' ?>" href="<?= url('configuracoes.php') ?>"><i class="bi bi-sliders"></i> Buscas</a>
        <a class="nav-link <?= in_array($current, ['imoveis.php', 'imovel.php'], true) ? 'active' : '' ?>" href="<?= url('imoveis.php') ?>"><i class="bi bi-houses"></i> Imoveis</a>
        <a class="nav-link <?= $current === 'apoie.php' ? 'active' : '' ?>" href="<?= url('apoie.php') ?>"><i class="bi bi-heart"></i> Apoie</a>
        <?php if (is_admin_user()): ?>
        <a class="nav-link <?= $current === 'monetizacao.php' ? 'active' : '' ?>" href="<?= url('monetizacao.php') ?>"><i class="bi bi-cash-coin"></i> Monetizacao</a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-user">
        <div class="small text-muted">Logado como</div>
        <div class="fw-semibold"><?= e(current_user()['nome'] ?? 'Usuario') ?></div>
        <a class="btn btn-sm btn-outline-light w-100 mt-2" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </div>
</aside>
