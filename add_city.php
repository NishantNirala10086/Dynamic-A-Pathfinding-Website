<?php
// add_city.php
define('DB_HOST','127.0.0.1'); define('DB_NAME','a_star_db'); define('DB_USER','root'); define('DB_PASS','');
try { $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); } catch (PDOException $e) { die($e->getMessage()); }
$name = $_POST['name'] ?? ''; $lat = $_POST['lat'] ?? ''; $lng = $_POST['lng'] ?? ''; $h = $_POST['h'] ?? null;
if (!$name || $lat==='' || $lng==='') { header('Location: index.php'); exit; }
$stmt = $pdo->prepare("INSERT INTO cities (name, lat, lng, heuristic) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, floatval($lat), floatval($lng), $h === '' ? null : floatval($h)]);
header('Location: index.php');
