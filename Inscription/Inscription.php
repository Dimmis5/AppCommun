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

function save_users($users) {
    file_put_contents(USER_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (!$username || !$password || !$password_confirm) {
        $errors[] = "Tous les champs sont obligatoires.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } else {
        $users = load_users();
        if (isset($users[$username])) {
            $errors[] = "Nom d'utilisateur déjà pris.";
        } else {
            $users[$username] = password_hash($password, PASSWORD_DEFAULT);
            save_users($users);
            $success = "Inscription réussie. Vous pouvez maintenant vous connecter.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Inscription</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
    <h1>Inscription</h1>

    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required />
        <input type="password" name="password" placeholder="Mot de passe" required />
        <input type="password" name="password_confirm" placeholder="Confirmer mot de passe" required />
        <button type="submit">S'inscrire</button>
    </form>

    <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
</div>
</body>
</html>