<?php
session_start();
if (!isset($_SESSION['login_ut'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}

include('../outils/connex_bd.php');
$conn = createConnection();
$login = $_SESSION['login_ut'];

$isAdmin = isset($_SESSION['statut']) && in_array($_SESSION['statut'], ['administrateur', 'superadministrateur']);
$isSuperAdmin = isset($_SESSION['statut']) && $_SESSION['statut'] === 'superadministrateur';

// Récupération des 12 photos les plus récentes
$stmt = $conn->prepare("SELECT url_photo, nom_photo, date FROM photos WHERE num_ut = :login ORDER BY date DESC LIMIT 12");
$stmt->execute([':login' => $login]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📅 Photos récentes</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
</head>
<body class="dashboard">
<div class="sidebar">
    <div class="top-section">
        <h2><?= htmlspecialchars($login) ?></h2>
        <nav class="sidebar-nav">
            <a href="../Mon compte/mon_compte_user.php">👤 Mon compte</a>
            <a href="../connexion/prendre_photo.php">📷 Prendre une photo</a>
            <a href="../connexion/page_accueil.php">🖼️ Galerie</a>
            <a href="../Favoris/favoris.php">⭐ Favoris</a>
            <a href="../Recents/recents.php" class="active">🕘 Récents</a>
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
        <h1>🕘 Photos récentes</h1>
        <h1 id="clock">-- 🕒</h1>
    </div>

    <div class="photo-gallery">
        <?php if (empty($photos)): ?>
            <p style="color: white; font-style: italic;">Aucune photo récente trouvée.</p>
        <?php else: ?>
            <?php foreach ($photos as $photo): ?>
                <div class="photo-card">
                    <img src="../photos/<?= htmlspecialchars($photo['url_photo']) ?>" alt="<?= htmlspecialchars($photo['nom_photo']) ?>">
                    <p style="color:white; font-size:0.85rem;"><?= date("d/m/Y H:i", strtotime($photo['date'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const date = now.toLocaleDateString('fr-FR', options);
    const time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    document.getElementById('clock').textContent = `🕒 ${date} – ${time}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>
</body>
</html>

