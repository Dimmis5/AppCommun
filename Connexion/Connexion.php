<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'mesures_dht11';
$user = 'arduino_user';
$password = 'monpassword';

$message = '';
$messageType = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $motDePasse = $_POST['password'];
    
    if (empty($email) || empty($motDePasse)) {
        $message = 'Veuillez remplir tous les champs.';
        $messageType = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT id, prenom, nom, mot_de_passe FROM utilisateur WHERE adresse_mail = ?");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($utilisateur && hash('sha256', $motDePasse) === $utilisateur['mot_de_passe']) {
                $_SESSION['user_id'] = $utilisateur['id'];
                $_SESSION['user_prenom'] = $utilisateur['prenom'];
                $_SESSION['user_nom'] = $utilisateur['nom'];
                $_SESSION['user_email'] = $email;
                
                header('Location: ../Accueil/Accueil.php');
                exit();
            } else {
                $message = 'Adresse e-mail ou mot de passe incorrect.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Erreur de connexion à la base de données.';
            $messageType = 'error';
        }
    }
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $motDePasse = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($prenom) || empty($nom) || empty($email) || empty($motDePasse)) {
        $message = 'Veuillez remplir tous les champs obligatoires.';
        $messageType = 'error';
    } elseif ($motDePasse !== $confirmPassword) {
        $message = 'Les mots de passe ne correspondent pas.';
        $messageType = 'error';
    } elseif (strlen($motDePasse) < 6) {
        $message = 'Le mot de passe doit contenir au moins 6 caractères.';
        $messageType = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id FROM utilisateur WHERE adresse_mail = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $message = 'Cette adresse e-mail est déjà utilisée.';
                $messageType = 'error';
            } else {
                $hashedPassword = hash('sha256', $motDePasse);
                $stmt = $pdo->prepare("INSERT INTO utilisateur (prenom, nom, adresse_mail, numero_telephone, mot_de_passe) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$prenom, $nom, $email, $telephone ?: null, $hashedPassword]);
                
                $message = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                $messageType = 'success';
            }
        } catch (PDOException $e) {
            $message = 'Erreur lors de la création du compte.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Mesures DHT11</title>
    <link rel="stylesheet" href="Connexion.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1 class="title">Connexion</h1>
            
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Connexion</div>
                <div class="tab" onclick="switchTab('register')">Inscription</div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div id="login-form" class="form-section active">
                <form method="POST">
                    <div class="form-group">
                        <input type="email" name="email" required>
                        <label>Adresse e-mail</label>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" required>
                        <label>Mot de passe</label>
                    </div>
                    <button type="submit" name="login" class="btn">Se connecter</button>
                </form>
            </div>

            <div id="register-form" class="form-section">
                <form method="POST">
                    <div class="row">
                        <div class="form-group">
                            <input type="text" name="prenom" required>
                            <label>Prénom</label>
                        </div>
                        <div class="form-group">
                            <input type="text" name="nom" required>
                            <label>Nom</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" required>
                        <label>Adresse e-mail</label>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="telephone">
                        <label>Numéro de téléphone (optionnel)</label>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" required minlength="6">
                        <label>Mot de passe</label>
                    </div>
                    <div class="form-group">
                        <input type="password" name="confirm_password" required minlength="6">
                        <label>Confirmer le mot de passe</label>
                    </div>
                    <button type="submit" name="register" class="btn">S'inscrire</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tabElement => {
                tabElement.classList.remove('active');
            });

            event.target.classList.add('active');
            document.getElementById(tab + '-form').classList.add('active');
        }

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value !== '') {
                    this.setAttribute('valid', '');
                } else {
                    this.removeAttribute('valid');
                }
            });
        });
    </script>
</body>
</html>