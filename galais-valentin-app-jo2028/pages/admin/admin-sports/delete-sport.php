<?php
// Suppression : un sport (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-sports.php");
}
check_csrf("manage-sports.php"); // Protection CSRF

$id = lire_id('id_sport', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-sports.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE id_sport = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : ce sport est lié à des épreuves du calendrier.";
        redirect("manage-sports.php");
    }

    $st = $connexion->prepare("DELETE FROM SPORT WHERE id_sport = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "Le sport a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Le sport demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression SPORT : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-sports.php");
