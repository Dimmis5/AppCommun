<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérifier que c'est une requête AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit;
}

//  Connexion à la base MySQL
$host = 'localhost';
$dbname = 'mesures_dht11';
$user = 'arduino_user';
$password = 'monpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les 100 dernières mesures et trié
    $stmt = $pdo->query("SELECT temperature, humidite, date_mesure FROM mesures ORDER BY date_mesure DESC LIMIT 100");
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Inverser l'ordre pour avoir les plus anciennes en premier 
    $mesures = array_reverse($mesures);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'mesures' => $mesures,
        'count' => count($mesures)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données',
        'details' => $e->getMessage()
    ]);
}
?>