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
$warnings = [];
$cleanOutput = [];
foreach ($output as $line) {
    if (str_starts_with($line, 'WARNINGS_JSON:')) {
        $decoded = json_decode(substr($line, strlen('WARNINGS_JSON:')), true);
        if (is_array($decoded)) {
            $warnings = array_values(array_filter(array_map('strval', $decoded)));
        }
        continue;
    }
    $cleanOutput[] = $line;
}
$message = implode("\n", $cleanOutput);
app_log($pdo, 'rodar_busca_imoveis', $message);

if ($code !== 0) {
    json_response(['success' => false, 'error' => 'Busca falhou. Confira dependencias Node e .env.', 'details' => $message], 500);
}

json_response(['success' => true, 'message' => $message ?: 'Busca finalizada.', 'warnings' => $warnings]);
