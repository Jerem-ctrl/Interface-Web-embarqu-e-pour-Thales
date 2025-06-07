<?php
session_start();
if (!isset($_SESSION['login_ut'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}

$isAdmin = isset($_SESSION['statut']) && in_array($_SESSION['statut'], ['administrateur', 'superadministrateur']);
$isSuperAdmin = isset($_SESSION['statut']) && $_SESSION['statut'] === 'superadministrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>⭐ Favoris - <?= htmlspecialchars($_SESSION['login_ut']) ?></title>
    <link rel="stylesheet" href="../connexion/connexion.css">
</head>
<body class="dashboard">
    <div class="sidebar">
        <div class="top-section">
            <h2><?= htmlspecialchars($_SESSION['login_ut']) ?></h2>
            <nav class="sidebar-nav">
                <a href="../Mon compte/mon_compte_user.php">👤 Mon compte</a>
                <a href="../connexion/prendre_photo.php">📷 Prendre une photo</a>
                <a href="../connexion/page_accueil.php">🖼️ Galerie</a>
                <a href="favoris.php" class="active">⭐ Favoris</a>
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

        <form method="POST" action="../connexion/deconnexion.php" class="logout-section">
            <button type="submit" class="logout">⚪ Déconnexion</button>
        </form>
    </div>

    <div class="main">
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>⭐ Mes Favoris</h1>
            </div>
            <div class="top-bar-right">
                <h1 id="clock">-- 🕒</h1>
            </div>
        </div>

        <div class="photo-gallery">
        <?php
        include('../outils/connex_bd.php');
        $conn = createConnection();
        $login = $_SESSION['login_ut'];

        try {
            $sql = "SELECT p.id_photo, p.url_photo
                    FROM favoris f
                    JOIN photos p ON f.id_photo = p.id_photo
                    WHERE f.login_ut = ?
                    ORDER BY p.date DESC";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$login]);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($photos)) {
                echo "<p style='color: white; font-style: italic;'>Aucune photo n’a été ajoutée aux favoris.</p>";
            }

            foreach ($photos as $row) {
                echo "<div class='photo-card'>";
                echo "<img src='../photos/" . htmlspecialchars($row['url_photo']) . "' alt='photo'>";
                echo "</div>";
            }
        } catch (PDOException $e) {
            echo "<p>Erreur SQL : " . $e->getMessage() . "</p>";
        }
        ?>
        </div>
    </div>

<script>
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
</script>
</body>
</html>

