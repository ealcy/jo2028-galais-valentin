<?php
// Suppression : un pays (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-countries.php");
}
check_csrf("manage-countries.php"); // Protection CSRF

$id = lire_id('id_pays', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-countries.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM ATHLETE WHERE id_pays = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : ce pays est lié à des athlètes.";
        redirect("manage-countries.php");
    }

    $st = $connexion->prepare("DELETE FROM PAYS WHERE id_pays = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "Le pays a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Le pays demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression PAYS : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-countries.php");
