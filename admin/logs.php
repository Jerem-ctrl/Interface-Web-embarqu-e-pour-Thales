<?php
session_start();
if (!isset($_SESSION['login_ut']) || !in_array($_SESSION['statut'], ['administrateur', 'superadministrateur'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}

include('../outils/connex_bd.php');
$conn = createConnection();

// Vider les logs si demandé
if (isset($_POST['vider_logs'])) {
    $conn->query("DELETE FROM logs");
}

// Requête de base
$sql = "SELECT id_log, login_ut, description, date_heure FROM logs WHERE 1";
$params = [];

// Filtre utilisateur
if (!empty($_GET['utilisateur'])) {
    $sql .= " AND login_ut LIKE ?";
    $params[] = "%" . $_GET['utilisateur'] . "%";
}

// Filtre date
if (!empty($_GET['date'])) {
    $sql .= " AND DATE(date_heure) = ?";
    $params[] = $_GET['date'];
}

$sql .= " ORDER BY date_heure DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour afficher la gravité
function afficherGravite($description) {
    if (stripos($description, 'connexion réussie') !== false) {
        return "<span style='color: white;'>ℹ️ Info</span>";
    }
    return "<span style='color: gray;'>❔ Inconnue</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📝 Logs</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
    <style>
        .main { padding: 20px; overflow-y: auto; max-height: 100vh; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; color: white; }
        th, td { border: 1px solid gray; padding: 10px; text-align: left; }
        .filters { margin-top: 20px; background: #1f1f2e; padding: 10px; }
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
                <a href="archivage.php">📂 Archivage</a>
                <a href="gestion_utilisateurs.php">👥 Utilisateurs</a>
                <a href="logs.php" class="active">📝 Logs</a>
                <?php if ($_SESSION['statut'] === 'superadministrateur') : ?>
                    <a href="../superadmin/fichiers_systeme.php">🛠️ Fichiers système</a>
                <?php endif; ?>
            </nav>
        </div>
        <form method="POST" action="../connexion/deconnexion.php" class="logout-section">
            <button type="submit" class="logout">⚪ Déconnexion</button>
        </form>
    </div>

    <div class="main">
        <h1>📝 Historique des logs</h1>

        <form method="GET" class="filters">
            <input type="text" name="utilisateur" placeholder="🔍 Utilisateur" value="<?= htmlspecialchars($_GET['utilisateur'] ?? '') ?>">
            <input type="date" name="date" value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
            <button type="submit">🔎 Filtrer</button>
        </form>

        <form method="POST" style="margin-top:10px">
            <button type="submit" name="vider_logs" onclick="return confirm('Vider tous les logs ?')">🔄 Vider les logs</button>
        </form>

        <table>
            <tr>
                <th># Log</th>
                <th>Utilisateur</th>
                <th>Gravité</th>
                <th>Action</th>
                <th>Date</th>
            </tr>
            <?php foreach ($logs as $index => $log): ?>
                <tr>
                    <td>#<?= str_pad($index + 1, 3, "0", STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($log['login_ut']) ?></td>
                    <td><?= afficherGravite($log['description']) ?></td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                    <td><?= $log['date_heure'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5">Aucun log trouvé.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>