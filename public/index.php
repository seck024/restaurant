<?php
$pdo = new PDO("mysql:host=localhost;dbname=bts_project", "root", "");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["form_type"])) {

<<<<<<< HEAD
        // Traitement du formulaire de candidature
=======
        //  Traitement du formulaire de candidature
>>>>>>> 39f50a591dd6e3afd8d581ed3ceed1ee5971872c
        if ($_POST["form_type"] == "contact") {
            $nom = htmlspecialchars($_POST["name"]);
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

<<<<<<< HEAD
=======
            //  Gestion du CV
>>>>>>> 39f50a591dd6e3afd8d581ed3ceed1ee5971872c
            $uploadDir = "uploads/";
            $cvFileName = basename($_FILES["cv"]["name"]);
            $uploadFile = $uploadDir . $cvFileName;

            if (move_uploaded_file($_FILES["cv"]["tmp_name"], $uploadFile)) {
<<<<<<< HEAD
=======
                // Enregistrer la candidature en base de données
>>>>>>> 39f50a591dd6e3afd8d581ed3ceed1ee5971872c
                $stmt = $pdo->prepare("INSERT INTO candidatures (nom, email, cv_path) VALUES (?, ?, ?)");
                $stmt->execute([$nom, $email, $uploadFile]);
                echo "<p style='color: green;'>Candidature envoyée avec succès !</p>";
            } else {
                echo "<p style='color: red;'>Erreur lors du téléchargement du CV.</p>";
            }
        }

<<<<<<< HEAD
        // Traitement du formulaire de réservation
        elseif ($_POST["form_type"] == "reservation") {
            $nom          = htmlspecialchars($_POST["name"]);
            $email        = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
            $date         = $_POST["date"];
            $time         = $_POST["time"];
            $nb_personnes = (int) $_POST["nb-personnes"];

            // =============================
            // VÉRIFICATION PAR JOURNÉE
            // =============================
            $capacite_max = 250;

            // Total des personnes déjà réservées ce jour-là (hors réservations refusées)
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(nb_personnes), 0)
                FROM reservations
                WHERE date = ? AND status != 'refusée'
            ");
            $stmt->execute([$date]);
            $places_prises    = (int) $stmt->fetchColumn();
            $places_restantes = $capacite_max - $places_prises;

            if ($nb_personnes > $places_restantes) {
                if ($places_restantes <= 0) {
                    echo "<p style='color: red;'>Désolé, le restaurant est complet pour le <strong>" . htmlspecialchars($date) . "</strong>. Veuillez choisir une autre date.</p>";
                } else {
                    echo "<p style='color: red;'>Il ne reste que <strong>" . $places_restantes . " place(s)</strong> pour le <strong>" . htmlspecialchars($date) . "</strong>. Veuillez réduire le nombre de personnes ou choisir une autre date.</p>";
                }
            } else {
                // Places disponibles → on enregistre
                $stmt = $pdo->prepare("INSERT INTO reservations (nom, email, date, nb_personnes, time) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $email, $date, $nb_personnes, $time]);
                echo "<p style='color: green;'>✅ Réservation confirmée ! Il reste " . ($places_restantes - $nb_personnes) . " place(s) pour cette journée.</p>";
            }
        }
    }
}
=======
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

>>>>>>> 39f50a591dd6e3afd8d581ed3ceed1ee5971872c
