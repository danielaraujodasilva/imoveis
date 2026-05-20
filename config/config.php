<?php
declare(strict_types=1);

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(404);
    exit;
}

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'agente_vagas');
define('DB_USER', 'root');
define('DB_PASS', '');

define('ROOT_PATH', dirname(__DIR__));
define('NODE_PATH', 'node');
define('NODE_SCRIPT_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'node');

$localConfig = __DIR__ . '/local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-este-segredo-no-servidor');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', ROOT_PATH);
defined('GIT_PATH') || define('GIT_PATH', 'git');
defined('BASE_URL') || define('BASE_URL', '/imoveis');

defined('SITE_NAME') || define('SITE_NAME', 'Radar Imoveis');
defined('ADMIN_EMAIL') || define('ADMIN_EMAIL', 'danielaraujodasilva@gmail.com');
defined('SITE_CONTACT_EMAIL') || define('SITE_CONTACT_EMAIL', '');
defined('SITE_PUBLIC_URL') || define('SITE_PUBLIC_URL', '');

defined('GOOGLE_ANALYTICS_ID') || define('GOOGLE_ANALYTICS_ID', '');
defined('GOOGLE_ADSENSE_CLIENT') || define('GOOGLE_ADSENSE_CLIENT', 'ca-pub-9113343087591042');
defined('GOOGLE_ADSENSE_SLOT_MAIN') || define('GOOGLE_ADSENSE_SLOT_MAIN', '');

defined('DONATION_URL') || define('DONATION_URL', '');
defined('DONATION_PIX_KEY') || define('DONATION_PIX_KEY', '');
