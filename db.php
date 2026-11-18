<?php
date_default_timezone_set("Africa/Porto-Novo");
$host = "localhost";
$dbname = "agz_dashboard";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    exit("Impossible de se connecter à la base de données.");
}