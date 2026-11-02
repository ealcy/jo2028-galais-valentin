<?php
// Suppression : un résultat (uniquement en POST, avec jeton CSRF)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirect("manage-results.php");
}
check_csrf("manage-results.php"); // Protection CSRF

$id_athlete = lire_id('id_athlete', INPUT_POST);
$id_epreuve = lire_id('id_epreuve', INPUT_POST);
if ($id_athlete === null || $id_epreuve === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-results.php");
}

try {
    $st = $connexion->prepare("DELETE FROM PARTICIPER WHERE id_athlete = :a AND id_epreuve = :e");
    $st->execute([':a' => $id_athlete, ':e' => $id_epreuve]);

    if ($st->rowCount() > 0) {
        $_SESSION['success'] = "Le résultat a été supprimé avec succès.";
    } else {
        $_SESSION['error'] = "Le résultat demandé est introuvable.";
    }
} catch (PDOException $e) {
    error_log("Erreur suppression PARTICIPER : " . $e->getMessage());
    $_SESSION['error'] = "Erreur de base de données : la suppression a échoué.";
}

redirect("manage-results.php");
