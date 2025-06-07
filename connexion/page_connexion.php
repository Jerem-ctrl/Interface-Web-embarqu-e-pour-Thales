<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$bdd = [
    "servername" => "localhost",
    "username" => "root",
    "password" => "root",
    "dbname" => "rp09",
];

// Function to create database connection
function createConnection($bdd) {
    try {
        // Get database connection parameters
        $servername = $bdd["servername"];
        $username = $bdd["username"];
        $password = $bdd["password"];
        $dbname = $bdd["dbname"];

        // Create PDO connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // Set error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        // Handle connection errors
        echo "Connection failed: " . $e->getMessage();
    }  

    // Return the connection object
    return isset($conn) ? $conn : null;
}

$conn = createConnection($bdd);

if (isset($_POST['envoi'])) {
    if (!empty($_POST['login_ut']) && !empty($_POST['mdp'])) {
        $username = htmlspecialchars($_POST['login_ut']);
        $password = $_POST['mdp'];
        $req = $conn->prepare("SELECT * FROM utilisateur WHERE login_ut = ? AND mdp_ut = ?");
        $req->execute(array($username, $password));
        $userExist = $req->rowCount();
        if ($userExist == 1) {
            session_start();
            $_SESSION['login_ut'] = $username;
            header("Location: page_accueil.php");
            exit();
        } else {
            $message = "Nom d'utilisateur ou mot de passe incorrect";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/page_connexion.css">
    <title>Page de Connexion</title>
</head>
<body>

    <div class="login-container">
        <h2>Bienvenue ! </h2>
        <form action="" method="POST">
            <!-- Champ pour le nom d'utilisateur -->
            <span class="icon">👤</span>
            <input type="text" id="login_ut" name="login_ut" required autocomplete="off"><br><br>
            
            <!-- Champ pour le mot de passe -->
            <span class="icon">🔒</span>
            <input type="password" id="mdp" name="mdp" required autocomplete="off"><br><br>
            
            <!-- Bouton de soumission du formulaire -->
            <input type="submit" value="Se connecter" name="envoi">
            <button type="button-group" onclick="prendrePhoto()">Prendre une Photo</button>
        </form>
    </div>
</body>
</html>
