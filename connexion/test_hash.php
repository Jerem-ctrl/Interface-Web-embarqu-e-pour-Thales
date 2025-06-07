<?php
$motDePasse = 'super123';  // Ce que tu tapes
$hashEnBase = '$2y$10$eLR8Bvnn9yP3TxsFOebFEeGQmYHXRJ.N57CHeB9v2I.nVNVHqnhje'; // Ce qu’il y a en BDD

if (password_verify($motDePasse, $hashEnBase)) {
    echo "✅ Mot de passe VALIDE";
} else {
    echo "❌ Mot de passe INVALIDE";
}
echo password_hash('admin', PASSWORD_DEFAULT);
?>
