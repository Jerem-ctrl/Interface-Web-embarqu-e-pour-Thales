<?php
// Activation des erreurs PHP temporairement pour debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['login_ut'])) {
    $username = htmlspecialchars($_POST['login_ut']);

    try {
        $bdd = new PDO("mysql:host=localhost;dbname=rp09", "root", "");
        $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $bdd->prepare("SELECT login_ut FROM utilisateur WHERE login_ut = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $mail = new PHPMailer(true);

            try {
                // Configuration SMTP Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'girardjeremy8@gmail.com';
                $mail->Password = 'jaevbuhvmdfqhmaq'; // mot de passe d'application
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
        } else {
            $message = "❌ Utilisateur non reconnu.";
        }

    } catch (PDOException $e) {
        $message = "❌ Erreur DB : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="connexion.css">
</head>
<body>
    <div class="login-container">
        <h2>Mot de passe oublié</h2>
        <form method="POST">
            <input type="text" name="login_ut" placeholder="Nom d'utilisateur" required>
            <div class="button-col">
                <input type="submit" value="Envoyer la demande" />
                <a href="connexion.php" class="photo-button">Retour</a>
            </div>
        </form>
        <?php if (!empty($message)) : ?>
            <p class="error-message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>



