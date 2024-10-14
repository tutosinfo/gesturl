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
            `hit` INT(11) NOT NULL DEFAULT 0
        )";
        $pdo->exec($sql);

        // Création de la table 'api' avec des colonnes facultatives
        $sql = "CREATE TABLE IF NOT EXISTS `api` (
            `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `popads_api_key` VARCHAR(255) DEFAULT NULL,
            `campaign_id` INT(11) UNSIGNED DEFAULT NULL
        )";
        $pdo->exec($sql);

        // Vérifier si une clé API PopAds a été fournie
        if (!empty($popads_api_key)) {
            $popads_url = "https://www.popads.net/api/campaign_list?key=" . urlencode($popads_api_key);
            $popads_response = file_get_contents($popads_url);
            $popads_data = json_decode($popads_response, true);

            if (isset($popads_data['campaigns']) && !empty($popads_data['campaigns'])) {
                $campaign_id = $popads_data['campaigns'][0]['id'];

                $sql = "INSERT INTO `api` (`popads_api_key`, `campaign_id`) VALUES (:popads_api_key, :campaign_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':popads_api_key' => $popads_api_key, ':campaign_id' => $campaign_id]);

                echo "<p>Installation réussie ! La table 'urls' et la table 'api' ont été créées.</p>";
                echo "<p>Clé API PopAds enregistrée : " . htmlspecialchars($popads_api_key, ENT_QUOTES, 'UTF-8') . "</p>";
                echo "<p>ID de la campagne PopAds enregistré : " . htmlspecialchars($campaign_id, ENT_QUOTES, 'UTF-8') . "</p>";
            } else {
                echo "<p>Erreur lors de la récupération des données de l'API PopAds. Veuillez vérifier votre clé API et réessayer.</p>";
            }
        } else {
            // Si aucune clé API n'est fournie, ignorer les étapes liées à l'API
            echo "<p>Installation réussie ! La table 'urls' et la table 'api' ont été créées.</p>";
            echo "<p>Aucune clé API PopAds n'a été fournie. Vous pourrez la configurer ultérieurement.</p>";
        }

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

        <input type="submit" name="submit" value="Installer">
    </form>

<?php
}
?>
</body>
</html>
