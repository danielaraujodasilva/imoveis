<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();

$cmd = 'cd /d ' . escapeshellarg(NODE_SCRIPT_DIR) . ' && ' . escapeshellcmd(NODE_PATH) . ' search-properties.js --user=' . current_user_id() . ' 2>&1';
$output = [];
$code = 0;
exec($cmd, $output, $code);
$message = implode("\n", $output);
app_log($pdo, 'rodar_busca_imoveis', $message);

if ($code !== 0) {
    json_response(['success' => false, 'error' => 'Busca falhou. Confira dependencias Node e .env.', 'details' => $message], 500);
}

json_response(['success' => true, 'message' => $message ?: 'Busca finalizada.']);
