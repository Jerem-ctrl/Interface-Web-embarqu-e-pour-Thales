<?php
session_start();
if (!isset($_SESSION['login_ut']) || $_SESSION['statut'] !== 'superadministrateur') {
    header("Location: ../connexion/connexion.php");
    exit();
}

include('../outils/connex_bd.php');
$conn = createConnection();

$baseDir = realpath(__DIR__ . '/../..') . '/site_web';

if (isset($_GET['download'])) {
    $downloadPath = str_replace(['..', '../', '\\'], '', $_GET['download']);
    $fileToDownload = realpath($baseDir . DIRECTORY_SEPARATOR . $downloadPath);
    if ($fileToDownload && strpos($fileToDownload, $baseDir) === 0 && is_file($fileToDownload)) {
        if (file_exists($fileToDownload)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileToDownload) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileToDownload));
            ob_end_clean(); // Nettoyage du buffer pour éviter les conflits
            flush();
            readfile($fileToDownload);
            exit();
        }
    }
}

function afficherContenu($path, $base) {
    $items = scandir($path);
    echo "<ul>";
    foreach ($items as $item) {
        if (in_array($item, ['.', '..'])) continue;
        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        $relative = ltrim(str_replace($base, '', $fullPath), DIRECTORY_SEPARATOR);
        echo "<li>";
        echo is_dir($fullPath) ? "📁 " : "📄 ";
        if (is_dir($fullPath)) {
            echo htmlspecialchars($item);
            afficherContenu($fullPath, $base);
        } else {
            echo htmlspecialchars($item);
            echo ' <a class="download" href="?download=' . urlencode($relative) . '">⬇️ Télécharger</a>';
        }
        echo "</li>";
    }
    echo "</ul>";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🛠️ Fichiers système</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
    <style>
        body.dashboard {
            overflow-y: auto;
        }
        .main {
            padding: 20px;
            color: white;
            height: calc(100vh - 40px);
            overflow-y: auto;
        }
        ul {
            list-style-type: none;
            padding-left: 20px;
        }
        li {
            margin-bottom: 6px;
        }
        a.download {
            margin-left: 10px;
            color: lightgreen;
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
            <a href="../admin/archivage.php">📂 Archivage</a>
            <a href="../admin/gestion_utilisateurs.php">👥 Utilisateurs</a>
            <a href="../admin/logs.php">📜 Logs</a>
            <a href="fichiers_systeme.php" class="active">🛠️ Fichiers système</a>
        </nav>
    </div>
    <form method="POST" action="../connexion/deconnexion.php" class="logout-section">
        <button type="submit" class="logout">⚪ Déconnexion</button>
    </form>
</div>
<div class="main">
    <h1>🛠️ Contenu complet de <code>/site_web</code></h1>
    <?php afficherContenu($baseDir, $baseDir); ?>
</div>
</body>
</html>