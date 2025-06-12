<?php
require_once 'connect.php';
try{
  $pdo = new PDO ('mysql:host=localhost;dbname=bdd;charset=utf8','root','');
  $stmt=$pdo->query("SELECT* FROM G7E ORDER BY Time DESC LIMIT 1");
  $row= $stmt->fetch(PDO::FETCH_ASSOC);
  echo json_encode([
    'degrees'=>$row['Degrees'],
    'time'=>$row['Time']
   ]);
} catch (PDOException $e){
   echo json_ecode(['error'=> 'Erreur de connexion']);
}
?>
