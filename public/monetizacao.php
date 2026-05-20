<?php
$pageTitle = 'Monetizacao';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$settingsFields = [
    'ADMIN_EMAIL' => [
        'label' => 'E-mail administrador',
        'type' => 'email',
        'help' => 'Somente este e-mail ve e altera esta tela.',
        'placeholder' => 'danielaraujodasilva@gmail.com',
    ],
    'SITE_CONTACT_EMAIL' => [
        'label' => 'E-mail de contato publico',
        'type' => 'email',
        'help' => 'Aparece nas paginas de contato e privacidade.',
        'placeholder' => 'contato@seudominio.com',
    ],
    'SITE_PUBLIC_URL' => [
        'label' => 'URL publica',
        'type' => 'url',
        'help' => 'Endereco principal usado para referencia e cadastros externos.',
        'placeholder' => 'https://danieltatuador.com/imoveis',
    ],
    'GOOGLE_ANALYTICS_ID' => [
        'label' => 'Google Analytics',
        'type' => 'text',
        'help' => 'ID do GA4, geralmente no formato G-XXXXXXXXXX.',
        'placeholder' => 'G-XXXXXXXXXX',
    ],
    'GOOGLE_ADSENSE_CLIENT' => [
        'label' => 'AdSense client',
        'type' => 'text',
        'help' => 'ID do editor AdSense, geralmente ca-pub-XXXXXXXXXXXXXXXX.',
        'placeholder' => 'ca-pub-XXXXXXXXXXXXXXXX',
    ],
    'GOOGLE_ADSENSE_SLOT_MAIN' => [
        'label' => 'AdSense slot principal',
        'type' => 'text',
        'help' => 'ID do bloco de anuncio criado no AdSense.',
        'placeholder' => '1234567890',
    ],
    'DONATION_URL' => [
        'label' => 'Link de apoio',
        'type' => 'url',
        'help' => 'Link de GitHub Sponsors, Apoia.se, Mercado Pago, Catarse ou similar.',
        'placeholder' => 'https://...',
    ],
    'DONATION_PIX_KEY' => [
        'label' => 'Chave Pix',
        'type' => 'text',
        'help' => 'Mostrada na pagina Apoie quando nao houver link de apoio.',
        'placeholder' => 'email, telefone, CPF/CNPJ ou chave aleatoria',
    ],
];

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $valuesToSave = [];
    foreach ($settingsFields as $key => $field) {
        $value = trim((string)($_POST[$key] ?? ''));
        if ($value !== '' && $field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $field['label'] . ' precisa ser um e-mail valido.';
            continue;
        }
        if ($value !== '' && $field['type'] === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[] = $field['label'] . ' precisa ser uma URL valida, com https://.';
            continue;
        }
        $valuesToSave[$key] = $value;
    }
    if (!$errors) {
        foreach ($valuesToSave as $key => $value) {
            save_app_setting($pdo, $key, $value);
        }
        $success = true;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="card mb-4">
    <div class="card-body">
        <span class="eyebrow">Configuracao restrita</span>
        <h2 class="h5 mt-2">Monetizacao e medicao</h2>
        <p class="text-muted mb-0">Edite tudo por aqui. Ao salvar, o site passa a usar estes valores automaticamente.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> Configuracoes salvas.</div>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card settings-card">
    <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="settings-grid">
            <?php foreach ($settingsFields as $key => $field): ?>
                <?php $value = $_SERVER['REQUEST_METHOD'] === 'POST' ? (string)($_POST[$key] ?? '') : app_setting($key); ?>
                <div class="setting-row">
                    <div>
                        <label class="form-label" for="<?= e($key) ?>"><?= e($field['label']) ?></label>
                        <p class="text-muted small mb-0"><?= e($field['help']) ?></p>
                    </div>
                    <div>
                        <input
                            class="form-control"
                            id="<?= e($key) ?>"
                            name="<?= e($key) ?>"
                            type="<?= e($field['type']) ?>"
                            value="<?= e($value) ?>"
                            placeholder="<?= e($field['placeholder']) ?>"
                        >
                        <div class="setting-status mt-2">
                            <?php if (trim($value) !== ''): ?>
                                <span class="badge text-bg-success">Configurado</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Nao configurado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-column flex-md-row gap-2 justify-content-end mt-4">
            <a class="btn btn-outline-light" href="<?= url('apoie.php') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Ver Apoie</a>
            <button class="btn btn-accent" type="submit"><i class="bi bi-save"></i> Salvar configuracoes</button>
        </div>
    </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
