<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login_json();

$where = ['user_id = ?'];
$params = [current_user_id()];

if (!empty($_GET['q'])) {
    $where[] = '(titulo LIKE ? OR anunciante LIKE ? OR descricao LIKE ? OR cidade LIKE ? OR bairro LIKE ?)';
    $term = '%' . $_GET['q'] . '%';
    array_push($params, $term, $term, $term, $term, $term);
}
foreach (['fonte', 'status', 'cidade', 'bairro', 'tipo_negocio', 'tipo_imovel'] as $field) {
    if (!empty($_GET[$field])) {
        $where[] = "$field = ?";
        $params[] = $_GET[$field];
    }
}
if (isset($_GET['preco_min']) && $_GET['preco_min'] !== '') { $where[] = 'preco >= ?'; $params[] = (float)$_GET['preco_min']; }
if (isset($_GET['preco_max']) && $_GET['preco_max'] !== '') { $where[] = 'preco <= ?'; $params[] = (float)$_GET['preco_max']; }
if (isset($_GET['quartos_min']) && $_GET['quartos_min'] !== '') { $where[] = 'quartos >= ?'; $params[] = (int)$_GET['quartos_min']; }
if (isset($_GET['area_min']) && $_GET['area_min'] !== '') { $where[] = 'area_m2 >= ?'; $params[] = (float)$_GET['area_min']; }
if (isset($_GET['nota_min']) && $_GET['nota_min'] !== '') { $where[] = 'nota_oportunidade >= ?'; $params[] = (int)$_GET['nota_min']; }

$orderMap = [
    'nota' => 'nota_oportunidade',
    'recentes' => 'id',
    'preco' => 'preco',
    'cidade' => 'cidade',
    'bairro' => 'bairro',
    'quartos' => 'quartos',
    'area' => 'area_m2',
    'fonte' => 'fonte',
    'status' => 'status',
];
$sortKey = $_GET['ordem'] ?? 'recentes';
$sortDir = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderColumn = $orderMap[$sortKey] ?? 'id';
$limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);
$order = $orderColumn . ' ' . $sortDir . ', id DESC';

$countSql = 'SELECT COUNT(*) FROM imoveis WHERE ' . implode(' AND ', $where);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = 'SELECT * FROM imoveis WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

json_response([
    'success' => true,
    'data' => $rows,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
    'has_more' => $offset + count($rows) < $total,
]);
