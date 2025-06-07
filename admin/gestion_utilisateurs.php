<?php
session_start();
if (!isset($_SESSION['login_ut']) || !in_array($_SESSION['statut'], ['administrateur', 'superadministrateur'])) {
    header("Location: ../connexion/connexion.php");
    exit();
}

include('../outils/connex_bd.php');
$conn = createConnection();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $login = $_POST['login'] ?? null;
        switch ($_POST['action']) {
            case 'delete':
                if ($login) {
                    if ($_SESSION['statut'] === 'superadministrateur' && $_SESSION['login_ut'] === $login) {
                        $stmt = $conn->prepare("DELETE FROM utilisateur WHERE login_ut = ?");
                        $stmt->execute([$login]);
                        session_destroy();
                        header("Location: ../connexion/connexion.php");
                        exit();
                    } else {
                        $stmt = $conn->prepare("DELETE FROM utilisateur WHERE login_ut = ? AND acces != 'superadministrateur'");
                        $stmt->execute([$login]);
                    }
                }
                break;
            case 'block':
                if ($login) {
                    $stmt = $conn->prepare("UPDATE utilisateur SET bloque = 1 WHERE login_ut = ?");
                    $stmt->execute([$login]);
                }
                break;
            case 'unblock':
                if ($login) {
                    $stmt = $conn->prepare("UPDATE utilisateur SET bloque = 0 WHERE login_ut = ?");
                    $stmt->execute([$login]);
                }
                break;
            case 'resetpw':
                if ($login && !empty($_POST['newpw'])) {
                    $newHash = password_hash($_POST['newpw'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE utilisateur SET mdp_ut = ? WHERE login_ut = ?");
                    $stmt->execute([$newHash, $login]);
                }
                break;
            case 'changestatus':
                if ($login && !empty($_POST['newstatus'])) {
                    $stmt = $conn->prepare("UPDATE utilisateur SET acces = ? WHERE login_ut = ?");
                    $stmt->execute([$_POST['newstatus'], $login]);
                }
                break;
            case 'create':
                if (!empty($_POST['new_login']) && !empty($_POST['new_password']) && !empty($_POST['new_statut'])) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO utilisateur (login_ut, mdp_ut, acces, bloque) VALUES (?, ?, ?, 0)");
                        $stmt->execute([
                            $_POST['new_login'],
                            password_hash($_POST['new_password'], PASSWORD_DEFAULT),
                            $_POST['new_statut']
                        ]);
                        header("Location: gestion_utilisateurs.php");
                        exit();
                    } catch (PDOException $e) {
                        echo "<p style='color:red;'>Erreur lors de la création : " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                break;
        }
    }
}

// Récupération des utilisateurs (inclut aussi le superadmin lui-même)
$stmt = $conn->prepare("SELECT login_ut, acces, bloque, derniere_connexion FROM utilisateur ORDER BY login_ut");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>👥 Gestion des utilisateurs</title>
    <link rel="stylesheet" href="../connexion/connexion.css">
    <style>
        .main {
            max-height: 100vh;
            overflow-y: auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            color: white;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid gray;
            padding: 10px;
            text-align: center;
        }
        .actions form {
            display: inline;
        }
        .actions input[type="text"], select {
            width: 100px;
        }
        .create-user-form {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #555;
            background: #1f1f2e;
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
                <a href="archivage.php">📂 Archivage</a>
                <a href="gestion_utilisateurs.php" class="active">👥 Utilisateurs</a>
                <a href="logs.php">📝 Logs</a>
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
        <div class="top-bar">
            <div class="top-bar-left">
                <h1>👥 Gestion des utilisateurs</h1>
            </div>
            <div class="top-bar-right">
                <h1 id="clock">-- 🕒</h1>
            </div>
        </div>

        <table>
            <tr>
                <th>Nom</th>
                <th>Statut</th>
                <th>État</th>
                <th>Dernière connexion</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['login_ut']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="login" value="<?= $user['login_ut'] ?>">
                            <select name="newstatus">
                                <option value="utilisateur" <?= $user['acces'] == 'utilisateur' ? 'selected' : '' ?>>utilisateur</option>
                                <option value="administrateur" <?= $user['acces'] == 'administrateur' ? 'selected' : '' ?>>administrateur</option>
                                <?php if ($_SESSION['statut'] === 'superadministrateur'): ?>
                                    <option value="superadministrateur" <?= $user['acces'] == 'superadministrateur' ? 'selected' : '' ?>>superadministrateur</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" name="action" value="changestatus">✏️</button>
                        </form>
                    </td>
                    <td><?= $user['bloque'] ? '🔒 Bloqué' : '✅ Actif' ?></td>
                    <td><?= $user['derniere_connexion'] ?? 'Jamais' ?></td>
                    <td class="actions">
                        <form method="POST">
                            <input type="hidden" name="login" value="<?= $user['login_ut'] ?>">
                            <input type="text" name="newpw" placeholder="Nouveau mdp">
                            <button type="submit" name="action" value="resetpw">🔄</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="login" value="<?= $user['login_ut'] ?>">
                            <button type="submit" name="action" value="<?= $user['bloque'] ? 'unblock' : 'block' ?>">
                                <?= $user['bloque'] ? '✅ Débloquer' : '⛔ Bloquer' ?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Supprimer ce compte ?');">
                            <input type="hidden" name="login" value="<?= $user['login_ut'] ?>">
                            <button type="submit" name="action" value="delete">🗑️ Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="create-user-form">
            <h2>➕ Créer un nouvel utilisateur</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="text" name="new_login" placeholder="Login" required>
                <input type="text" name="new_password" placeholder="Mot de passe" required>
                <select name="new_statut" required>
                    <option value="utilisateur">Utilisateur</option>
                    <option value="administrateur">Administrateur</option>
                </select>
                <button type="submit" name="action" value="create">➕ Ajouter</button>
            </form>
        </div>
    </div>

    <script>
    function updateClock() {
        const now = new Date();
        const date = now.toLocaleDateString('fr-FR', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        const time = now.toLocaleTimeString('fr-FR', {
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
        document.getElementById("clock").textContent = `🕒 ${date} – ${time}`;
    }
    setInterval(updateClock, 1000);
    updateClock();
    </script>
</body>
</html>
