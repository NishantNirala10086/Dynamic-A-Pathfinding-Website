<?php
// add_edge.php
define('DB_HOST','127.0.0.1'); define('DB_NAME','a_star_db'); define('DB_USER','root'); define('DB_PASS','');
try { $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); } catch (PDOException $e) { die($e->getMessage()); }
$from = intval($_POST['from_city']); $to = intval($_POST['to_city']); $dist = $_POST['distance']; $und = isset($_POST['undirected']) ? 1 : 0;
if ($from <=0 || $to <=0) { header('Location: index.php'); exit; }
if ($dist === '') {
    // compute distance from cities table
    $stmt = $pdo->prepare("SELECT lat,lng FROM cities WHERE id=?");
    $stmt->execute([$from]); $a = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->execute([$to]); $b = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$a || !$b) { header('Location: index.php'); exit; }
    // haversine in PHP
    function hav($a1,$a2,$b1,$b2){
        $R = 6371.0; $dlat = deg2rad($b1-$a1); $dlon = deg2rad($b2-$a2);
        $r1 = deg2rad($a1); $r2 = deg2rad($b1);
        $aa = sin($dlat/2)*sin($dlat/2)+cos($r1)*cos($r2)*sin($dlon/2)*sin($dlon/2);
        return $R * 2 * atan2(sqrt($aa), sqrt(1-$aa));
    }
    $dist = hav($a['lat'],$b['lat'],$a['lng'],$b['lng']); // NOTE: order adjusted
}
$stmt = $pdo->prepare("INSERT INTO edges (from_city,to_city,distance) VALUES (?, ?, ?)");
$stmt->execute([$from, $to, floatval($dist)]);
if ($und) {
    $stmt->execute([$to, $from, floatval($dist)]);
}
header('Location: index.php');
