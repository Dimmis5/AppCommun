<?php
// Paramètres de connexion à la base MySQL
$host = 'romantcham.fr';
$dbname = 'Domotic_db';
$user = 'G7E';
$password = 'afyubr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        SELECT 
            m.valeur,
            m.date
        FROM mesure m
        WHERE m.id_composant = 2 -- Luminosité
        ORDER BY m.date DESC
        LIMIT 100
    ");
    $stmt->execute();
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mesures = array_reverse($mesures);

    $stmt = $pdo->prepare("
        SELECT 
            m.valeur,
            m.date
        FROM mesure m
        WHERE m.id_composant = 2 -- Luminosité
        ORDER BY m.date DESC
        LIMIT 1
    ");
    $stmt->execute();
    $derniere_mesure = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de connexion ou de requête : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Tempérium - Historique Luminosité</title>
    <link rel="icon" href="../logo/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="capteur.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div class="home-button-container">
            <a href="../Accueil/Accueil.php" class="home-button">
                Accueil
            </a>
            <a href ="../Affichage/Affichage3.php" class ="home-button"> Retour </a>
        </div>

        <div class="current-value">
            <h2>Valeur actuelle de luminosité</h2>
            <table>
                <thead>
                    <tr>
                        <th>Valeur</th>
                        <th>Unité</th>
                        <th>Date de la mesure</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($derniere_mesure['valeur']) ?></td>
                        <td>lux</td>
                        <td><?= htmlspecialchars($derniere_mesure['date']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="graph-container">
            <h2>Historique des 100 dernières mesures</h2>
            <canvas id="luminosityChart"></canvas>
        </div>
    </div>

            <h2>Historique récent (100 dernières mesures)</h2>
        <table>
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Luminosité (Lux)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($mesures) as $mesure): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mesure['date']); ?></td>
                        <td><?php echo htmlspecialchars($mesure['valeur']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        const dates = <?= json_encode(array_column($mesures, 'date')) ?>;
        const valeurs = <?= json_encode(array_column($mesures, 'valeur')) ?>;

        const ctx = document.getElementById('luminosityChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Luminosité (lux)',
                    data: valeurs,
                    borderColor: 'rgb(255, 159, 64)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Date et heure'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Luminosité (lux)'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>