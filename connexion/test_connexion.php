<?php
try {
    $conn = new PDO("mysql:host=localhost;dbname=rp09", "root", "root");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion réussie à la base de données.";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
}
