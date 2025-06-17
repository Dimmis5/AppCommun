<?php
session_start();

// Regarder si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
if ($isLoggedIn) {
    $userName = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
}

// Paramètres de connexion à la base MySQL
$host = 'localhost';
$dbname = 'mesures_dht11';
$user = 'arduino_user';
$password = 'monpassword';

$tableHtml = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer seulement la dernière mesure pour le tableau
    $stmt = $pdo->query("SELECT * FROM mesures ORDER BY date_mesure DESC LIMIT 1");
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le HTML du tableau
    if (!empty($mesures)) {
        $tableHtml = '<table>
            <caption>Dernière Mesure de Température et Humidité</caption>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Température (°C)</th>
                    <th>Humidité (%)</th>
                    <th>Date de la mesure</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($mesures as $mesure) {
            $tableHtml .= '<tr>
                <td>'.htmlspecialchars($mesure['id']).'</td>
                <td>'.htmlspecialchars($mesure['temperature']).'</td>
                <td>'.htmlspecialchars($mesure['humidite']).'</td>
                <td>'.htmlspecialchars($mesure['date_mesure']).'</td>
            </tr>';
        }
        
        $tableHtml .= '</tbody></table>';
    } else {
        $tableHtml = '<table><tr><td colspan="4">Aucune donnée trouvée.</td></tr></table>';
    }
} catch (PDOException $e) {
    $tableHtml = '<table><tr><td colspan="4">Erreur de connexion à la base de données.</td></tr></table>';
}

// Inclure le template HTML
include 'Accueil_template.php';
?>