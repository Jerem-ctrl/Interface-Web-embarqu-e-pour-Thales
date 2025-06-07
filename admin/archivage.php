<?php
session_start();

$isAdmin = isset($_SESSION['statut']) && in_array($_SESSION['statut'], ['administrateur', 'superadministrateur']);

if (!isset($_SESSION['login_ut']) || !in_array($_SESSION['statut'], ['administrateur', 'superadministrateur'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}


include('../outils/connex_bd.php');
$conn = createConnection();

// Traitement des actions (restaurer / supprimer définitivement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['photos'])) {
    $ids = $_POST['photos'];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    if (isset($_POST['restaurer'])) {
        $sql = "UPDATE photos SET date_supprimee = NULL WHERE id_photo IN ($placeholders)";
        $msg = "✅ Photos restaurées.";
    } elseif (isset($_POST['supprimer_def'])) {
        $sql = "DELETE FROM photos WHERE id_photo IN ($placeholders)";
        $msg = "❌ Photos supprimées définitivement.";
    }

    if (isset($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->execute($ids);
    }
}

if ($isAdmin) {

    $sql = "SELECT id_photo, url_photo, description_photo, date_supprimee, supprime_par, num_ut AS login_ut
            FROM photos 
            WHERE date_supprimee IS NOT NULL 
            AND date_supprimee > NOW() - INTERVAL 30 DAY 
            ORDER BY date_supprimee DESC";
    $stmt = $conn->query($sql);
} else {
    $sql = "SELECT id_photo, url_photo, description_photo, date_supprimee 
            FROM photos 
            WHERE num_ut = ? 
            AND date_supprimee IS NOT NULL 
            AND date_supprimee > NOW() - INTERVAL 30 DAY 
            ORDER BY date_supprimee DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$login]);
}
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📂 Archivage - Admin</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
    <style>
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .photo-card {
            background-color: #1e2a38;
            padding: 10px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }
        .photo-card img {
            width: 100%;
            border-radius: 8px;
        }
        .photo-description, .photo-info {
            color: #ccc;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .actions button {
            margin-right: 10px;
        }
    </style>
</head>
<body class="dashboard">
    <div class="sidebar">
        <div class="top-section">
            <h2><?= htmlspecialchars($_SESSION['login_ut']) ?></h2>
            <nav class="sidebar-nav">
                <a href="../Mon compte/mon_compte_user.php">👤 Mon compte</a>
                <a href="../connexion/prendre_photo.php">📷 Prendre une photo</a>
                <a href="../connexion/page_accueil.php">🖼️ Galerie</a>
                <a href="../Favoris/favoris.php">⭐ Favoris</a>
                <a href="../Recents/recents.php">🕘 Récents</a>
                <a href="../Corbeille/corbeille.php">🗑️ Corbeille</a>

                <hr>
                <a href="archivage.php" class="active">📂 Archivage</a>
                <a href="gestion_utilisateurs.php">👥 Utilisateurs</a>
                <a href="logs.php">📝 Logs</a>
            </nav>
        </div>
        <form method="POST" action="../connexion/connexion.php" class="logout-section">
            <button type="submit" class="logout">⚪ Déconnexion</button>
        </form>
    </div>

    <div class="main">
        <h1>📂 Photos archivées</h1>
        <?php if (!empty($msg)) echo "<p style='color:lime;'>$msg</p>"; ?>

        <?php if (empty($photos)) : ?>
            <p style="color: white;">Aucune photo supprimée à afficher.</p>
        <?php else : ?>
        <form method="POST" action="">
            <div class="actions" style="margin-bottom: 15px;">
                <button type="submit" name="restaurer">♻️ Restaurer</button>
                <button type="submit" name="supprimer_def">❌ Supprimer définitivement</button>
                <button type="button" onclick="deselectAll()">🧹 Désélectionner tout</button>
            </div>
            <div class="photo-gallery">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-card">
                        <label>
                            <input type="checkbox" name="photos[]" value="<?= $photo['id_photo'] ?>">
                            <img src="../photos/<?= htmlspecialchars($photo['url_photo']) ?>" alt="photo">
                        </label>
                        <div class="photo-description">📝 <?= htmlspecialchars($photo['description_photo']) ?></div>
                        <div class="photo-info">👤 Supprimée par : <?= htmlspecialchars($photo['supprime_par']) ?></div>
                        <div class="photo-info">🗑️ Le : <?= date("d/m/Y H:i", strtotime($photo['date_supprimee'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
    function deselectAll() {
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    }
    </script>
</body>
</html>
