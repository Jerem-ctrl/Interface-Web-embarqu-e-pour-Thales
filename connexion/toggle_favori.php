<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['login_ut']) || !isset($_POST['id_photo'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$login = $_SESSION['login_ut'];
$id_photo = (int) $_POST['id_photo'];

require '../outils/connex_bd.php';
$conn = createConnection();

// Vérifie si déjà favori
$check = $conn->prepare("SELECT * FROM favoris WHERE login_ut = ? AND id_photo = ?");
$check->execute([$login, $id_photo]);

if ($check->rowCount()) {
    $delete = $conn->prepare("DELETE FROM favoris WHERE login_ut = ? AND id_photo = ?");
    $delete->execute([$login, $id_photo]);
    echo json_encode(['status' => 'removed']);
} else {
    $insert = $conn->prepare("INSERT INTO favoris (login_ut, id_photo) VALUES (?, ?)");
    $insert->execute([$login, $id_photo]);
    echo json_encode(['status' => 'added']);
}
?>