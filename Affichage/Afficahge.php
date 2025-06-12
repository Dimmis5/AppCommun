<?php
require_once 'connect.php';

try {
    $stmt = $pdo->query("SELECT * FROM G7E ORDER BY Time DESC LIMIT 10");
    $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Données G7E</title>
</head>
<body>
    <h2>Historique des degrés récupérés</h2>
    <table border="1">
        <tr><th>Heure</th><th>Degrés</th></tr>
        <?php foreach ($resultats as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['Time']) ?></td>
            <td><?= htmlspecialchars($row['degrees']) ?>°</td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
