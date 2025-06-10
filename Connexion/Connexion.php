<?php
session_start();

define('USER_FILE', 'users.json');

function load_users() {
    if (!file_exists(USER_FILE)) {
        file_put_contents(USER_FILE, json_encode([]));
    }
    $data = file_get_contents(USER_FILE);
    return json_decode($data, true);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$username || !$password) {
        $errors[] = "Veuillez remplir tous les champs;.";
    } else {
        $users = load_users();
        if (!isset($users[$username]) || !password_verify($password, $users[$username])) {
            $errors[] = "Nom d'utilisateur ou mot de passe incorrect.";
        } else {
            $_SESSION['user'] = $username;
            header('Location: welcome.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Connexion</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
    <h1>Connexion</h1>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required />
        <input type="password" name="password" placeholder="Mot de passe" required />
        <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore inscrit ? <a href="inscription.php">Créer un compte</a></p>
</div>
</body>
</html>