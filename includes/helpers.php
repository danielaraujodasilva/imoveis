<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function require_valid_csrf(): void
{
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessao expirada. Recarregue a pagina e tente novamente.');
    }
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function current_user_id(): int
{
    return (int)($_SESSION['usuario']['id'] ?? 0);
}

function is_auth_page(): bool
{
    return in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'register.php'], true);
}

function is_public_page(): bool
{
    return in_array(basename($_SERVER['SCRIPT_NAME']), ['login.php', 'register.php', 'privacidade.php', 'contato.php', 'apoie.php'], true);
}

function require_login(): void
{
    if (current_user_id() > 0 || is_public_page()) {
        return;
    }
    header('Location: ' . url('login.php'));
    exit;
}

function require_login_json(): void
{
    if (current_user_id() <= 0) {
        json_response(['success' => false, 'error' => 'Login necessario.'], 401);
    }
}

function is_admin_user(): bool
{
    $email = strtolower((string)(current_user()['email'] ?? ''));
    $adminEmail = app_setting('ADMIN_EMAIL');
    return $email !== '' && $adminEmail !== '' && $email === strtolower($adminEmail);
}

function require_admin(): void
{
    require_login();
    if (!is_admin_user()) {
        http_response_code(403);
        exit('Acesso restrito ao administrador.');
    }
}

function user_where(string $column = 'user_id'): string
{
    return "({$column} = ? OR {$column} IS NULL)";
}

function claim_legacy_rows(PDO $pdo, int $userId): void
{
    foreach (['buscas_imoveis', 'imoveis', 'logs_execucao_imoveis'] as $table) {
        $stmt = $pdo->prepare("UPDATE {$table} SET user_id = ? WHERE user_id IS NULL");
        $stmt->execute([$userId]);
    }
}

function badge_class(string $status): string
{
    return match ($status) {
        'interessante' => 'text-bg-info',
        'contatado' => 'text-bg-primary',
        'visita' => 'text-bg-success',
        'descartado' => 'text-bg-danger',
        'arquivada' => 'text-bg-secondary',
        default => 'text-bg-warning',
    };
}

function status_options(): array
{
    return ['novo', 'interessante', 'contatado', 'visita', 'descartado', 'arquivado'];
}

function app_log(PDO $pdo, string $tipo, string $mensagem): void
{
    $stmt = $pdo->prepare('INSERT INTO logs_execucao_imoveis (user_id, tipo, mensagem) VALUES (?, ?, ?)');
    $stmt->execute([current_user_id() ?: null, $tipo, $mensagem]);
}

function feature_enabled(string $constant): bool
{
    return app_setting($constant) !== '';
}

function app_setting(string $key, ?string $fallback = null): string
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            try {
                $stmt = $GLOBALS['pdo']->query('SELECT setting_key, setting_value FROM app_settings');
                foreach ($stmt->fetchAll() as $row) {
                    $settings[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
                }
            } catch (Throwable $e) {
                $settings = [];
            }
        }
    }
    if (array_key_exists($key, $settings)) {
        return trim((string)$settings[$key]);
    }
    if ($fallback !== null) {
        return trim($fallback);
    }
    return defined($key) ? trim((string)constant($key)) : '';
}

function save_app_setting(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value, updated_by, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()');
    $stmt->execute([$key, trim($value), current_user_id() ?: null]);
}

function render_monetization_block(string $context = 'principal'): void
{
    $hasAds = feature_enabled('GOOGLE_ADSENSE_CLIENT') && feature_enabled('GOOGLE_ADSENSE_SLOT_MAIN');
    $hasSupport = feature_enabled('DONATION_URL') || feature_enabled('DONATION_PIX_KEY');
    if (!$hasAds && !$hasSupport) {
        return;
    }
    echo '<aside class="monetization-block" aria-label="Apoio ao projeto">';
    if ($hasAds) {
        echo '<span class="ad-label">Publicidade</span>';
        echo '<ins class="adsbygoogle" style="display:block" data-ad-client="' . e(app_setting('GOOGLE_ADSENSE_CLIENT')) . '" data-ad-slot="' . e(app_setting('GOOGLE_ADSENSE_SLOT_MAIN')) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>';
        echo '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
    } elseif ($hasSupport) {
        echo '<div><span class="eyebrow">Projeto gratuito</span><p class="mb-0">O Radar Imoveis segue gratuito. Contribuicoes ajudam a manter hospedagem, melhorias e novas fontes de anuncios.</p></div>';
        if (feature_enabled('DONATION_URL')) {
            echo '<a class="btn btn-sm btn-accent" target="_blank" rel="noopener" href="' . e(app_setting('DONATION_URL')) . '"><i class="bi bi-heart"></i> Apoiar</a>';
        } elseif (feature_enabled('DONATION_PIX_KEY')) {
            echo '<code class="support-code">' . e(app_setting('DONATION_PIX_KEY')) . '</code>';
        }
    }
    echo '</aside>';
}
