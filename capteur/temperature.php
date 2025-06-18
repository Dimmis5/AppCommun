<?php
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
        WHERE c.id = 6 -- Température
        ORDER BY m.date DESC
        LIMIT 1
    ");
    $currentStmt->execute();
    $currentTemp = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    $historyStmt = $pdo->prepare("
        SELECT 
            m.valeur,
            m.date
        FROM mesure m
        WHERE m.id_composant = 6 -- Température
        ORDER BY m.date DESC
        LIMIT 100
    ");
    $historyStmt->execute();
    $tempHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tempHistory = array_reverse($tempHistory);
    $labels = [];
    $values = [];
    
    foreach ($tempHistory as $measure) {
        $labels[] = date('H:i:s', strtotime($measure['date']));
        $values[] = $measure['valeur'];
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion ou de requête : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique Température</title>
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
        
        <h1>Historique de la Température</h1>
        
        <div class="current-value">
            <h2>Valeur actuelle</h2>
            <table>
                <thead>
                    <tr>
                        <th>Type de Capteur</th>
                        <th>Valeur</th>
                        <th>Unité</th>
                        <th>Date de la mesure</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($currentTemp): ?>
                    <tr>
                        <td>Température</td>
                        <td><?= htmlspecialchars($currentTemp['valeur']) ?></td>
                        <td>°C</td>
                        <td><?= htmlspecialchars($currentTemp['date']) ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="4">Aucune donnée disponible</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        
        
        <div class="chart-container">
            <canvas id="tempChart"></canvas>
        </div>
        <br>
    </div>

            <div class="history-table">
            <h2>100 dernières mesures</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date et Heure</th>
                            <th>Température (°C)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($tempHistory) as $measure): ?>
                        <tr>
                            <td><?= htmlspecialchars($measure['date']) ?></td>
                            <td><?= htmlspecialchars($measure['valeur']) ?> °C</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('tempChart').getContext('2d');
            const tempChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($labels) ?>,
                    datasets: [{
                        label: 'Température (°C)',
                        data: <?= json_encode($values) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        tension: 0.1,
                        pointRadius: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            title: {
                                display: true,
                                text: 'Température (°C)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Heure de la mesure'
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    }
                }
            });
            
            setInterval(() => {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newScript = doc.querySelector('script');
                        if (newScript) {
                            eval(newScript.textContent);
                        }
                    });
            }, 2000);
        });
    </script>
</body>
</html>