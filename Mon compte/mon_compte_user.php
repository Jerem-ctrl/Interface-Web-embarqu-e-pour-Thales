<?php
session_start();
if (!isset($_SESSION['login_ut'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}

require '../connexion/PHPMailer/src/PHPMailer.php';
require '../connexion/PHPMailer/src/SMTP.php';
require '../connexion/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include('../outils/connex_bd.php');
$conn = createConnection();
$login = $_SESSION['login_ut'];

// Récupération des infos personnelles
$stmt = $conn->prepare("SELECT acces AS statut, date_creation, derniere_connexion FROM utilisateur WHERE login_ut = :login");
$stmt->execute([':login' => $login]);
$infos = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupération des stats de photos
$nbPhotos = $conn->prepare("SELECT COUNT(*) FROM photos WHERE num_ut = :login AND prise = 'manuelle'");
$nbPhotos->execute([':login' => $login]);
$totalPhotos = $nbPhotos->fetchColumn();

$lastPhoto = $conn->prepare("SELECT MAX(date) FROM photos WHERE num_ut = :login AND prise = 'manuelle'");
$lastPhoto->execute([':login' => $login]);
$dateLastPhoto = $lastPhoto->fetchColumn();


// Historique
$logs = $conn->prepare("SELECT description, date_heure FROM logs WHERE login_ut = :login ORDER BY date_heure DESC LIMIT 5");
$logs->execute([':login' => $login]);
$actions = $logs->fetchAll(PDO::FETCH_ASSOC);

// Gestion des actions depuis la page
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['demander_mdp'])) {
        $username = htmlspecialchars($login); // récupéré de la session

        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'girardjeremy8@gmail.com';
            $mail->Password = 'jaevbuhvmdfqhmaq'; // mot de passe d'application Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Email content
            $mail->setFrom('jeremygirard23@gmail.com', 'RaspberryPI');
            $mail->addAddress('girardjeremy8@gmail.com', 'Admin');
            $mail->isHTML(true);
            $mail->Subject = '🔐 Demande de changement de mot de passe';
            $mail->Body = "L'utilisateur <strong>$username</strong> a demandé un changement de mot de passe.<br>Merci de vérifier dans phpMyAdmin.";

            $mail->send();
            $message = "✉️ Une demande a été envoyée à l’administrateur.";
        } catch (Exception $e) {
            $message = "❌ Erreur SMTP : " . $mail->ErrorInfo;
        }

    } elseif (isset($_POST['supprimer_compte'])) {
        $username = htmlspecialchars($login);

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'girardjeremy8@gmail.com';
            $mail->Password = 'jaevbuhvmdfqhmaq'; // mot de passe d'application
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('jeremygirard23@gmail.com', 'RaspberryPI');
            $mail->addAddress('girardjeremy8@gmail.com', 'Admin');
            $mail->isHTML(true);
            $mail->Subject = '🚮 Demande de suppression de compte';
            $mail->Body = "L'utilisateur <strong>$username</strong> a demandé la <span style='color:red;'>suppression de son compte</span>.";

            $mail->send();

            $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
            $log->execute([$login, "Demande de suppression de compte envoyée"]);

            $message = "🚮 Une demande de suppression a été envoyée à l’administrateur.";
        } catch (Exception $e) {
            $message = "❌ Erreur lors de l'envoi de l'email : " . $mail->ErrorInfo;
        }

    } elseif (isset($_POST['contacter_admin'])) {
        $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
        $log->execute([$login, "Ouverture de la messagerie pour contacter l'administrateur"]);

        if (empty($_POST['ajax'])) {
            $message = "Message à l'administrateur transmis.";
        }

        if (!empty($_POST['ajax'])) exit;
    }
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Compte</title>
    <link rel="stylesheet" href="mon_compte.css">
</head>
<body>
<div class="mon-compte-wrapper">
    <a href="../connexion/page_accueil.php" class="photo-button btn-retour">⬅ Retour</a>
    <h1>👤 Mon Compte</h1>

    <?php if ($message): ?>
        <p class="error-message">✅ <?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="section">
        <h2>💼 Informations personnelles</h2>
        <div class="info-item"><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($login) ?></div>
        <div class="info-item"><strong>Statut :</strong> <?= htmlspecialchars($infos['statut'] ?? 'Inconnu') ?></div>
        <div class="info-item"><strong>Compte créé le :</strong> <?= date('d/m/Y H:i', strtotime($infos['date_creation'] ?? '1970-01-01')) ?></div>
        <div class="info-item"><strong>Derniere connexion :</strong> <?= date('d/m/Y H:i', strtotime($infos['derniere_connexion'] ?? '1970-01-01')) ?></div>
    </div>

    <div class="section">
        <h2>📸 Activité</h2>
        <div class="info-item"><strong>Photos prises :</strong> <?= $totalPhotos ?></div>
        <div class="info-item"><strong>Derniere photo :</strong> <?= $dateLastPhoto ? date("d/m/Y H:i", strtotime($dateLastPhoto)) : 'Aucune' ?></div>
        <div class="info-item">
            <strong>📝 Derniers événements :</strong>
            <ul>
                <?php foreach ($actions as $act): ?>
                    <li><?= date("d/m/Y H:i", strtotime($act['date_heure'])) ?> – <?= htmlspecialchars($act['description']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <form method="POST" class="action-form">
        <!-- 🔐 Changement de mot de passe -->
        <div class="form-group">
            <label for="demande-mdp"><strong>🔐 Demande de changement de mot de passe :</strong></label>
            <input type="hidden" name="login_ut" value="<?= htmlspecialchars($login) ?>">
            <button id="demande-mdp" type="submit" name="demander_mdp" class="button">Envoyer la demande</button>
        </div>

        
        <!-- 📧 Contacter l'administrateur -->
        <div class="form-group">
            <label for="contact-admin-btn"><strong>📧 Contacter l’administrateur :</strong></label>
        <button id="contact-admin-btn" type="button" class="button" onclick="ouvrirMessagerie()">Ouvrir la messagerie</button>
        </div>


        <!-- 🧨 Suppression du compte -->
        <div class="form-group">
            <label for="supprimer-compte"><strong>🚮​ Supprimer mon compte :</strong></label>
            <button id="supprimer-compte" type="submit" name="supprimer_compte" class="button btn-delete">Confirmer la suppression</button>
        </div>
    </form>

</div>

<script>
function ouvrirMessagerie() {
    // Envoie un appel AJAX silencieux pour loguer l'action
    fetch("mon_compte_user.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "contacter_admin=true&ajax=true"
    });

    // Ensuite, ouvre la messagerie
    const login = "<?= htmlspecialchars($login) ?>";
    const subject = encodeURIComponent("Contact depuis le compte " + login);
    const body = encodeURIComponent("Bonjour, je souhaite contacter l'administrateur concernant mon compte.");
    const mailtoLink = `mailto:girardjeremy8@gmail.com?subject=${subject}&body=${body}`;
    window.location.href = mailtoLink;
}
</script>


</body>
</html>
