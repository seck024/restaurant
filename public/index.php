<?php
$pdo = new PDO("mysql:host=localhost;dbname=bts_project", "root", "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["form_type"])) {

        //  Traitement du formulaire de candidature
        if ($_POST["form_type"] == "contact") {
            $nom = htmlspecialchars($_POST["name"]);
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

            //  Gestion du CV
            $uploadDir = "uploads/";
            $cvFileName = basename($_FILES["cv"]["name"]);
            $uploadFile = $uploadDir . $cvFileName;

            if (move_uploaded_file($_FILES["cv"]["tmp_name"], $uploadFile)) {
                // Enregistrer la candidature en base de données
                $stmt = $pdo->prepare("INSERT INTO candidatures (nom, email, cv_path) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $email, $uploadFile]);
                echo "<p style='color: green;'>Candidature envoyée avec succès !</p>";
            } else {
                echo "<p style='color: red;'>Erreur lors du téléchargement du CV.</p>";
            }
        }

        //  Traitement du formulaire de réservation
        elseif ($_POST["form_type"] == "reservation") {
            $nom = htmlspecialchars($_POST["name"]);
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
            $date = $_POST["date"];
            $time = $_POST["time"];

            // Enregistrer la réservation en base de données
            $stmt = $pdo->prepare("INSERT INTO reservations (nom, email, date, time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $email, $date, $time]);

            echo "<p style='color: green;'>Réservation confirmée !</p>";
        }
    }
}

