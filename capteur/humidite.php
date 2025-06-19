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
        WHERE c.id = 7 -- Humidité
        ORDER BY m.date DESC
        LIMIT 1
    ");
    $currentStmt->execute();
    $currentHumidity = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    $historyStmt = $pdo->prepare("
        SELECT 
            m.valeur,
            m.date
        FROM mesure m
        WHERE m.id_composant = 7 -- Humidité
        ORDER BY m.date DESC
        LIMIT 100
    ");
    $historyStmt->execute();
    $humidityHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de connexion ou de requête : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tempérium - Humidité</title>
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
        
        <h1>Données d'humidité</h1>
        
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
                <tbody id="currentHumidityData">
                    <?php if ($currentHumidity): ?>
                    <tr>
                        <td>Humidité</td>
                        <td><?= htmlspecialchars($currentHumidity['valeur']) ?></td>
                        <td>%</td>
                        <td><?= htmlspecialchars($currentHumidity['date']) ?></td>
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
            <canvas id="humidityChart"></canvas>
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
                        <th>Humidité (%)</th>
                    </tr>
                </thead>
                <tbody id="historyData">
                    <?php foreach (array_reverse($humidityHistory) as $measure): ?>
                    <tr>
                        <td><?= htmlspecialchars($measure['date']) ?></td>
                        <td><?= htmlspecialchars($measure['valeur']) ?> %</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let humidityChart;

        // Fonction pour créer le graphique initial
        function createInitialChart() {
            const ctx = document.getElementById('humidityChart').getContext('2d');
            humidityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Humidité (%)',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 3.5,
                        tension: 0.1,
                        pointRadius: 2,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            suggestedMin: 0,
                            suggestedMax: 100,
                            title: {
                                display: true,
                                text: 'Humidité (%)'
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
            fetch('get_humidity_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    const currentHumidityHtml = data.current ? `
                        <tr>
                            <td>Humidité</td>
                            <td>${data.current.valeur}</td>
                            <td>%</td>
                            <td>${data.current.date}</td>
                        </tr>
                    ` : `
                        <tr>
                            <td colspan="4">Aucune donnée disponible</td>
                        </tr>
                    `;
                    document.getElementById('currentHumidityData').innerHTML = currentHumidityHtml;

                    let historyHtml = '';
                    data.history.reverse().forEach(measure => {
                        historyHtml += `
                            <tr>
                                <td>${measure.date}</td>
                                <td>${measure.valeur} %</td>
                            </tr>
                        `;
                    });
                    document.getElementById('historyData').innerHTML = historyHtml;

                    humidityChart.data.labels = data.labels;
                    humidityChart.data.datasets[0].data = data.values;
                    humidityChart.update();
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