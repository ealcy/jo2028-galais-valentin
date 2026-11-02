<?php
// Suppression : un lieu (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-places.php");
}
check_csrf("manage-places.php"); // Protection CSRF

$id = lire_id('id_lieu', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-places.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE id_lieu = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : ce lieu est lié à des épreuves du calendrier.";
        redirect("manage-places.php");
    }

    $st = $connexion->prepare("DELETE FROM LIEU WHERE id_lieu = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "Le lieu a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Le lieu demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression LIEU : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-places.php");
