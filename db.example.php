<?php
/**
 * Vadnica Bančništvo - Konfiguracija baze podatkov (Primer / Template)
 * 
 * Navodila:
 * 1. Vnesite vaše podatke za povezavo z MySQL strežnikom spodaj
 * 2. Zaženite "setup.php" v brskalniku ali preko CLI (php setup.php)
 */

require_once __DIR__ . "/crypto_helper.php";

$host = "localhost";
$user = "root";
$pass = "";
$db   = "vadnica_bancnistvo";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka pri povezavi z bazo: " . $e->getMessage()]);
    exit;
}
