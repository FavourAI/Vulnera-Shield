<?php
$host = 'mysql-vulnera-vulnera-db.b.aivencloud.com';
$port = 12480;
$db   = 'vulnerashield-db1';
$user = 'avnadmin';
$pass = 'AVNS_oYWDRU_LIr6jpdTlkwG';
$ssl_ca = __DIR__ . '/ca.pem'; // Make sure this file exists

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
