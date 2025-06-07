<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

// Déconnexion après 5 min d'inactivité
if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > 300) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Connexion à la base de données
$bdd = [
    "servername" => "localhost",
    "username" => "root",
    "password" => "root", // ← Ton mot de passe MySQL si besoin
    "dbname" => "rp09",
];

function createConnection($bdd) {
    try {
        $conn = new PDO("mysql:host={$bdd['servername']};dbname={$bdd['dbname']}", $bdd['username'], $bdd['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        error_log("Erreur DB : " . $e->getMessage());
        return null;
    }
}

$conn = createConnection($bdd);
$message = "";

// Initialiser le nombre de tentatives
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!empty($_POST['login_ut']) && !empty($_POST['mdp'])) {
        $username = htmlspecialchars($_POST['login_ut']);
        $password = $_POST['mdp'];

        $check = $conn->prepare("SELECT bloque FROM utilisateur WHERE login_ut = ?");
        $check->execute([$username]);
        $user = $check->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['bloque']) {
            $message = "Ce compte est bloqué. Contactez l'administrateur.";

            $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
            $log->execute([$username, "Tentative de connexion sur compte bloqué"]);
        } else {
            $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE login_ut = ?");
            $stmt->execute([$username]);

            if ($stmt->rowCount() === 1) {
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                // 🔐 Vérifie mot de passe clair OU hashé
                if ($password === $userData['mdp_ut'] || password_verify($password, $userData['mdp_ut'])) {
                    $_SESSION['login_ut'] = $username;
                    $_SESSION['statut'] = $userData['acces'];
                    $_SESSION['login_attempts'] = 0;

                    $update = $conn->prepare("UPDATE utilisateur SET derniere_connexion = NOW() WHERE login_ut = ?");
                    $update->execute([$username]);

                    $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
                    $log->execute([$username, "Connexion réussie"]);

                    header("Location: page_accueil.php");
                    exit();
                } else {
                    // ❌ Mauvais mot de passe
                    $_SESSION['login_attempts']++;
                    $message = "Identifiants invalides. Tentatives : {$_SESSION['login_attempts']} / 3";

                    $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
                    $log->execute([$username, "Tentative de connexion échouée"]);

                    if ($_SESSION['login_attempts'] >= 3) {
                        $conn->prepare("UPDATE utilisateur SET bloque = TRUE WHERE login_ut = ?")->execute([$username]);
                        $message = "Compte bloqué après 3 tentatives. Contactez l’administrateur.";

                        $log = $conn->prepare("INSERT INTO logs (login_ut, description) VALUES (?, ?)");
                        $log->execute([$username, "Compte bloqué après 3 échecs de connexion"]);
                    }
                }
            } else {
                $message = "Utilisateur non trouvé.";
            }
        }
    } else {
        $message = "Champs requis.";
    }
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Connexion – Système Avionique</title>
    <link rel="stylesheet" href="connexion.css" />
</head>
<body>
    <div class="login-container">
        <h2>Connexion - PHOTO_ATB</h2>
        <form method="POST" action="">
            <input type="text" name="login_ut" placeholder="Nom d'utilisateur" required />
            <input type="password" name="mdp" placeholder="Mot de passe" required />

            <div class="forgot-wrapper">
                <a href="mot_de_passe_oublie.php" class="forgot">Mot de passe oublié ?</a>
                <a href="mailto:girardjeremy8@gmail.com" class="forgot">Contacter l'administrateur</a>
            </div>

            <div class="button-col">
                <input type="submit" name="envoi" value="Se connecter" id="connectBtn" />
                <a href="prendre_photo.php" class="photo-button" style="text-decoration: none;"><span>📷 Prendre une Photo</span></a>
            </div>
        </form>

        <?php if (!empty($message)) : ?>
            <p class="error-message"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

