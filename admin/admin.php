<?php
session_start();
include('../outils/connex_bd.php');

$conn = createConnection();

// Supposons que l'utilisateur connecté a son ID en session
$id_utilisateur = $_SESSION['num_ut']; // par exemple

// Requête pour récupérer les infos
$sql = "SELECT id, nom, prenom, statut FROM utilisateurs WHERE id = ?";
$stmt = $conn->prepare($sql);



if (isset($_POST['valider'])) {
    $email = $_POST['email'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $centre_gestion = $_POST['centre_gestion'];
    $password = $_POST['password'];

    if (!empty($password)) {
        // Si un nouveau mot de passe est saisi
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql_update = "UPDATE utilisateurs 
                       SET email = ?, nom = ?, prenom = ?, centre_gestion = ?, mot_de_passe = ?
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindValue(1, $email, PDO::PARAM_STR);
        $stmt_update->bindValue(2, $nom, PDO::PARAM_STR);
        $stmt_update->bindValue(3, $prenom, PDO::PARAM_STR);
        $stmt_update->bindValue(4, $centre_gestion, PDO::PARAM_STR);
        $stmt_update->bindValue(5, $password_hash, PDO::PARAM_STR);
        $stmt_update->bindValue(6, $id_utilisateur, PDO::PARAM_INT);
    } else {
        // Si aucun nouveau mot de passe
        $sql_update = "UPDATE utilisateurs 
                       SET email = ?, nom = ?, prenom = ?, centre_gestion = ?
                       WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindValue(1, $email, PDO::PARAM_STR);
        $stmt_update->bindValue(2, $nom, PDO::PARAM_STR);
        $stmt_update->bindValue(3, $prenom, PDO::PARAM_STR);
        $stmt_update->bindValue(4, $centre_gestion, PDO::PARAM_STR);
        $stmt_update->bindValue(5, $id_utilisateur, PDO::PARAM_INT);
    }

    if ($stmt_update->execute()) {
        echo "<script>alert('Modifications enregistrées avec succès !'); window.location.href='admin.php';</script>";
    } else {
        echo "<script>alert('Erreur lors de la mise à jour.');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header class="top-bar">
        <div class="retour-box">
            <button class="btn-retour" onclick="history.back()">↩️ Retour</button>
        </div>
        <div class="account-box">
            <button class="btn-filtre" onclick="window.location.href='../admin/admin.php'">👤 Mon compte</button>
        </div>
    </header>

    <main class="admin-box">
    <form method="POST" action="admin.php" class="admin-form">
    <input type="text" name="id" value="<?= htmlspecialchars($user['id']) ?>" readonly>

    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Adresse e-mail (facultatif)">

    <div class="name-fields">
        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" placeholder="Nom (facultatif)">
        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" placeholder="Prénom (facultatif)">
    </div>

    <div class="password-fields">
        <input type="password" name="password" placeholder="Nouveau mot de passe (laisser vide si inchangé)">
        <button type="button" class="btn-centre" onclick="window.location.href='centre_gestion.php'">Centre de gestion</button>
    </div>

    <div class="button-actions">
        <button type="submit" name="valider" class="btn-validate">✅ Valider les modifications</button>
        <button type="submit" name="supprimer" class="btn-delete">❌ Suppression de compte</button>
    </div>
</form>

        <div class="statut-box">
        <p>Statut :</p>
             <p class="statut-value"><?= htmlspecialchars($user['statut']) ?></p>
        </div>
    </main>


</body>
</html>