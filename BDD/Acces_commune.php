<?php
$host = 'romantcham.fr';
$dbname = 'Domotic_db';
$user = 'G7E';
$password = 'afyubr';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Tables dans la base '$dbname' :</h2>";
    foreach ($tables as $table) {
        echo "<h3>Table : $table</h3>";

        $dataStmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'><tr>";
            foreach (array_keys($rows[0]) as $col) {
                echo "<th>" . htmlspecialchars($col) . "</th>";
            }
            echo "</tr>";

            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Pas de données dans cette table.</p>";
        }
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
