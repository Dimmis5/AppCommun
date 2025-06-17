<?php
// Connexion à la base MySQL
$host = 'localhost';
$dbname = 'mesures_dht11';
$user = 'arduino_user';
$password = 'monpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer seulement la dernière mesure
    $stmt = $pdo->query("SELECT * FROM mesures ORDER BY date_mesure DESC LIMIT 1");
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Afficher le tableau
    if (!empty($mesures)) {
        echo '<table>
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
            echo '<tr>
                <td>'.htmlspecialchars($mesure['id']).'</td>
                <td>'.htmlspecialchars($mesure['temperature']).'</td>
                <td>'.htmlspecialchars($mesure['humidite']).'</td>
                <td>'.htmlspecialchars($mesure['date_mesure']).'</td>
            </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<table><tr><td colspan="4">Aucune donnée trouvée.</td></tr></table>';
    }
} catch (PDOException $e) {
    echo '<table><tr><td colspan="4">Erreur de connexion à la base de données.</td></tr></table>';
}
?>