<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=Domotic_db;charset=utf8', 'G7E', 'afyubr');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Erreur de connexion à la base de données']));
}
?>
