<?php
session_start(); // Démarre la session PHP pour stocker des variables de session.

require_once("database.php"); // Inclut le fichier de connexion à la base de données.

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cette page ne traite que le formulaire de connexion (méthode POST)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/login.php");
    exit();
}

// Vérifie le token CSRF
$jeton = $_POST['csrf_token'] ?? '';
if (!is_string($jeton) || !hash_equals($_SESSION['csrf_token'], $jeton)) {
    $_SESSION['error'] = "Token CSRF invalide.";
    header("Location: ../pages/login.php");
    exit();
}

// Récupération des données : le login est nettoyé des espaces, le mot de passe
// n'est surtout pas modifié (il sera comparé au hash Bcrypt avec password_verify).
$login = isset($_POST['login']) && is_string($_POST['login']) ? trim($_POST['login']) : '';
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

// Si le login ou mot de passe est vide
if ($login === '' || $password === '') {
    $_SESSION['error'] = "Login ou mot de passe manquant.";
    header("Location: ../pages/login.php");
    exit();
}

try {
    // Requête préparée : le login est transmis séparément du SQL (protection contre les injections SQL)
    $query = "SELECT id_admin, nom_admin, prenom_admin, login, password FROM ADMINISTRATEUR WHERE login = :login";
    $stmt = $connexion->prepare($query);
    $stmt->bindParam(":login", $login, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Vérifie que l'administrateur existe et que le mot de passe correspond au hash Bcrypt
    if ($row && password_verify($password, $row["password"])) {
        // Nouvel identifiant de session après connexion (protection contre la fixation de session)
        session_regenerate_id(true);

        $_SESSION["id_admin"] = $row["id_admin"];
        $_SESSION["nom_admin"] = $row["nom_admin"];
        $_SESSION["prenom_admin"] = $row["prenom_admin"];
        $_SESSION["login"] = $row["login"];

        header("Location: ../pages/admin/admin.php");
        exit();
    }

    $_SESSION['error'] = "Login ou mot de passe incorrect.";
} catch (PDOException $e) {
    error_log("Erreur de connexion admin : " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'exécution de la requête.";
}

header("Location: ../pages/login.php");
exit();
