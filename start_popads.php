<?php
require_once 'config.php';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
$pdo = new PDO($dsn, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "SELECT * FROM api WHERE id = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $url = "https://www.popads.net/api/campaign_start";
    $api_key = $result['popads_api_key'];
    $campaign_id = $result['campaign_id'];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "key=$api_key&campaign_id=$campaign_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Erreur:' . curl_error($ch);
    } else {
        $response = json_decode($response, true);
        if ($response && $response['result'] == true) {
    echo "La campagne a été démarrée avec succès.";
} else {
    echo "Erreur lors de du démarrage de la campagne.";
}

    }

    curl_close($ch);
} else {
    echo "Aucune donnée trouvée pour l'ID 1.";
}
?>
