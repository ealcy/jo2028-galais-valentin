<?php
// Modification : un pays
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_pays');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-countries.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM PAYS WHERE id_pays = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture PAYS : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "Le pays demandé est introuvable.";
    redirect("manage-countries.php");
}

$erreur = "";
$valeurs = [
    'nom_pays' => (string) $ligne['nom_pays'],
];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-country.php?id_pays=" . $id); // Protection CSRF

    $valeurs['nom_pays'] = post_text('nom_pays');

    // Validation
    if ($erreur === "" && $valeurs['nom_pays'] === "") { $erreur = "Le champ « Nom du pays » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_pays']) > 255) { $erreur = "Le champ « Nom du pays » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM PAYS WHERE nom_pays = :nom_pays AND id_pays <> :id_courant");
        $st->execute([':nom_pays' => $valeurs['nom_pays'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce pays existe déjà."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE PAYS SET nom_pays = :nom_pays WHERE id_pays = :id_courant");
            $st->execute([':nom_pays' => $valeurs['nom_pays'], ':id_courant' => $id]);
            $_SESSION['success'] = "Le pays a été modifié avec succès.";
            redirect("manage-countries.php");
        } catch (PDOException $e) {
            error_log("Erreur modification PAYS : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un pays", "../../../");
?>
        <h1>Modifier un pays</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-country.php?id_pays=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_pays">Nom du pays :</label>
            <input type="text" name="nom_pays" id="nom_pays" maxlength="255" value="<?php echo e($valeurs['nom_pays']); ?>" required>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-countries.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
