<?php
// Suppression : une épreuve (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-events.php");
}
check_csrf("manage-events.php"); // Protection CSRF

$id = lire_id('id_epreuve', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-events.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM PARTICIPER WHERE id_epreuve = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : cette épreuve est liée à des résultats (supprimez d'abord les résultats associés).";
        redirect("manage-events.php");
    }

    $st = $connexion->prepare("DELETE FROM EPREUVE WHERE id_epreuve = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "L'épreuve a été supprimée avec succès.";
    } else {
        $_SESSION['error'] = "L'épreuve demandée est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression EPREUVE : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-events.php");
