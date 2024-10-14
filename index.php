<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des URLs</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico" />
</head>
<body>
    <h1>Ajouter une URL</h1>
    <form action="index.php" method="post">
        <label for="url">URL :</label>
        <input type="text" name="url" id="url" required>
        <input type="submit" name="submit" value="Ajouter">
    </form>

    <!-- Formulaire pour ajouter des URLs en masse -->
    <form action="index.php" method="post">
        <label for="urls">Collez vos liens ici (un par ligne ou un lien /feed/) :</label><br>
        <textarea id="urls" name="urls" rows="10" cols="50"></textarea><br>
        <input type="submit" name="import" value="Importer">
    </form>

    <?php
    require_once 'config.php';

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    if (isset($_POST['clear_all'])) {
        try {
            $sql = "DELETE FROM `urls`";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            echo "<p>Toutes les URLs ont été supprimées avec succès!</p>";
        } catch (PDOException $e) {
            echo "<p>Erreur lors de la suppression des URLs : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    if (isset($_POST['submit'])) {
        $url = $_POST['url'];

        try {
            $sql = "INSERT INTO `urls` (`url`) VALUES (:url)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':url' => $url]);

            echo "<p>URL ajoutée avec succès!</p>";
        } catch (PDOException $e) {
            echo "<p>Erreur lors de l'ajout de l'URL : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    if (isset($_POST['import'])) {
        $urls = $_POST['urls'];
        $urlsArray = explode("\n", $urls);

        foreach ($urlsArray as $url) {
            $url = trim($url);
            if (!empty($url)) {
                if (strpos($url, '/feed') !== false) {
                    $rss = @simplexml_load_file($url);
                    if ($rss) {
                        foreach ($rss->channel->item as $item) {
                            $feedUrl = (string) $item->link;
                            try {
                                $sql = "INSERT INTO `urls` (`url`) VALUES (:url)";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([':url' => $feedUrl]);
                            } catch (PDOException $e) {
                                echo "<p>Erreur lors de l'ajout de l'URL du flux : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
                            }
                        }
                    } else {
                        echo "<p>Impossible de charger le flux RSS: " . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</p>";
                    }
                } else {
                    try {
                        $sql = "INSERT INTO `urls` (`url`) VALUES (:url)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([':url' => $url]);
                    } catch (PDOException $e) {
                        echo "<p>Erreur lors de l'ajout de l'URL : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
                    }
                }
            }
        }

        echo "<p>Liens importés avec succès!</p>";
    }

    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];

        try {
            $sql = "DELETE FROM `urls` WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            echo "<p>URL supprimée avec succès!</p>";
        } catch (PDOException $e) {
            echo "<p>Erreur lors de la suppression de l'URL : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    if (isset($_GET['reset'])) {
        $id = (int)$_GET['reset'];

        try {
            $sql = "UPDATE `urls` SET `hit` = 0 WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            echo "<p>Le compteur de hits de l'URL a été réinitialisé avec succès!</p>";
        } catch (PDOException $e) {
            echo "<p>Erreur lors de la réinitialisation du compteur de hits : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }
    ?>
    <form action="index.php" method="post">
        <input type="submit" name="clear_all" value="Supprimer toutes les URLs" onclick="return confirm('Êtes-vous sûr de vouloir supprimer toutes les URLs ?');">
    </form>
    <table>
        <tr>
            <th>ID</th>
            <th>URL</th>
            <th>Hits</th>
            <th>Site:</th>
            <th>Action</th>
        </tr>
        <?php
        try {
            $sql = "SELECT * FROM `urls`";
            $stmt = $pdo->query($sql);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $safe_id = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
                $safe_url = htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8');
                $safe_hit = htmlspecialchars($row['hit'], ENT_QUOTES, 'UTF-8');
                $encoded_query = urlencode('site:' . $row['url']);
                $google_search_url = "https://www.google.fr/search?q={$encoded_query}";
                $safe_google_search_url = htmlspecialchars($google_search_url, ENT_QUOTES, 'UTF-8');
                echo "<tr>";
                echo "<td>{$safe_id}</td>";
                echo "<td>{$safe_url}</td>";
                echo "<td>{$safe_hit}</td>";
                echo "<td><a href='{$safe_google_search_url}' target='_blank'>Voir Indexation</a></td>";
                echo "<td><a href='index.php?delete={$safe_id}'>Supprimer</a> | <a href='index.php?reset={$safe_id}'>Réinitialiser</a></td>";
                echo "</tr>";
            }
        } catch (PDOException $e) {
            echo "<p>Erreur lors de la récupération des URLs : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
        }
        ?>
    </table>

    <h2>Informations de la campagne PopAds</h2>
    <div id="popads_info">
        <?php
        require_once 'popads_info.php';

        $sql = "SELECT * FROM `api`";
        $stmt = $pdo->query($sql);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $popads_api_key = htmlspecialchars($row['popads_api_key'], ENT_QUOTES, 'UTF-8');
            $campaign_id = htmlspecialchars($row['campaign_id'], ENT_QUOTES, 'UTF-8');

            $campaignInfo = getPopAdsCampaignInfo($popads_api_key, $campaign_id);

            if ($campaignInfo) {
                echo "<p>Clé API PopAds : {$popads_api_key}</p>";
                echo "<p>ID de la campagne : {$campaign_id}</p>";
                echo "<p>État de la campagne : " . htmlspecialchars($campaignInfo['status'], ENT_QUOTES, 'UTF-8') . "</p>";
                echo "<p>Budget restant : " . htmlspecialchars($campaignInfo['budget'], ENT_QUOTES, 'UTF-8') . " $</p>";
            } else {
                echo "<p>Erreur lors de la récupération des informations de la campagne. Veuillez vérifier la clé API et l'ID de la campagne.</p>";
            }
        } else {
            echo "<p>Aucune information de l'API PopAds trouvée. Veuillez vérifier la table 'api'.</p>";
        }
        ?>
        <a href="index.php" class="btn-actu">Actualiser Les Infos</a>
        <?php
        $url_dir = dirname($_SERVER['REQUEST_URI']);
        if ($url_dir == '/') {
            $url_dir = '';
        }
        $full_url = 'https://' . $_SERVER['HTTP_HOST'] . $url_dir . '/random.php';
        $safe_full_url = htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8');
        ?>
        <p>
            Lien vers random.php (l'url à définir dans POPADS) :
            <a href="<?php echo $safe_full_url; ?>">
                <?php echo $safe_full_url; ?>
            </a>
            <button onclick="copyToClipboard(<?php echo json_encode($full_url); ?>)">COPIER</button>
        </p>
    </div>

    <div id="popads_actions">
        <h2>Actions sur la campagne POP ADS</h2>
        <button id="stop_popads" class="btn btn-danger">STOP POPADS</button> - <button id="start_popads" class="btn btn-success">START POPADS</button>
        <br>
        <p><a href="guide_api_popads.html" target="_blank">Voir le Guide de création de la clé API</a></p>
    </div>

    <script src="js/stop_popads.js"></script>
    <script src="js/start_popads.js"></script>
    <script src="js/copier.js"></script>

    <footer style="position: fixed; left: 0; bottom: 0; width: 100%; background-color: #f8f9fa; text-align: center; padding: 10px;">
GestURL Version 4.0.1 (Octobre 2024)
</footer>
</body>
</html>
