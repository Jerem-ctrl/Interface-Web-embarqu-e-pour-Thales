<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

$saveDir = "/var/www/html/photos";
$latestPhoto = null;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['take_photo'])) {
    $timestamp = date("Y-m-d_H-i-s");
    $filename = "photo_{$timestamp}.jpg";
    $filepath = "$saveDir/$filename";

    $cmd = "fswebcam -r 1280x720 --no-banner $filepath 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($filepath)) {
        $latestPhoto = $filename;
        $message = "✅ Photo capturée avec succès.";
    } else {
        $message = "❌ Erreur de capture : $output";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>📷 Caméra en direct - Mode Invité</title>
    <link rel="stylesheet" href="connexion.css">
    <style>
        .video-preview {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
        }
        video {
            width: 100%;
            max-width: 100%;
            border-radius: 10px;
        }
        .photo-preview img {
            max-width: 100%;
            margin-top: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>📷 Caméra en direct</h2>

        <form method="POST">
            <div class="button-col">
                <input type="submit" name="take_photo" value="📸 Prendre une photo" class="photo-button">
                <?php
                    $retour = isset($_SESSION['login_ut']) ? 'page_accueil.php' : 'connexion.php';
                ?>
                <a href="<?= $retour ?>" class="photo-button">Retour</a>
            </div>
        </form>

        <?php if (!empty($message)) : ?>
            <p class="error-message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($latestPhoto) : ?>
            <div class="photo-preview">
                <img src="/photos/<?= htmlspecialchars($latestPhoto); ?>" alt="Dernière photo">
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


