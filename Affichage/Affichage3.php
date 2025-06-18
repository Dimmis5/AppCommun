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
            c.nom as nom_capteur,
            m.valeur,
            m.date,
            c.id as id_composant
        FROM mesure m
        INNER JOIN composant c ON m.id_composant = c.id
        WHERE c.id IN (1, 2, 6, 7) -- Servo (1), Luminosité (2), Température (6), Humidité (7)
        AND m.date = (
            SELECT MAX(m2.date) 
            FROM mesure m2 
            WHERE m2.id_composant = m.id_composant
        )
        ORDER BY c.id
    ");
    $stmt->execute();
    $mesures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organisation des données par type de capteur
    $donnees = [];
    foreach ($mesures as $mesure) {
        $donnees[$mesure['id_composant']] = $mesure;
    }
    
} catch (PDOException $e) {
    die("Erreur de connexion ou de requête : " . $e->getMessage());
}

function afficherTableau($donnees) {
    ?>
    <table>
        <caption>Dernières Mesures des Capteurs</caption>
        <thead>
            <tr>
                <th>Type de Capteur</th>
                <th>Valeur</th>
                <th>Unité</th>
                <th>Date de la mesure</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($donnees)): ?>
                <?php 
                // Affichage du servo (id_composant = 1)
                if (isset($donnees[1])): ?>
                    <tr class="servo">
                        <td>ServoMoteur</td>
                        <td><?= htmlspecialchars($donnees[1]['valeur']) ?></td>
                        <td><?= $donnees[1]['valeur'] == 1 ? 'Activé' : 'Désactivé' ?></td>
                        <td><?= htmlspecialchars($donnees[1]['date']) ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php 
                // Affichage de la luminosité (id_composant = 2)
                if (isset($donnees[2])): ?>
                    <tr class="luminosite">
                        <td><a href="../capteur/lunimiere.php" style="color: inherit; text-decoration: none;">Luminosité</a></td>
                        <td><?= htmlspecialchars($donnees[2]['valeur']) ?></td>
                        <td>lux</td>
                        <td><?= htmlspecialchars($donnees[2]['date']) ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php 
                // Affichage de la température (id_composant = 6)
                if (isset($donnees[6])): ?>
                    <tr class="temperature">
                        <td><a href="../capteur/temperature.php" style="color: inherit; text-decoration: none;">Température</a></td>
                        <td><?= htmlspecialchars($donnees[6]['valeur']) ?></td>
                        <td>°C</td>
                        <td><?= htmlspecialchars($donnees[6]['date']) ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php 
                // Affichage de l'humidité (id_composant = 7)
                if (isset($donnees[7])): ?>
                    <tr class="humidite">
                        <td><a href="../capteur/humidite.php" style="color: inherit; text-decoration: none;">Humidité</a></td>
                        <td><?= htmlspecialchars($donnees[7]['valeur']) ?></td>
                        <td>%</td>
                        <td><?= htmlspecialchars($donnees[7]['date']) ?></td>
                    </tr>
                <?php endif; ?>
                
                <?php if (empty($donnees)): ?>
                    <tr><td colspan="4">Aucune donnée trouvée.</td></tr>
                <?php endif; ?>
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
    afficherTableau($donnees);
    exit; 
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Tempérium</title>
    <link rel="icon" href="../logo/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="Affi.css">

</head>
<body>

    <div class="home-button-container">
        <a href="../Accueil/Accueil.php" class="home-button">
            Accueil
        </a>
    </div>

    <div id="table-container">
        <?php afficherTableau($donnees); ?>
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
                    container.innerHTML = html;
                    isInitialLoad = false;
                } else {
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

        chargerTableau();

        setInterval(chargerTableau, 2000);
    </script>

</body>
</html>