<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation</title>
    <link rel="stylesheet" href="css/style-install.css">
</head>
<body>

<?php
if (isset($_POST['submit'])) {
    $host = $_POST['host'];
    $dbname = $_POST['dbname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $popads_api_key = $_POST['popads_api_key'];
    $serper_api_key = $_POST['serper_api_key'];

    // Génération du contenu du fichier de configuration
    $configContent = "<?php\n";
    $configContent .= "\$host = '" . addslashes($host) . "';\n";
    $configContent .= "\$dbname = '" . addslashes($dbname) . "';\n";
    $configContent .= "\$username = '" . addslashes($username) . "';\n";
    $configContent .= "\$password = '" . addslashes($password) . "';\n";
    $configContent .= "?>";

    file_put_contents('config.php', $configContent);

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Création de la table 'urls'
        $sql = "CREATE TABLE IF NOT EXISTS `urls` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `url` VARCHAR(2048) NOT NULL,
            `hit` INT(11) NOT NULL DEFAULT 0,
            `is_indexed` TINYINT(1) DEFAULT NULL,
            `last_checked` DATETIME DEFAULT NULL
        )";
        $pdo->exec($sql);

        // Création de la table 'api'
        $sql = "CREATE TABLE IF NOT EXISTS `api` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `popads_api_key` VARCHAR(255) DEFAULT NULL,
            `campaign_id` INT(11) UNSIGNED DEFAULT NULL,
            `serper_api_key` VARCHAR(255) DEFAULT NULL
        )";
        $pdo->exec($sql);

        // Vérifier et ajouter la colonne 'serper_api_key' dans la table 'api' si elle n'existe pas
        $columns = $pdo->query("SHOW COLUMNS FROM `api` LIKE 'serper_api_key'")->fetchAll();
        if (count($columns) === 0) {
            $pdo->exec("ALTER TABLE `api` ADD COLUMN `serper_api_key` VARCHAR(255) DEFAULT NULL AFTER `campaign_id`");
        }

        // Vérifier et ajouter les colonnes 'is_indexed' et 'last_checked' dans la table 'urls' si elles n'existent pas
        $columns = $pdo->query("SHOW COLUMNS FROM `urls` LIKE 'is_indexed'")->fetchAll();
        if (count($columns) === 0) {
            $pdo->exec("ALTER TABLE `urls` ADD COLUMN `is_indexed` TINYINT(1) DEFAULT NULL, ADD COLUMN `last_checked` DATETIME DEFAULT NULL");
        }

        // Vérifier si une ligne existe déjà dans la table 'api'
        $stmt = $pdo->query("SELECT COUNT(*) FROM `api`");
        $apiCount = $stmt->fetchColumn();

        $campaign_id = null;

        // Si une clé API PopAds est fournie, récupérer l'ID de la campagne
        if (!empty($popads_api_key)) {
            // Récupérer les campagnes via l'API PopAds
            $popads_url = "https://www.popads.net/api/campaign_list?key=" . urlencode($popads_api_key);
            $popads_response = file_get_contents($popads_url);
            $popads_data = json_decode($popads_response, true);

            if (isset($popads_data['campaigns']) && !empty($popads_data['campaigns'])) {
                $campaign_id = $popads_data['campaigns'][0]['id'];
                echo "<p>Clé API PopAds enregistrée : " . htmlspecialchars($popads_api_key, ENT_QUOTES, 'UTF-8') . "</p>";
                echo "<p>ID de la campagne PopAds enregistré : " . htmlspecialchars($campaign_id, ENT_QUOTES, 'UTF-8') . "</p>";
            } else {
                echo "<p>Erreur lors de la récupération des données de l'API PopAds. Veuillez vérifier votre clé API et réessayer.</p>";
                $popads_api_key = null;
            }
        } else {
            echo "<p>Aucune clé API PopAds n'a été fournie. Vous pourrez la configurer ultérieurement.</p>";
        }

        // Vérifier si une clé API serper.dev a été fournie
        if (!empty($serper_api_key)) {
            echo "<p>Clé API serper.dev enregistrée : " . htmlspecialchars($serper_api_key, ENT_QUOTES, 'UTF-8') . "</p>";
        } else {
            echo "<p>Aucune clé API serper.dev n'a été fournie. Vous pourrez la configurer ultérieurement.</p>";
            $serper_api_key = null;
        }

        if ($apiCount > 0) {
            // Mettre à jour la ligne existante dans la table 'api'
            $sql = "UPDATE `api` SET `popads_api_key` = :popads_api_key, `campaign_id` = :campaign_id, `serper_api_key` = :serper_api_key WHERE `id` = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':popads_api_key' => $popads_api_key,
                ':campaign_id' => $campaign_id,
                ':serper_api_key' => $serper_api_key
            ]);
            echo "<p>La table 'api' a été mise à jour avec les nouvelles clés API.</p>";
        } else {
            // Insérer une nouvelle ligne dans la table 'api'
            $sql = "INSERT INTO `api` (`popads_api_key`, `campaign_id`, `serper_api_key`) VALUES (:popads_api_key, :campaign_id, :serper_api_key)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':popads_api_key' => $popads_api_key,
                ':campaign_id' => $campaign_id,
                ':serper_api_key' => $serper_api_key
            ]);
            echo "<p>La table 'api' a été créée avec les clés API.</p>";
        }

        echo "<p>Installation réussie ! Les tables 'urls' et 'api' ont été créées ou mises à jour.</p>";

        // Renommer le fichier d'installation pour des raisons de sécurité
        rename('install.php', 'install.bak');
        echo "<p>Le fichier d'installation a été renommé en 'install.bak' pour des raisons de sécurité.</p>";
        echo '<p><a href="index.php">Accéder à GestURL</a></p>';

    } catch (PDOException $e) {
        echo "<p>Erreur lors de la connexion à la base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    }
} else {
?>

    <h1>Installation de l'outil de gestion d'URLs</h1>
    <form action="install.php" method="post">
        <label for="host">Hôte :</label>
        <input type="text" name="host" id="host" required><br>

        <label for="dbname">Nom de la base de données :</label>
        <input type="text" name="dbname" id="dbname" required><br>

        <label for="username">Nom d'utilisateur :</label>
        <input type="text" name="username" id="username" required><br>

        <label for="password">Mot de passe :</label>
        <input type="password" name="password" id="password" required><br>

        <label for="popads_api_key">Clé API PopAds (facultatif) : <a href="guide_api_popads.html" target="_blank">Voir le Guide de création de la clé API</a></label>
        <input type="text" name="popads_api_key" id="popads_api_key"><br>

        <label for="serper_api_key">Clé API serper.dev (facultatif) :</label>
        <input type="text" name="serper_api_key" id="serper_api_key"><br>

        <input type="submit" name="submit" value="Installer">
    </form>

<?php
}
?>
</body>
</html>