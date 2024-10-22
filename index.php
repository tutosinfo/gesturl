 <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des URLs</title>
    <!-- Meta tag pour la réactivité mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="img/favicon.ico" />
    <!-- Feuille de style personnalisée -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Icons Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
<!-- Barre de navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">GestURL</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Basculer la navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="container my-5">
    <?php
    require_once 'config.php';

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger" role="alert">Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        exit;
    }

     // Récupérer les clés API depuis la base de données
    $sql = "SELECT * FROM `api` LIMIT 1";
    $stmt = $pdo->query($sql);
    if ($apiRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $popads_api_key = $apiRow['popads_api_key'];
        $campaign_id = $apiRow['campaign_id'];
        $serper_api_key = $apiRow['serper_api_key'];
    } else {
        $popads_api_key = null;
        $campaign_id = null;
        $serper_api_key = null;
    }

    // Traitement des formulaires et affichage des messages
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['clear_all'])) {
            // Confirmation avant suppression
            echo '<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmModalLabel">Confirmation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                </div>
                                <div class="modal-body">
                                    Êtes-vous sûr de vouloir supprimer toutes les URLs ?
                                </div>
                                <div class="modal-footer">
                                    <form method="post">
                                        <button type="submit" name="confirm_clear_all" class="btn btn-danger">Oui, supprimer</button>
                                    </form>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </div>
                        </div>
                    </div>';
        }

        if (isset($_POST['confirm_clear_all'])) {
            try {
                $sql = "DELETE FROM `urls`";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            Toutes les URLs ont été supprimées avec succès!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                          </div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de la suppression des URLs : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }

        if (isset($_POST['submit'])) {
            $url = $_POST['url'];

            try {
                $sql = "INSERT INTO `urls` (`url`) VALUES (:url)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':url' => $url]);

                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            URL ajoutée avec succès!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                          </div>';
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de l\'ajout de l\'URL : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
            }
        }

        if (isset($_POST['import'])) {
        $urls = trim($_POST['urls']); // Trim pour supprimer les espaces en début et fin

            if (empty($urls)) {
                // Si le champ est vide ou ne contient que des espaces
                echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        Le champ des URLs est vide. Veuillez entrer au moins une URL.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                      </div>';
            } else {
            $urlsArray = explode("\n", $urls);
            $urlsImported = 0;

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
                                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de l\'ajout de l\'URL du flux : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                                }
                            }
                        } else {
                            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">Impossible de charger le flux RSS: ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</div>';
                        }
                    } else {
                        try {
                            $sql = "INSERT INTO `urls` (`url`) VALUES (:url)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':url' => $url]);
                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de l\'ajout de l\'URL : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                        }
                    }
                }
            }

            if ($urlsImported > 0) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            ' . $urlsImported . ' lien(s) importé(s) avec succès!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                          </div>';
                } else {
                    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                            Aucune URL valide n\'a été trouvée pour l\'importation.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                          </div>';
                }
        }
    }

    }

    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];

        try {
            $sql = "DELETE FROM `urls` WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        URL supprimée avec succès!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                      </div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de la suppression de l\'URL : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    if (isset($_GET['reset'])) {
        $id = (int)$_GET['reset'];

        try {
            $sql = "UPDATE `urls` SET `hit` = 0 WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Le compteur de hits de l\'URL a été réinitialisé avec succès!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                      </div>';
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de la réinitialisation du compteur de hits : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
    ?>

    <!-- Onglets pour naviguer entre les sections -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="add-url-tab" data-bs-toggle="tab" data-bs-target="#add-url" type="button" role="tab" aria-controls="add-url" aria-selected="true">Ajouter une URL</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="import-urls-tab" data-bs-toggle="tab" data-bs-target="#import-urls" type="button" role="tab" aria-controls="import-urls" aria-selected="false">Importer des URLs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="urls-list-tab" data-bs-toggle="tab" data-bs-target="#urls-list" type="button" role="tab" aria-controls="urls-list" aria-selected="false">Liste des URLs</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="popads-info-tab" data-bs-toggle="tab" data-bs-target="#popads-info" type="button" role="tab" aria-controls="popads-info" aria-selected="false">Campagne PopAds</button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <!-- Formulaire d'ajout d'une URL -->
        <div class="tab-pane fade show active" id="add-url" role="tabpanel" aria-labelledby="add-url-tab">
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Ajouter une URL</h5>
                </div>
                <div class="card-body">
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="url" class="form-label">URL :</label>
                            <input type="url" name="url" id="url" class="form-control" required placeholder="https://example.com">
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Formulaire pour ajouter des URLs en masse -->
        <div class="tab-pane fade" id="import-urls" role="tabpanel" aria-labelledby="import-urls-tab">
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Importer des URLs en masse</h5>
                </div>
                <div class="card-body">
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="urls" class="form-label">Collez vos liens ici (un par ligne ou un lien /feed/) :</label>
                            <textarea id="urls" name="urls" rows="10" class="form-control" placeholder="https://example.com/page1
https://example.com/page2
..."></textarea>
                        </div>
                        <button type="submit" name="import" class="btn btn-primary">Importer</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Table des URLs -->
        <div class="tab-pane fade" id="urls-list" role="tabpanel" aria-labelledby="urls-list-tab">
            <div class="d-flex justify-content-between align-items-center mt-4">
                <h5>Liste des URLs</h5>
                <!-- Bouton pour supprimer toutes les URLs -->
                <form action="index.php" method="post">
                    <button type="submit" name="clear_all" class="btn btn-danger">Supprimer toutes les URLs</button>
                </form>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>Hits</th>
                        <th>Indexed ?</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    try {
                        $sql = "SELECT * FROM `urls` ORDER BY `id` DESC";
                        $stmt = $pdo->query($sql);

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $safe_id = htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8');
                            $safe_url = htmlspecialchars($row['url'], ENT_QUOTES, 'UTF-8');
                            $safe_hit = htmlspecialchars($row['hit'], ENT_QUOTES, 'UTF-8');

                            // Vérification de l'indexation avec serper.dev via cURL
                            if (!empty($serper_api_key)) {
                                // Vérification si la dernière vérification date de plus de 24 heures
                                $currentTime = new DateTime();
                                $lastChecked = isset($row['last_checked']) ? new DateTime($row['last_checked']) : null;

                                $shouldCheck = true;
                                if ($lastChecked) {
                                    $interval = $currentTime->diff($lastChecked);
                                    $hoursPassed = ($interval->days * 24) + $interval->h;
                                    if ($hoursPassed < 24 && isset($row['is_indexed'])) {
                                        $shouldCheck = false;
                                        $isIndexed = (bool)$row['is_indexed'];
                                    }
                                }

                                if ($shouldCheck) {
                                    $query = 'site:' . $row['url'];
                                    $postData = [
                                        'q' => $query,
                                        'location' => 'France',
                                        'gl' => 'fr',
                                        'hl' => 'fr'
                                    ];

                                    $ch = curl_init('https://google.serper.dev/search');
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Content-Type: application/json',
                                        'X-API-KEY: ' . $serper_api_key
                                    ]);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                                    $response = curl_exec($ch);
                                    $curlError = curl_error($ch);
                                    curl_close($ch);

                                    if ($response !== false && empty($curlError)) {
                                        $result = json_decode($response, true);
                                        // Vérifier si l'URL exacte est présente dans les résultats organiques
                                        $isIndexed = false;
                                        if (!empty($result['organic'])) {
                                            foreach ($result['organic'] as $organicResult) {
                                                if (isset($organicResult['link'])) {
                                                    // Normaliser les URLs pour la comparaison
                                                    $checkedUrl = rtrim($row['url'], '/');
                                                    $resultUrl = rtrim($organicResult['link'], '/');
                                                    if ($resultUrl === $checkedUrl) {
                                                        $isIndexed = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        // En cas d'erreur, considérer l'URL comme non indexée
                                        $isIndexed = false;
                                    }

                                    // Mise à jour de la base de données avec le statut d'indexation
                                    $sqlUpdate = "UPDATE `urls` SET `is_indexed` = :is_indexed, `last_checked` = :last_checked WHERE `id` = :id";
                                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                                    $stmtUpdate->execute([
                                        ':is_indexed' => $isIndexed ? 1 : 0,
                                        ':last_checked' => $currentTime->format('Y-m-d H:i:s'),
                                        ':id' => $row['id']
                                    ]);
                                }
                            } else {
                                // Si la clé API serper.dev n'est pas fournie
                                $isIndexed = null;
                            }

                            // Affichage de l'icône d'indexation
                            if ($isIndexed === true) {
                                $indexStatus = '<span class="text-success"><i class="bi bi-check-circle"></i> Oui</span>';
                            } elseif ($isIndexed === false) {
                                $indexStatus = '<span class="text-danger"><i class="bi bi-x-circle"></i> Non</span>';
                            } else {
                                $indexStatus = '<span class="text-muted"><i class="bi bi-question-circle"></i> N/A</span>';
                            }

                            $encoded_query = urlencode('site:' . $row['url']);
                            $google_search_url = "https://www.google.fr/search?q={$encoded_query}";
                            $safe_google_search_url = htmlspecialchars($google_search_url, ENT_QUOTES, 'UTF-8');

                            echo "<tr>";
                            echo "<td>{$safe_id}</td>";
                            echo "<td><a href=\"{$safe_url}\" target=\"_blank\">{$safe_url}</a></td>";
                            echo "<td>{$safe_hit}</td>";
                            echo "<td>{$indexStatus}</td>";
                            echo "<td>
                                            <a href=\"index.php?delete={$safe_id}\" class=\"btn btn-sm btn-danger\" onclick=\"return confirm('Êtes-vous sûr de vouloir supprimer cette URL ?');\"><i class=\"bi bi-trash\"></i></a>
                                            <a href=\"index.php?reset={$safe_id}\" class=\"btn btn-sm btn-warning text-white\"><i class=\"bi bi-arrow-counterclockwise\"></i></a>
                                          </td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">Erreur lors de la récupération des URLs : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Informations de la campagne PopAds -->
        <div class="tab-pane fade" id="popads-info" role="tabpanel" aria-labelledby="popads-info-tab">
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Informations de la campagne PopAds</h5>
                </div>
                <div class="card-body">
                    <?php
                    require_once 'popads_info.php';

                    $sql = "SELECT * FROM `api`";
                    $stmt = $pdo->query($sql);

                    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $popads_api_key = htmlspecialchars($row['popads_api_key'], ENT_QUOTES, 'UTF-8');
                        $campaign_id = htmlspecialchars($row['campaign_id'], ENT_QUOTES, 'UTF-8');

                        $campaignInfo = getPopAdsCampaignInfo($popads_api_key, $campaign_id);

                        if ($campaignInfo) {
                            echo "<p><strong>Clé API PopAds :</strong> {$popads_api_key}</p>";
                            echo "<p><strong>ID de la campagne :</strong> {$campaign_id}</p>";
                            echo "<p><strong>État de la campagne :</strong> " . htmlspecialchars($campaignInfo['status'], ENT_QUOTES, 'UTF-8') . "</p>";
                            echo "<p><strong>Budget restant :</strong> " . htmlspecialchars($campaignInfo['budget'], ENT_QUOTES, 'UTF-8') . " $</p>";
                        } else {
                            echo '<div class="alert alert-warning" role="alert">Erreur lors de la récupération des informations de la campagne. Veuillez vérifier la clé API et l\'ID de la campagne.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-warning" role="alert">Aucune information de l\'API PopAds trouvée. Veuillez vérifier la table "api".</div>';
                    }
                    ?>
                    <a href="index.php" class="btn btn-secondary mt-3">Actualiser Les Infos</a>
                    <?php
                    $url_dir = dirname($_SERVER['REQUEST_URI']);
                    if ($url_dir == '/') {
                        $url_dir = '';
                    }
                    $full_url = 'https://' . $_SERVER['HTTP_HOST'] . $url_dir . '/random.php';
                    $safe_full_url = htmlspecialchars($full_url, ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="input-group mt-3">
                        <span class="input-group-text">Lien vers random.php :</span>
                        <input type="text" class="form-control" id="randomUrl" value="<?php echo $safe_full_url; ?>" readonly>
                        <button class="btn btn-outline-secondary" onclick="copyToClipboard(<?php echo json_encode($full_url); ?>)"><i class="bi bi-clipboard"></i> Copier</button>
                    </div>
                </div>
            </div>

            <!-- Actions sur la campagne POP ADS -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5>Actions sur la campagne POP ADS</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button id="stop_popads" class="btn btn-danger"><i class="bi bi-stop-circle"></i> STOP POPADS</button>
                        <button id="start_popads" class="btn btn-success ms-md-2"><i class="bi bi-play-circle"></i> START POPADS</button>
                    </div>
                    <p class="mt-3"><a href="guide_api_popads.html" target="_blank">Voir le Guide de création de la clé API</a></p>
                </div>
            </div>
        </div>
    </div> <!-- Fin des onglets -->
</div> <!-- Fin du container -->

<!-- Footer -->
<footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container text-center">
        GestURL Version 4.0.1 (Octobre 2024)
    </div>
</footer>

<!-- Bootstrap JS et dépendances -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Scripts JavaScript existants -->
<script src="js/stop_popads.js"></script>
<script src="js/start_popads.js"></script>
<script src="js/copier.js"></script>

<!-- Script pour afficher la modal de confirmation -->
<script>
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    if (document.getElementById('confirmModal')) {
        confirmModal.show();
    }
</script>
</body>
</html>