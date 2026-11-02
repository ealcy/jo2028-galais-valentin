<?php
// Suppression : un genre (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-genres.php");
}
check_csrf("manage-genres.php"); // Protection CSRF

$id = lire_id('id_genre', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-genres.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM ATHLETE WHERE id_genre = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : ce genre est lié à des athlètes.";
        redirect("manage-genres.php");
    }

    $st = $connexion->prepare("DELETE FROM GENRE WHERE id_genre = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "Le genre a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Le genre demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression GENRE : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-genres.php");
