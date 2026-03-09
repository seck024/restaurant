<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// =============================
// CONNEXION PDO
// =============================
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bts_project;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}

// =============================
// CONNEXION ADMIN VIA BDD
// =============================
if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email']     = $admin['email'];
    } else {
        $login_error = "Email ou mot de passe incorrect.";
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin_reservation.php");
    exit;
}

// =============================
// PAGE LOGIN
// =============================
if (empty($_SESSION['admin_logged_in'])) { ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connexion - Administration</title>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="admin.css">
    </head>
    <body class="login-page">
    <div class="login-box">
        <div class="login-logo">&#127374; Restaurant</div>
        <div class="login-subtitle">Espace Administration</div>
        <?php if (!empty($login_error)): ?>
            <div class="error"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>
        <form method="post">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button class="btn-login" type="submit" name="login">Se connecter</button>
        </form>
    </div>
    </body>
    </html>
    <?php exit; }

// =============================
// FONCTION ENVOI EMAIL
// =============================
function envoyerEmail($destinataire, $nom, $statut, $date, $heure, $nb_personnes) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'seck777696931@gmail.com';
        $mail->Password   = 'vlex exux stgw idyd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('seck777696931@gmail.com', 'Restaurant');
        $mail->addAddress($destinataire, $nom);
        if ($statut === 'acceptée') {
            $mail->Subject = 'Votre reservation est confirmee !';
            $mail->Body    = "Bonjour " . $nom . ",\n\nNous avons le plaisir de vous confirmer votre reservation :\n\nDate      : " . $date . "\nHeure     : " . $heure . "\nPersonnes : " . $nb_personnes . "\n\nNous vous attendons avec plaisir !\n\nCordialement,\nL'equipe du Restaurant";
        } else {
            $mail->Subject = 'Votre reservation n\'a pas pu etre acceptee';
            $mail->Body    = "Bonjour " . $nom . ",\n\nNous sommes desoles de vous informer que votre reservation du " . $date . " a " . $heure . " pour " . $nb_personnes . " personne(s) n'a pas pu etre acceptee.\n\nN'hesitez pas a nous contacter ou a choisir une autre date.\n\nCordialement,\nL'equipe du Restaurant";
        }
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        return false;
    }
}

// =============================
// TRAITEMENT : MISE A JOUR STATUT
// =============================
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['new_status'])) {
    $allowed = ['acceptee', 'refusee'];
    $id = (int) $_POST['id'];
    $newStatus = $_POST['new_status'];
    if (!in_array($newStatus, $allowed)) {
        $message = ['type' => 'error', 'text' => 'Statut invalide.'];
    } else {
        $realStatus = $newStatus === 'acceptee' ? 'acceptée' : 'refusée';
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$id]);
        $resa = $stmt->fetch();
        $update = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $update->execute([$realStatus, $id]);
        if ($resa) {
            $emailEnvoye = envoyerEmail($resa['email'], $resa['nom'], $realStatus, $resa['date'], $resa['time'], $resa['nb_personnes']);
            $message = $emailEnvoye
                ? ['type' => 'success', 'text' => 'Statut mis a jour et email envoye a ' . $resa['email'] . ' !']
                : ['type' => 'error',   'text' => 'Statut mis a jour mais echec envoi email.'];
        } else {
            $message = ['type' => 'success', 'text' => 'Statut mis a jour.'];
        }
    }
}

// =============================
// TRAITEMENT : SUPPRESSION
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];
    $delete = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $delete->execute([$delete_id]);
    $message = ['type' => 'success', 'text' => 'Reservation supprimee avec succes.'];
}

// =============================
// RECHERCHE + FILTRES
// =============================
$recherche  = trim($_GET['recherche'] ?? '');
$filtre     = $_GET['filtre'] ?? 'en attente';
$filtres_ok = ['en attente', 'acceptée', 'refusée', 'toutes'];
if (!in_array($filtre, $filtres_ok)) { $filtre = 'en attente'; }

