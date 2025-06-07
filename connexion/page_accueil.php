<?php
session_start();
if (!isset($_SESSION['login_ut'])) {
    header("Location: connexion.php");
    exit();
}

$isAdmin = isset($_SESSION['statut']) && in_array($_SESSION['statut'], ['administrateur', 'superadministrateur']);
$isSuperAdmin = isset($_SESSION['statut']) && $_SESSION['statut'] === 'superadministrateur';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - <?= htmlspecialchars($_SESSION['login_ut']) ?></title>
    <link rel="stylesheet" href="./connexion.css">
</head>
<body class="dashboard">
        <div class="sidebar">
            <div class="top-section">
                <h2><?= htmlspecialchars($_SESSION['login_ut']) ?></h2>
                <nav class="sidebar-nav">
                    <a href="../Mon compte/mon_compte_user.php">👤 Mon compte</a>
                    <a href="prendre_photo.php">📷 Prendre une photo</a>
                    <a href="page_accueil.php">🖼️ Galerie</a>
                    <a href="../Favoris/favoris.php">⭐ Favoris</a>
                    <a href="../Recents/recents.php">🕘 Récents</a>
                    <a href="../Corbeille/corbeille.php">🗑️ Corbeille</a>

                    <?php if ($isAdmin): ?>
                        <hr>
                        <a href="../admin/archivage.php">📂 Archivage</a>
                        <a href="../admin/gestion_utilisateurs.php">👥 Utilisateurs</a>
                        <a href="../admin/logs.php">📝 Logs</a>
                    <?php endif; ?>

                    <?php if ($isSuperAdmin): ?>
                        <a href="../superadmin/fichiers_systeme.php">🛠️ Fichiers système</a>
                    <?php endif; ?>
                </nav>
            </div>

            <form method="POST" action="deconnexion.php" class="logout-section">
                <button type="submit" class="logout">⚪ Déconnexion</button>
            </form>
            <div class="storage-module">
                <h3>Stockage</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="storage-text" id="storageText">Calcul en cours...</div>
            </div>

        </div>
    <div class="main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>Galerie photos</h1>
            </div>
            <div class="top-bar-right">
                <h1 id="clock">-- 🕒</h1>
            </div>
        </div>
        <div class="search-bar">
            <form method="GET" action="" class="left-tools">
                <input type="text" name="texte_filtre" class="search-input" placeholder="Rechercher..." value="<?= isset($_GET['texte_filtre']) ? htmlspecialchars($_GET['texte_filtre']) : '' ?>">
                <button type="submit" class="filter-button">🔍 Rechercher</button>
            </form>
            <div class="right-tools">
                <button type="button" class="filter-button" onclick="ouvrirPopup()">🛠️ Filtres</button>

                <div class="dropdown">
                    <button type="button" class="filter-button" onclick="toggleSort()">🎛️ Trier</button>
                    <div class="dropdown-menu" id="sortOptions">
                        <a href="?sort=date_desc">📅 Plus récentes</a>
                        <a href="?sort=date_asc">📅 Plus anciennes</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="actions-wrapper">
        <form method="POST" action="page_accueil.php">
            <div class="actions" style="margin-bottom: 20px;">
                <button type="submit" name="del">🗑️ Supprimer</button>
                <button type="submit" name="telecharger">⬇️ Télécharger</button>
                <button type="button" onclick="deselectionnerTout()">❌ Tout dé-sélectionner</button>
            </div>
            <div class="photo-gallery">
            <?php
            include('../outils/connex_bd.php');
            $conn = createConnection();

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['telecharger']) && !empty($_POST['photos'])) {
                $ids = $_POST['photos'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                // Récupération des fichiers
                $sql = "SELECT url_photo FROM photos WHERE id_photo IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                $stmt->execute($ids);
                $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if ($photos) {
                    $zip = new ZipArchive();
                    $zipFilename = tempnam(sys_get_temp_dir(), 'photos_') . '.zip';

                    if ($zip->open($zipFilename, ZipArchive::CREATE) === TRUE) {
                        foreach ($photos as $file) {
                            $filePath = realpath(__DIR__ . '/../photos/' . $file);
                            if (file_exists($filePath)) {
                                $zip->addFile($filePath, basename($filePath));
                            }
                        }
                        $zip->close();

                        // 🔐 Log de téléchargement
                        $login = $_SESSION['login_ut'];
                        $desc = "Téléchargement de " . count($photos) . " photo(s)";
                        $logStmt = $conn->prepare("INSERT INTO logs (login_ut, description, date_heure) VALUES (?, ?, NOW())");
                        $logStmt->execute([$login, $desc]);

                        // 🔽 Téléchargement ZIP
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="mes_photos.zip"');
                        header('Content-Length: ' . filesize($zipFilename));
                        readfile($zipFilename);
                        unlink($zipFilename);
                        exit;
                    } else {
                        echo "<p style='color:red;'>❌ Erreur création archive ZIP.</p>";
                    }
                }
                }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del']) && !empty($_POST['photos'])) {
                $ids = $_POST['photos'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));

                $sql = "UPDATE photos SET date_supprimee = NOW(), supprime_par = ? WHERE id_photo IN ($placeholders)";
                $params = array_merge([$_SESSION['login_ut']], $ids);
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                echo "<p style='color:lime;'>✅ Photos déplacées dans la corbeille.</p>";
            }

            // Gestion du tri
            $order = "date DESC";
            if (isset($_GET['sort'])) {
                switch ($_GET['sort']) {
                    case 'date_asc':
                        $order = "date ASC";
                        break;
                    case 'alpha':
                        $order = "nom_photo ASC";
                        break;
                    default:
                        $order = "date DESC";
                }
            }

            try {
            $conditions = [];
            $params = [];

            // Type de prise
            if (isset($_GET['prise_auto'])) {
                $conditions[] = "prise = 'auto'";
            }
            if (isset($_GET['prise_manuelle'])) {
                $conditions[] = "prise = 'manuelle'";
            }

            // Plage de dates
            if (!empty($_GET['date_debut'])) {
                $conditions[] = "date >= :date_debut";
                $params[':date_debut'] = $_GET['date_debut'];
            }
            if (!empty($_GET['date_fin'])) {
                $conditions[] = "date <= :date_fin";
                $params[':date_fin'] = $_GET['date_fin'];
            }

            // Nom d'utilisateur
            if (!empty($_GET['nom_utilisateur'])) {
                $conditions[] = "num_ut LIKE :nom_utilisateur";
                $params[':nom_utilisateur'] = "%" . $_GET['nom_utilisateur'] . "%";
            }

            // Matin / Après-midi / Nuit (heure)
                if (!empty($_GET['plage_horaire'])) {
                    switch ($_GET['plage_horaire']) {
                        case 'matin':
                            $conditions[] = "HOUR(date) >= 5 AND HOUR(date) < 11";
                        break;
                        case 'apres_midi':
                            $conditions[] = "HOUR(date) >= 11 AND HOUR(date) < 18";
                        break;
                        case 'nuit':
                            $conditions[] = "(HOUR(date) >= 18 OR HOUR(date) < 5)";
                        break;
                    }
                }

                // Barre de recherche texte (GET)
                if (!empty($_GET['texte_filtre'])) {
                    $conditions[] = "(nom_photo LIKE :texte_filtre OR description_photo LIKE :texte_filtre)";
                    $params[':texte_filtre'] = '%' . $_GET['texte_filtre'] . '%';
                }

                // Construction de la requête SQL
                $sql = "SELECT id_photo, date, prise, nom_photo, description_photo, url_photo FROM photos";
                $conditions[] = "date_supprimee IS NULL";
                if (!empty($conditions)) {
                    $sql .= " WHERE " . implode(" AND ", $conditions);
                }
                $sql .= " ORDER BY $order LIMIT 12";

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($photos)) {
                    echo "<p style='color: white; font-style: italic;'>Aucune photo ne correspond à vos filtres.</p>";
                }

                $favStmt = $conn->prepare("SELECT id_photo FROM favoris WHERE login_ut = ?");
                $favStmt->execute([$_SESSION['login_ut']]);
                $favoris_ids = $favStmt->fetchAll(PDO::FETCH_COLUMN);

                echo '<div class="photo-gallery">';

                foreach ($photos as $row) {
                    $isFavori = in_array($row['id_photo'], $favoris_ids);
                    $icon = $isFavori ? '⭐' : '☆';

                    echo "<div class='photo-card'>";
                    echo "<label>";
                    echo "<input type='checkbox' name='photos[]' value='" . $row['id_photo'] . "'>";
                    echo "<img src='../photos/" . htmlspecialchars($row['url_photo']) . "' alt='photo' onclick='ouvrirZoom(this.src)'>";
                    echo "</label>";
                    echo "<p class='photo-description'>" . htmlspecialchars($row['description_photo']) . "</p>";
                    echo "<button type='button' class='favori-btn' data-id='" . $row['id_photo'] . "'>$icon</button>";
                    echo "</div>";
                }
            echo '</div>'; // Fin .photo-gallery
            echo '</form>';


            } catch (PDOException $e) {
                echo "<p>Erreur SQL : " . $e->getMessage() . "</p>";
            }
            ?>
        <div id="popupFiltre" class="popup-filtre">
            <div class="popup-contenu">
            <span class="fermer-popup" onclick="fermerPopup()">✕</span>
            <h3>🔎 Filtrer les photos</h3>
            <form method="GET" action="">
                <div class="checkbox-group">
                    <label><input type="checkbox" name="prise_auto" value="auto">Prise automatique</label>
                    <label><input type="checkbox" name="prise_manuelle" value="manuelle">Prise manuelle</label>
                </div>

                <label>De :</label>
                <input type="datetime-local" name="date_debut">

                <label>À :</label>
                <input type="datetime-local" name="date_fin">

                <label>Nom d'utilisateur :</label>
                <input type="text" name="nom_utilisateur">

                <label>Période :</label>
                <select name="plage_horaire">
                    <option value="">-- Choisir --</option>
                    <option value="matin">🌅 Matin (05h-11h)</option>
                    <option value="apres_midi">🌞 Après-midi (11h-18h)</option>
                    <option value="nuit">🌙 Nuit (18h-05h)</option>
                </select>

                <button type="submit" name="filtrer" class="btn-valider">Appliquer</button>
                <button type="submit" name="reset" class="btn-valider" formmethod="get">Réinitialiser</button>
            </form>
        </div>
    </div>
    <div id="zoom-overlay" class="zoom-overlay" onclick="fermerZoom()">
        <img id="zoom-img" src="" alt="Zoom">
    </div>

    <script>
    function toggleSort() {
        const menu = document.getElementById("sortOptions");
        if (!menu) return;
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }

    // Fermer si clic en dehors
    document.addEventListener("click", function(event) {
        const dropdown = document.querySelector('.dropdown');
        const menu = document.getElementById("sortOptions");

        if (dropdown && !dropdown.contains(event.target)) {
            menu.style.display = "none";
        }
    });

    function updateClock() {
        const now = new Date();
        const options = {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        };

        const date = now.toLocaleDateString('fr-FR', options);
        const time = now.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        const clock = document.getElementById('clock');
        if (clock) {
            clock.textContent = `🕒 ${date} – ${time}`;
        }
    }

    setInterval(updateClock, 1000);
    updateClock();
    function ouvrirPopup() {
        console.log("Popup déclenchée"); 
        document.getElementById("popupFiltre").style.display = "flex";
    }
    function fermerPopup() {
        document.getElementById("popupFiltre").style.display = "none";
    }

    fetch("get_storage.php")
        .then(response => response.json())
        .then(data => {
            const progress = document.getElementById("progressBar");
            const text = document.getElementById("storageText");

            const used = parseFloat(data.used_gb);
            const total = parseFloat(data.total_gb);
            const percent = ((used / total) * 100).toFixed(1);

            progress.style.width = `${percent}%`;
            text.textContent = `${used} Go utilisé sur ${total} Go (${percent}%)`;
        })
        .catch(() => {
            document.getElementById("storageText").textContent = "Erreur lors du chargement";
        });
    document.querySelectorAll('.favori-btn').forEach(button => {
        button.addEventListener('click', function () {
            const idPhoto = this.dataset.id;
            fetch('toggle_favori.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'id_photo=' + encodeURIComponent(idPhoto)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'added') {
                    this.textContent = '⭐';
                } else if (data.status === 'removed') {
                    this.textContent = '☆';
                }
            });
        });
    });
    function ouvrirZoom(src) {
        const zoomImg = document.getElementById("zoom-img");
        const overlay = document.getElementById("zoom-overlay");
        zoomImg.src = src;
        overlay.style.display = "flex";
    }

    function fermerZoom() {
        document.getElementById("zoom-overlay").style.display = "none";
    }

    function deselectionnerTout() {
        document.querySelectorAll('input[type="checkbox"][name="photos[]"]').forEach(cb => cb.checked = false);
    }
    </script>
</body>
</html>
