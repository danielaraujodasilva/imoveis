<?php
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_NAME') || define('DB_NAME', 'agente_vagas');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');

defined('BASE_URL') || define('BASE_URL', '/imoveis');
defined('SITE_PUBLIC_URL') || define('SITE_PUBLIC_URL', 'https://seudominio.com/imoveis');

defined('DEPLOY_WEBHOOK_SECRET') || define('DEPLOY_WEBHOOK_SECRET', 'troque-por-um-segredo-grande');
defined('DEPLOY_BRANCH') || define('DEPLOY_BRANCH', 'main');
defined('DEPLOY_REPO_PATH') || define('DEPLOY_REPO_PATH', dirname(__DIR__));
defined('GIT_PATH') || define('GIT_PATH', 'git');
