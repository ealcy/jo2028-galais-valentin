<?php
/*
 * includes/admin.php
 * Fonctions communes à toutes les pages de l'espace administrateur.
 * Chaque page admin commence par :
 *     require_once("../../../includes/admin.php");
 *     require_admin("../../../");
 */

// Démarre la session si elle ne l'est pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion PDO ($connexion)
require_once __DIR__ . '/../database/database.php';

/* ------------------------------------------------------------------ */
/* Sécurité                                                           */
/* ------------------------------------------------------------------ */

/**
 * Protection XSS : échappe une valeur avant de l'afficher dans la page HTML.
 */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige vers l'URL donnée puis arrête le script.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit();
}

/**
 * Protège une page : si l'utilisateur n'est pas connecté,
 * il est renvoyé vers la page de connexion.
 * $base = chemin relatif vers la racine du site (ex. "../../../").
 */
function require_admin(string $base): void
{
    if (!isset($_SESSION['id_admin']) || !isset($_SESSION['login'])) {
        $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
        redirect($base . 'pages/login.php');
    }
}

/**
 * Retourne le jeton CSRF de la session (le crée si besoin).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Champ caché à placer dans chaque formulaire POST.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Vérifie le jeton CSRF d'une requête POST. En cas d'échec, on redirige.
 */
function check_csrf(string $redirection): void
{
    $jeton = $_POST['csrf_token'] ?? '';
    if (!is_string($jeton) || !hash_equals(csrf_token(), $jeton)) {
        $_SESSION['error'] = "Jeton CSRF invalide : veuillez réessayer.";
        redirect($redirection);
    }
}

/* ------------------------------------------------------------------ */
/* Lecture / validation des données de formulaire                      */
/* ------------------------------------------------------------------ */

/**
 * Lit un entier positif dans l'URL (GET) ou dans un formulaire (POST).
 * Retourne null si la valeur est absente ou invalide.
 */
function lire_id(string $nom, int $source = INPUT_GET): ?int
{
    $id = filter_input($source, $nom, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

/**
 * Lit un champ texte POST : supprime les espaces en trop.
 * (Aucun "assainissement" destructif : l'échappement se fait à l'affichage.)
 */
function post_text(string $nom): string
{
    $valeur = $_POST[$nom] ?? '';
    if (!is_string($valeur)) {
        return '';
    }
    return trim(preg_replace('/\s+/u', ' ', $valeur));
}

/**
 * Vérifie qu'une date est au format AAAA-MM-JJ et qu'elle existe.
 */
function est_date_valide(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

/**
 * Normalise une heure "HH:MM" ou "HH:MM:SS" en "HH:MM". Retourne "" si invalide.
 */
function normaliser_heure(string $heure): string
{
    if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $heure, $m)) {
        return $m[1] . ':' . $m[2];
    }
    return '';
}

/**
 * Vérifie qu'une valeur choisie dans une liste déroulante existe bien
 * parmi les options proposées (colonne "id").
 */
function est_option_valide($valeur, array $options): bool
{
    foreach ($options as $option) {
        if ((string) $option['id'] === (string) $valeur) {
            return true;
        }
    }
    return false;
}

/* ------------------------------------------------------------------ */
/* Messages                                                           */
/* ------------------------------------------------------------------ */

/**
 * Affiche (puis efface) les messages d'erreur / succès stockés en session.
 */
function show_flash(): void
{
    if (isset($_SESSION['error'])) {
        echo '<p class="message message-error">' . e($_SESSION['error']) . '</p>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<p class="message message-success">' . e($_SESSION['success']) . '</p>';
        unset($_SESSION['success']);
    }
}

/* ------------------------------------------------------------------ */
/* Gabarit commun (en-tête / pied de page)                            */
/* ------------------------------------------------------------------ */

/**
 * Affiche le début de la page admin : <head>, menu, ouverture de <main>.
 */
function render_header(string $titre, string $base): void
{
    $menu = [
        'pages/admin/admin.php'                          => 'Accueil Administration',
        'pages/admin/admin-users/manage-users.php'       => 'Administrateurs',
        'pages/admin/admin-sports/manage-sports.php'     => 'Sports',
        'pages/admin/admin-places/manage-places.php'     => 'Lieux',
        'pages/admin/admin-countries/manage-countries.php' => 'Pays',
        'pages/admin/admin-genres/manage-genres.php'     => 'Genres',
        'pages/admin/admin-events/manage-events.php'     => 'Calendrier',
        'pages/admin/admin-athletes/manage-athletes.php' => 'Athlètes',
        'pages/admin/admin-results/manage-results.php'   => 'Résultats',
        'index.php'                                      => 'Site public',
        'pages/logout.php'                               => 'Déconnexion',
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo e($base); ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo e($base); ?>css/styles-computer.css">
    <link rel="stylesheet" href="<?php echo e($base); ?>css/styles-responsive.css">
    <link rel="shortcut icon" href="<?php echo e($base); ?>img/favicon.ico" type="image/x-icon">
    <title><?php echo e($titre); ?> - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu menu-admin">
<?php foreach ($menu as $lien => $libelle): ?>
                <li><a href="<?php echo e($base . $lien); ?>"><?php echo e($libelle); ?></a></li>
<?php endforeach; ?>
            </ul>
        </nav>
    </header>

    <main>
<?php
}

/**
 * Affiche la fin de la page admin : fermeture de <main>, footer.
 */
function render_footer(string $base): void
{
    ?>
    </main>

    <footer>
        <figure>
            <img src="<?php echo e($base); ?>img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
<?php
}
