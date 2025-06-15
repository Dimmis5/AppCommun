<?php
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userName = '';
if ($isLoggedIn) {
    $userName = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tempérium</title>
    <link rel="icon" href="../logo/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="Accueil.css">
<body>
    <header class="spacing">
        <div class="header-content">
            <h1>Projet Domotique : Gestion Rideau & Lampe</h1>
            <div class="company-name">  </div>
            <div class = "top-right-logo"><img src="../logo/logo.png" alt="logo" class="logo-img"></div>
        </div>
    </header>

    <?php if ($isLoggedIn): ?>
        <div class="user-info spacing">
            <span class="welcome-message">
                Bienvenue, <?php echo htmlspecialchars($userName); ?> !
            </span>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    <?php else: ?>
        <div class="guest-message spacing">
            Vous n'êtes pas connecté. 
            <a href="../Connexion/Connexion.php" class="login-link">Se connecter</a> 
            pour accéder à toutes les fonctionnalités.
        </div>
    <?php endif; ?>

    <nav>
        <a href="Accueil.php">Accueil</a>
        <a href="<?php echo $isLoggedIn ? '../Capteur/Capteur.html' : '../Connexion/Connexion.php'; ?>">Capteur & Actionneurs</a>
        <a href="<?php echo $isLoggedIn ? '../Capteur/Controler.html' : '../Connexion/Connexion.php'; ?>">Contôler</a>
        <a href="<?php echo $isLoggedIn ? '../Affichage/Affichage3.php' : '../Connexion/Connexion.php'; ?>">Donnée capteur</a>

    </nav>

    <?php if ($isLoggedIn): ?>
        <div id="table-container">
            <?php
            // Paramètres de connexion à la base MySQL
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
        </div>
        
        <script>
            let isInitialLoad = true;

            async function chargerTableau() {
                try {
                    const response = await fetch('get_data.php', {
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
                    document.getElementById('table-container').innerHTML = '<table><tr><td colspan="4">Erreur lors du chargement des données.</td></tr></table>';
                    console.error(e);
                }
            }

            // Rafraîchir toutes les 2 secondes
            setInterval(chargerTableau, 2000);
        </script>
    <?php endif; ?>

    <footer>
        &copy; 2025 Projet Domotique - Tous droits réservés
    </footer>
</body>
</html>