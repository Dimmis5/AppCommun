<?php
// Paramètres de connexion à la base MySQL
$host = 'romantcham.fr';
$dbname = 'Domotic_db';
$user = 'G7E';
$password = 'afyubr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupération des données initiales pour le premier chargement
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
            <a href="../Affichage/Affichage3.php" class="home-button">Retour</a>
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
                <tbody id="currentTempData">
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
                <tbody id="historyData">
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

    <script>
        let tempChart;

        // Fonction pour créer le graphique initial
        function createInitialChart() {
            const ctx = document.getElementById('tempChart').getContext('2d');
            tempChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Température (°C)',
                        data: [],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 159, 64)',
                        borderWidth: 3.5,
                        tension: 0.5,
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
        }

        // Fonction pour récupérer les nouvelles données
        function fetchData() {
            fetch('get_temp_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    const currentTempHtml = data.current ? `
                        <tr>
                            <td>Température</td>
                            <td>${data.current.valeur}</td>
                            <td>°C</td>
                            <td>${data.current.date}</td>
                        </tr>
                    ` : `
                        <tr>
                            <td colspan="4">Aucune donnée disponible</td>
                        </tr>
                    `;
                    document.getElementById('currentTempData').innerHTML = currentTempHtml;

                    let historyHtml = '';
                    data.history.reverse().forEach(measure => {
                        historyHtml += `
                            <tr>
                                <td>${measure.date}</td>
                                <td>${measure.valeur} °C</td>
                            </tr>
                        `;
                    });
                    document.getElementById('historyData').innerHTML = historyHtml;

                    tempChart.data.labels = data.labels;
                    tempChart.data.datasets[0].data = data.values;
                    tempChart.update();
                })
                .catch(error => console.error('Erreur:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            createInitialChart();
            fetchData();
            
            setInterval(fetchData, 2000);
        });
    </script>
</body>
</html>