<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bts_project;charset=utf8", "root", "");
    echo "Connexion BDD OK !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>