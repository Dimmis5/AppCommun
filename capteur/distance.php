<?php
//donnée fictif
function genererDonneesFictives() {
    $mesures = [];
    $baseTime = time() - (100 * 3600);
    
    // vaiation de distance
    $baseDistance = 150;
    
    for ($i = 0; $i < 100; $i++) {
        $variation = sin($i * 0.3) * 50 + rand(-20, 20);
        $distance = max(5, min(400, $baseDistance + $variation)); 
        
        $mesures[] = [
            'valeur' => round($distance, 1),
            'date' => date('Y-m-d H:i:s', $baseTime + ($i * 3600)) 
        ];
    }
    
    return $mesures;
}

$mesures = genererDonneesFictives();
$derniere_mesure = end($mesures); 

$derniere_mesure = [
    'valeur' => round(120 + rand(-30, 30), 1),
    'date' => date('Y-m-d H:i:s') 
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Tempérium - Historique Distance</title>
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
            <h2>Valeur actuelle de distance</h2>
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
                        <td>cm</td>
                        <td><?= htmlspecialchars($derniere_mesure['date']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="graph-container">
            <h2>Historique des 100 dernières mesures</h2>
            <canvas id="distanceChart"></canvas>
        </div>
    </div>

            <h2>Historique récent (100 dernières mesures)</h2>
        <table>
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Distance (cm)</th>
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

        const ctx = document.getElementById('distanceChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Distance (cm)',
                    data: valeurs,
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.1)',
                    tension: 0.1,
                    fill: false,
                    pointRadius: 0,
                    pointHoverRadius: 0
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
                            text: 'Distance (cm)'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>