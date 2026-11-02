<?php
// Suppression : un athlète (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Une suppression ne doit jamais se faire par simple lien (GET)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-athletes.php");
}
check_csrf("manage-athletes.php"); // Protection CSRF

$id = lire_id('id_athlete', INPUT_POST);
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-athletes.php");
}

try {
    // On empêche la suppression si d'autres données en dépendent (clés étrangères)
    $st = $connexion->prepare("SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :id");
    $st->execute([':id' => $id]);
    if ($st->fetchColumn() > 0) {
        $_SESSION['error'] = "Suppression impossible : cet athlète est lié à des résultats (supprimez d'abord les résultats associés).";
        redirect("manage-athletes.php");
    }

    $st = $connexion->prepare("DELETE FROM ATHLETE WHERE id_athlete = :id");
    $st->execute([':id' => $id]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "L'athlète a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "L'athlète demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression ATHLETE : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-athletes.php");
