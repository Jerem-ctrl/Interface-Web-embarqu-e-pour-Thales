<?php
session_start();
if (!isset($_SESSION['login_ut'])) {
    header("Location: ../connexion.php");
    exit();
}
require_once("connex_bd.php");
$conn = createConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['del']) && !empty($_POST['selected_photos'])) {
        $placeholders = implode(',', array_fill(0, count($_POST['selected_photos']), '?'));
        $sql = "UPDATE photos SET date_supprimee = NOW() WHERE id_photo IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($_POST['selected_photos']);
        header("Location: ../page_accueil.php");
        exit();
    }
}
?>
