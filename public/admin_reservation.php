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
if (empty($_SESSION['admin_logged_in'])) {
    echo '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion - Administration</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0f0e0d;--surface:#1a1917;--gold:#c9924a;--text:#e8e0d8}
body{font-family:"DM Sans",sans-serif;background:var(--bg);min-height:100vh;display:flex;align-items:center;justify-content:center;background-image:radial-gradient(ellipse at 20% 50%,rgba(180,120,60,.08) 0%,transparent 60%),radial-gradient(ellipse at 80% 20%,rgba(180,120,60,.05) 0%,transparent 50%)}
.login-box{background:var(--surface);border:1px solid rgba(180,120,60,.2);border-radius:4px;padding:52px 48px;width:100%;max-width:400px;animation:fadeIn .5s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.login-logo{font-family:"Playfair Display",serif;color:var(--gold);font-size:1.5rem;text-align:center;letter-spacing:.05em;margin-bottom:8px}
.login-subtitle{text-align:center;color:#6b6560;font-size:.8rem;letter-spacing:.15em;text-transform:uppercase;margin-bottom:40px}
label{display:block;color:#8a7e76;font-size:.75rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:8px}
input[type=email],input[type=password]{width:100%;background:var(--bg);border:1px solid rgba(255,255,255,.08);border-radius:3px;color:var(--text);padding:12px 16px;font-family:"DM Sans",sans-serif;font-size:.9rem;margin-bottom:20px;outline:none;transition:border-color .2s}
input[type=email]:focus,input[type=password]:focus{border-color:rgba(180,120,60,.5)}
.btn-login{width:100%;background:var(--gold);color:var(--bg);border:none;padding:13px;border-radius:3px;font-family:"DM Sans",sans-serif;font-weight:500;font-size:.85rem;letter-spacing:.1em;text-transform:uppercase;cursor:pointer;transition:background .2s;margin-top:4px}
.btn-login:hover{background:#b8813b}
.error{background:rgba(220,80,60,.1);border:1px solid rgba(220,80,60,.3);color:#e07060;padding:10px 14px;border-radius:3px;font-size:.82rem;margin-bottom:20px;text-align:center}
</style>
</head>
<body>
<div class="login-box">
<div class="login-logo">&#127374; Restaurant</div>
<div class="login-subtitle">Espace Administration</div>';
    if (!empty($login_error)) {
        echo '<div class="error">' . htmlspecialchars($login_error) . '</div>';
    }
    echo '<form method="post">
<label for="email">Adresse email</label>
<input type="email" id="email" name="email" required autocomplete="email">
<label for="password">Mot de passe</label>
<input type="password" id="password" name="password" required autocomplete="current-password">
<button class="btn-login" type="submit" name="login">Se connecter</button>
</form></div></body></html>';
    exit;
}

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
// RECHERCHE
// =============================
$recherche      = trim($_GET['recherche'] ?? '');
$filtre         = $_GET['filtre'] ?? 'en attente';
$filtres_ok     = ['en attente', 'acceptée', 'refusée', 'toutes'];
if (!in_array($filtre, $filtres_ok)) { $filtre = 'en attente'; }

// Construction de la requête dynamique selon recherche + filtre
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

// Compteurs
$nb_attente  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'en attente'")->fetchColumn();
$nb_acceptee = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'acceptée'")->fetchColumn();
$nb_refusee  = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'refusée'")->fetchColumn();

// =============================
// PAGE ADMIN
// =============================
echo '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration - Reservations</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0f0e0d;--surface:#1a1917;--surface2:#221f1c;--border:rgba(255,255,255,.07);--gold:#c9924a;--gold-dim:rgba(180,120,60,.15);--text:#e8e0d8;--muted:#7a7068;--green:#5a9e6f;--red:#c0574a;--amber:#c99a3a}
body{font-family:"DM Sans",sans-serif;background:var(--bg);color:var(--text);min-height:100vh;background-image:radial-gradient(ellipse at 10% 0%,rgba(180,120,60,.06) 0%,transparent 50%)}
header{display:flex;align-items:center;justify-content:space-between;padding:20px 40px;border-bottom:1px solid var(--border);background:var(--surface)}
.logo{font-family:"Playfair Display",serif;color:var(--gold);font-size:1.3rem;letter-spacing:.04em}
.logo span{color:var(--muted);font-size:.75rem;font-family:"DM Sans",sans-serif;margin-left:12px;letter-spacing:.1em;text-transform:uppercase}
.admin-email{color:var(--muted);font-size:.8rem}
.header-right{display:flex;align-items:center;gap:12px}
.logout-btn{background:transparent;border:1px solid var(--border);color:var(--muted);padding:8px 18px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:all .2s}
.logout-btn:hover{border-color:var(--gold);color:var(--gold)}
main{max-width:1200px;margin:0 auto;padding:40px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:36px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:24px 28px;display:flex;flex-direction:column;gap:6px}
.stat-label{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted)}
.stat-value{font-size:2.2rem;font-weight:300}
.stat-card.amber .stat-value{color:var(--amber)}
.stat-card.green .stat-value{color:var(--green)}
.stat-card.red .stat-value{color:var(--red)}

/* BARRE DE RECHERCHE */
.search-bar{display:flex;gap:10px;margin-bottom:20px}
.search-bar input{flex:1;background:var(--surface);border:1px solid var(--border);color:var(--text);padding:10px 16px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.88rem;outline:none;transition:border-color .2s}
.search-bar input:focus{border-color:var(--gold)}
.search-bar input::placeholder{color:var(--muted)}
.btn-search{background:var(--gold);color:var(--bg);border:none;padding:10px 20px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.82rem;font-weight:500;cursor:pointer;transition:background .2s;white-space:nowrap}
.btn-search:hover{background:#b8813b}
.btn-reset{background:transparent;color:var(--muted);border:1px solid var(--border);padding:10px 16px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.82rem;cursor:pointer;transition:all .2s;text-decoration:none;white-space:nowrap}
.btn-reset:hover{border-color:var(--gold);color:var(--gold)}
.search-info{font-size:.8rem;color:var(--muted);margin-bottom:16px}
.search-info strong{color:var(--gold)}

.filtres{display:flex;gap:8px;margin-bottom:24px}
.filtre-btn{padding:9px 20px;border-radius:3px;border:1px solid var(--border);background:transparent;color:var(--muted);font-family:"DM Sans",sans-serif;font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all .2s}
.filtre-btn:hover,.filtre-btn.active{background:var(--gold-dim);border-color:var(--gold);color:var(--gold)}
.msg{padding:12px 18px;border-radius:3px;font-size:.84rem;margin-bottom:20px;border:1px solid}
.msg.success{background:rgba(90,158,111,.1);border-color:rgba(90,158,111,.3);color:#7ac492}
.msg.error{background:rgba(192,87,74,.1);border-color:rgba(192,87,74,.3);color:#e07060}
.table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:4px;overflow:hidden}
table{width:100%;border-collapse:collapse}
thead tr{border-bottom:1px solid var(--border)}
th{padding:14px 18px;text-align:left;font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);font-weight:400;background:var(--surface2)}
td{padding:14px 18px;font-size:.88rem;border-bottom:1px solid var(--border);color:var(--text);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;letter-spacing:.06em;font-weight:500}
.badge.attente{background:rgba(201,154,58,.15);color:var(--amber);border:1px solid rgba(201,154,58,.3)}
.badge.acceptee{background:rgba(90,158,111,.15);color:var(--green);border:1px solid rgba(90,158,111,.3)}
.badge.refusee{background:rgba(192,87,74,.15);color:var(--red);border:1px solid rgba(192,87,74,.3)}
.action-form{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
select{background:var(--bg);border:1px solid var(--border);color:var(--text);padding:7px 12px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.82rem;outline:none;cursor:pointer}
.btn-valider{background:var(--gold);color:var(--bg);border:none;padding:7px 16px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.78rem;font-weight:500;cursor:pointer;transition:background .2s;white-space:nowrap}
.btn-valider:hover{background:#b8813b}
.btn-supprimer{background:transparent;color:var(--red);border:1px solid rgba(192,87,74,.4);padding:7px 14px;border-radius:3px;font-family:"DM Sans",sans-serif;font-size:.78rem;cursor:pointer;transition:all .2s;white-space:nowrap}
.btn-supprimer:hover{background:rgba(192,87,74,.15);border-color:var(--red)}
.empty{text-align:center;padding:60px;color:var(--muted);font-size:.88rem;letter-spacing:.05em}
@media(max-width:768px){main{padding:24px 16px}header{padding:16px 20px}.stats{grid-template-columns:1fr}table{font-size:.8rem}th,td{padding:10px 12px}.search-bar{flex-direction:column}}
</style>
</head>
<body>
<header>
<div class="logo">&#127374; Restaurant <span>Administration</span></div>
<div class="header-right">
<span class="admin-email">' . htmlspecialchars($_SESSION['admin_email']) . '</span>
<form method="post"><button class="logout-btn" name="logout">Deconnexion</button></form>
</div>
</header>
<main>

<div class="stats">
<div class="stat-card amber"><div class="stat-label">En attente</div><div class="stat-value">' . $nb_attente . '</div></div>
<div class="stat-card green"><div class="stat-label">Acceptees</div><div class="stat-value">' . $nb_acceptee . '</div></div>
<div class="stat-card red"><div class="stat-label">Refusees</div><div class="stat-value">' . $nb_refusee . '</div></div>
</div>';

if (!empty($message)) {
    echo '<div class="msg ' . $message['type'] . '">' . htmlspecialchars($message['text']) . '</div>';
}

// Barre de recherche
$filtre_url = urlencode($filtre);
echo '<form method="get" class="search-bar">
<input type="hidden" name="filtre" value="' . htmlspecialchars($filtre) . '">
<input type="text" name="recherche" placeholder="Rechercher par nom, email ou date (ex: 2026-03-05)..." value="' . htmlspecialchars($recherche) . '">
<button class="btn-search" type="submit">&#128269; Rechercher</button>';
if ($recherche !== '') {
    echo '<a href="?filtre=' . $filtre_url . '" class="btn-reset">&#10005; Effacer</a>';
}
echo '</form>';

// Info résultats recherche
if ($recherche !== '') {
    echo '<div class="search-info">' . count($reservations) . ' résultat(s) pour <strong>"' . htmlspecialchars($recherche) . '"</strong></div>';
}

// Filtres
echo '<div class="filtres">';
foreach (['en attente' => 'En attente', 'acceptée' => 'Acceptees', 'refusée' => 'Refusees', 'toutes' => 'Toutes'] as $val => $label) {
    $active = $filtre === $val ? 'active' : '';
    $rech_url = $recherche !== '' ? '&recherche=' . urlencode($recherche) : '';
    echo '<a href="?filtre=' . urlencode($val) . $rech_url . '" class="filtre-btn ' . $active . '">' . $label . '</a>';
}
echo '</div>';

// Tableau
echo '<div class="table-wrap">';
if (count($reservations) > 0) {
    echo '<table><thead><tr>
<th>ID</th><th>Nom</th><th>Email</th><th>Date</th><th>Heure</th><th>Personnes</th><th>Statut</th><th>Actions</th>
</tr></thead><tbody>';
    foreach ($reservations as $r) {
        $badgeClass = match($r['status']) {
            'acceptée' => 'acceptee',
            'refusée'  => 'refusee',
            default    => 'attente'
        };
        echo '<tr>
<td style="color:var(--muted)">#' . (int)$r['id'] . '</td>
<td>' . htmlspecialchars($r['nom']) . '</td>
<td style="color:var(--muted)">' . htmlspecialchars($r['email']) . '</td>
<td>' . htmlspecialchars($r['date']) . '</td>
<td>' . htmlspecialchars($r['time']) . '</td>
<td>' . (int)$r['nb_personnes'] . '</td>
<td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($r['status']) . '</span></td>
<td>
<form method="post" class="action-form">
<input type="hidden" name="id" value="' . (int)$r['id'] . '">
<select name="new_status">
<option value="acceptee">Accepter</option>
<option value="refusee">Refuser</option>
</select>
<button class="btn-valider" type="submit">Valider</button>
</form>
<form method="post" class="action-form" style="margin-top:6px" onsubmit="return confirm(\'Supprimer cette reservation ?\')">
<input type="hidden" name="delete_id" value="' . (int)$r['id'] . '">
<button class="btn-supprimer" type="submit">&#128465; Supprimer</button>
</form>
</td>
</tr>';
    }
    echo '</tbody></table>';
} else {
    $msg_vide = $recherche !== '' ? 'Aucun résultat pour "' . htmlspecialchars($recherche) . '".' : 'Aucune reservation trouvee pour ce filtre.';
    echo '<div class="empty">' . $msg_vide . '</div>';
}

echo '</div></main></body></html>';
