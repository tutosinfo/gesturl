<?php
// Nouveau fichier : import_links.php

// Inclure le fichier de configuration
include 'config.php';

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("La connexion a échoué : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des URLs
    $urls = $_POST['urls'];

    // Séparation des URLs par ligne
    $urlsArray = explode("\n", $urls);

    foreach ($urlsArray as $url) {
        $url = trim($url); // Enlever les espaces en début/fin de ligne
        if (!empty($url)) {
            if (strpos($url, '/feed/') !== false) {
                // Récupérer les URLs depuis le flux RSS
                $rss = simplexml_load_file($url);
                if ($rss) {
                    foreach ($rss->channel->item as $item) {
                        $feedUrl = (string) $item->link;
                        $stmt = $conn->prepare("INSERT INTO urls (id, url, hit) VALUES (NULL, ?, 0)");
                        $stmt->bind_param("s", $feedUrl);
                        $stmt->execute();
                    }
                }
            } else {
                // Insertion de l'URL fournie
                $stmt = $conn->prepare("INSERT INTO urls (id, url, hit) VALUES (NULL, ?, 0)");
                $stmt->bind_param("s", $url);
                $stmt->execute();
            }
        }
    }

    echo "<script>alert('Liens importés avec succès.'); window.location.href = 'index.php';</script>";
}

// Fermer la connexion
$conn->close();
?>