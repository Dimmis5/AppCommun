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
        WHERE m.id_composant = 7
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
        WHERE m.id_composant = 7
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
            <a href ="../Affichage/Affichage3.php" class ="home-button"> Retour </a>
        </div>
        <h1>Données d'humidité</h1>
        
        <div class="current-value">
            <h2>Valeur actuelle</h2>
            <p><strong>Humidité :</strong> <?php echo htmlspecialchars($derniere_mesure['valeur']); ?> %</p>
            <p><strong>Dernière mise à jour :</strong> <?php echo htmlspecialchars($derniere_mesure['date']); ?></p>
        </div>
        
        <div class="chart-container">
            <canvas id="humidityChart"></canvas>
        </div>
        
        <h2>Historique récent (100 dernières mesures)</h2>
        <table>
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Humidité (%)</th>
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
        const dates = <?php echo json_encode(array_column($mesures, 'date')); ?>;
        const valeurs = <?php echo json_encode(array_column($mesures, 'valeur')); ?>;
        
        const ctx = document.getElementById('humidityChart').getContext('2d');
        const humidityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Humidité (%)',
                    data: valeurs,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(255, 159, 64)',
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
                        title: {
                            display: true,
                            text: 'Humidité (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date et heure'
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
                        position: 'top',
                    }
                },
                hover: {
                    mode: 'nearest',
                    intersect: true
                }
            }
        });
        
        function refreshData() {
            fetch(window.location.href + '?refresh=1')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    document.querySelector('table tbody').innerHTML = doc.querySelector('table tbody').innerHTML;
                    
                    const currentValueDiv = doc.querySelector('.current-value');
                    if (currentValueDiv) {
                        document.querySelector('.current-value').innerHTML = currentValueDiv.innerHTML;
                    }
                })
                .catch(error => console.error('Erreur lors de la mise à jour:', error));
        }
        s
        setInterval(refreshData, 2000);
    </script>
</body>
</html>