if ($recherche !== '') {
    $search = '%' . $recherche . '%';
    if ($filtre === 'toutes') {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE (nom LIKE ? OR email LIKE ? OR date LIKE ?) ORDER BY date DESC, time DESC");
        $stmt->execute([$search, $search, $search]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE (nom LIKE ? OR email LIKE ? OR date LIKE ?) AND status = ? ORDER BY date DESC, time DESC");
        $stmt->execute([$search, $search, $search, $filtre]);
    }
} else {
    if ($filtre === 'toutes') {
        $stmt = $pdo->query("SELECT * FROM reservations ORDER BY date DESC, time DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE status = ? ORDER BY date DESC, time DESC");
        $stmt->execute([$filtre]);
    }
}
$reservations = $stmt->fetchAll();

$nb_attente  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'en attente'")->fetchColumn();
$nb_acceptee = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'acceptée'")->fetchColumn();
$nb_refusee  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'refusée'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Reservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-page">

<header>
    <div class="logo">&#127374; Restaurant <span>Administration</span></div>
    <div class="header-right">
        <span class="admin-email"><?= htmlspecialchars($_SESSION['admin_email']) ?></span>
        <form method="post">
            <button class="logout-btn" name="logout">Deconnexion</button>
        </form>
    </div>
</header>

<main>
    <!-- STATS -->
    <div class="stats">
        <div class="stat-card amber">
            <div class="stat-label">En attente</div>
            <div class="stat-value"><?= $nb_attente ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Acceptees</div>
            <div class="stat-value"><?= $nb_acceptee ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Refusees</div>
            <div class="stat-value"><?= $nb_refusee ?></div>
        </div>
    </div>

    <!-- MESSAGE -->
    <?php if (!empty($message)): ?>
        <div class="msg <?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
    <?php endif; ?>

    <!-- RECHERCHE -->
    <form method="get" class="search-bar">
        <input type="hidden" name="filtre" value="<?= htmlspecialchars($filtre) ?>">
        <input type="text" name="recherche" placeholder="Rechercher par nom, email ou date (ex: 2026-03-05)..." value="<?= htmlspecialchars($recherche) ?>">
        <button class="btn-search" type="submit">&#128269; Rechercher</button>
        <?php if ($recherche !== ''): ?>
            <a href="?filtre=<?= urlencode($filtre) ?>" class="btn-reset">&#10005; Effacer</a>
        <?php endif; ?>
    </form>

    <?php if ($recherche !== ''): ?>
        <div class="search-info"><?= count($reservations) ?> résultat(s) pour <strong>"<?= htmlspecialchars($recherche) ?>"</strong></div>
    <?php endif; ?>

    <!-- FILTRES -->
    <div class="filtres">
        <?php foreach (['en attente' => 'En attente', 'acceptée' => 'Acceptees', 'refusée' => 'Refusees', 'toutes' => 'Toutes'] as $val => $label):
            $active   = $filtre === $val ? 'active' : '';
            $rech_url = $recherche !== '' ? '&recherche=' . urlencode($recherche) : '';
            ?>
            <a href="?filtre=<?= urlencode($val) . $rech_url ?>" class="filtre-btn <?= $active ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <!-- TABLEAU -->
    <div class="table-wrap">
        <?php if (count($reservations) > 0): ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th><th>Nom</th><th>Email</th><th>Date</th>
                    <th>Heure</th><th>Personnes</th><th>Statut</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $r):
                    $badgeClass = match($r['status']) {
                        'acceptée' => 'acceptee',
                        'refusée'  => 'refusee',
                        default    => 'attente'
                    };
                    ?>
                    <tr>
                        <td style="color:var(--muted)">#<?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['nom']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['date']) ?></td>
                        <td><?= htmlspecialchars($r['time']) ?></td>
                        <td><?= (int)$r['nb_personnes'] ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                        <td>
                            <form method="post" class="action-form">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select name="new_status">
                                    <option value="acceptee">Accepter</option>
                                    <option value="refusee">Refuser</option>
                                </select>
                                <button class="btn-valider" type="submit">Valider</button>
                            </form>
                            <form method="post" class="action-form" style="margin-top:6px" onsubmit="return confirm('Supprimer cette reservation ?')">
                                <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                                <button class="btn-supprimer" type="submit">&#128465; Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty">
                <?= $recherche !== '' ? 'Aucun résultat pour "' . htmlspecialchars($recherche) . '".' : 'Aucune reservation trouvee pour ce filtre.' ?>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>