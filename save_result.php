<?php
// save_result.php
header('Content-Type: application/json');
define('DB_HOST','127.0.0.1'); define('DB_NAME','a_star_db'); define('DB_USER','root'); define('DB_PASS','');
try { $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); } catch (PDOException $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit; }
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload) { echo json_encode(['success'=>false,'error'=>'invalid payload']); exit; }
$start = $payload['start']; $end = $payload['end']; $path = json_encode($payload['path']); $cost = $payload['cost']; $stats = json_encode($payload['node_stats']);
try {
    $stmt = $pdo->prepare("INSERT INTO route_results (start_city, end_city, path_json, cost, node_stats_json) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$start, $end, $path, $cost, $stats]);
    echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
