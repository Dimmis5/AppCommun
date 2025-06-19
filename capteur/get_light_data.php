<?php
header('Content-Type: application/json');

// Paramètres de connexion à la base MySQL
$host = 'romantcham.fr';
$dbname = 'Domotic_db';
$user = 'G7E';
$password = 'afyubr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $currentStmt = $pdo->prepare("
        SELECT 
            c.nom as nom_capteur,
            m.valeur,
            m.date,
            c.id as id_composant
        FROM mesure m
        INNER JOIN composant c ON m.id_composant = c.id
        WHERE c.id = 2 -- Luminosité
        ORDER BY m.date DESC
        LIMIT 1
    ");
    $currentStmt->execute();
    $currentLight = $currentStmt->fetch(PDO::FETCH_ASSOC);

    $historyStmt = $pdo->prepare("
        SELECT 
            m.valeur,
            m.date
        FROM mesure m
        WHERE m.id_composant = 2 -- Luminosité
        ORDER BY m.date DESC
        LIMIT 100
    ");
    $historyStmt->execute();
    $lightHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    $reversedHistory = array_reverse($lightHistory);
    $labels = [];
    $values = [];
    
    foreach ($reversedHistory as $measure) {
        $labels[] = date('H:i:s', strtotime($measure['date']));
        $values[] = $measure['valeur'];
    }

    echo json_encode([
        'current' => $currentLight,
        'history' => $lightHistory,
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => "Erreur de connexion ou de requête : " . $e->getMessage()]);
}
?>