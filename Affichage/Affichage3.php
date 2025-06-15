<?php
// Paramètres de connexion à la base MySQL
$host = 'localhost';
$dbname = 'mesures_dht11';
$user = 'arduino_user';
$password = 'monpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Modification : LIMIT 1 pour récupérer seulement la dernière mesure
    $stmt = $pdo->query("SELECT * FROM mesures ORDER BY date_mesure DESC LIMIT 1");
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion ou de requête : " . $e->getMessage());
}

// Fonction pour afficher le tableau HTML (réutilisable)
function afficherTableau($mesures) {
    ?>
    <table>
        <caption>Dernière Mesure de Température et Humidité</caption>
        <thead>
            <tr>
                <th>ID</th>
                <th>Température (°C)</th>
                <th>Humidité (%)</th>
                <th>Date de la mesure</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($mesures)): ?>
                <?php foreach ($mesures as $mesure): ?>
                    <tr>
                        <td><?= htmlspecialchars($mesure['id']) ?></td>
                        <td><?= htmlspecialchars($mesure['temperature']) ?></td>
                        <td><?= htmlspecialchars($mesure['humidite']) ?></td>
                        <td><?= htmlspecialchars($mesure['date_mesure']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Aucune donnée trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

// Détection d'une requête AJAX (avec en-tête HTTP_X_REQUESTED_WITH)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    // Si c'est une requête AJAX, on renvoie juste le tableau
    afficherTableau($mesures);
    exit; // on stoppe le script ici
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dernière mesure DHT11</title>
    <link rel="stylesheet" href="Affi.css">
</head>
<body>

    <div class="home-button-container">
        <a href="../Accueil/Accueil.php" class="home-button">
            <span class="home-icon">🏠</span>
            Accueil
        </a>
    </div>

    <div id="table-container">
        <?php afficherTableau($mesures); ?>
    </div>

    <script>
        let isInitialLoad = true;

        async function chargerTableau() {
            try {
                const response = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Erreur réseau');
                const html = await response.text();
                
                const container = document.getElementById('table-container');
                
                if (isInitialLoad) {
                    // Premier chargement : animation normale
                    container.innerHTML = html;
                    isInitialLoad = false;
                } else {
                    // Mises à jour suivantes : transition fluide sans repop
                    const newTable = document.createElement('div');
                    newTable.innerHTML = html;
                    const newTableElement = newTable.querySelector('table');
                    
                    if (newTableElement) {
                        newTableElement.style.animation = 'none';
                        newTableElement.classList.add('no-animation');
                        container.innerHTML = '';
                        container.appendChild(newTableElement);
                    }
                }
            } catch (e) {
                document.getElementById('table-container').innerHTML = 'Erreur lors du chargement des données.';
                console.error(e);
            }
        }

        // Chargement initial
        chargerTableau();

        // Rafraîchir toutes les 2 secondes
        setInterval(chargerTableau, 2000);
    </script>

</body>
</html>