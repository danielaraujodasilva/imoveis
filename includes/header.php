<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
$pageTitle = $pageTitle ?? app_setting('SITE_NAME', SITE_NAME);
require_login();
$isPublicPage = is_public_page();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="Radar Imoveis agrega oportunidades imobiliarias e ajuda a filtrar bons negocios.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="<?= $isPublicPage ? 'auth-shell' : 'app-shell' ?>">
    <?php if (!$isPublicPage) { require __DIR__ . '/sidebar.php'; } ?>
    <main class="<?= $isPublicPage ? 'auth-main' : 'app-main' ?>">
        <?php if (!$isPublicPage): ?>
        <div class="topbar d-flex align-items-center justify-content-between">
            <div>
                <span class="eyebrow">Radar Imoveis</span>
                <h1 class="h4 mb-0"><?= e($pageTitle) ?></h1>
            </div>
            <button class="btn btn-accent" id="runSearchBtn" type="button">
                <i class="bi bi-lightning-charge"></i> Rastrear agora
            </button>
        </div>
        <?php endif; ?>
