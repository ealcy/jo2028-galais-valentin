<?php
// Suppression : un administrateur (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-users.php");
}
check_csrf("manage-users.php"); // Protection CSRF

$id = lire_id('id_admin', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-users.php");
}

// Un administrateur ne peut pas supprimer son propre compte
if ((int) $_SESSION['id_admin'] === $id) {
    $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte.";
    redirect("manage-users.php");
}

try {
    // On garde toujours au moins un administrateur
    $total = (int) $connexion->query("SELECT COUNT(*) FROM ADMINISTRATEUR")->fetchColumn();
    if ($total <= 1) {
        $_SESSION['error'] = "Suppression impossible : il doit rester au moins un administrateur.";
        redirect("manage-users.php");
    }

    $st = $connexion->prepare("DELETE FROM ADMINISTRATEUR WHERE id_admin = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "L'administrateur a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "L'administrateur demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression ADMINISTRATEUR : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-users.php");
