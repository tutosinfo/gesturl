<?php
require "config.php";

// Création de l'objet de connexion
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
$conn = new PDO($dsn, $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sélectionne une URL aléatoire dans la base de données
$sql = "SELECT id, url FROM urls ORDER BY RAND() LIMIT 1";
$result = $conn->query($sql);
$row = $result->fetch(PDO::FETCH_ASSOC);
$random_id = $row['id'];
$random_url = $row['url'];

// Incrémente le compteur de 'hits' pour l'URL sélectionnée
$sql = "UPDATE urls SET hit = hit + 1 WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $random_id]);

// Redirige l'utilisateur vers l'URL aléatoire sélectionnée
header("Location: {$random_url}");
exit;
?>
