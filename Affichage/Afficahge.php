<?php
$conn = new mysqli("localhost", "root", "", "bdd");
$sql = "SELECT * FROM G7E ORDER BY Time DESC LIMIT 10";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Données G7E</title>
</head>
<body>
    <h2>Historique des degrés mesurés</h2>
    <table border="1">
        <tr><th>Heure</th><th>Degrés</th></tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['Time'] ?></td>
            <td><?= $row['Degrees'] ?>°</td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
