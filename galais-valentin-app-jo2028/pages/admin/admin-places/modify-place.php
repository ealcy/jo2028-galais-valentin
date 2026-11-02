<?php
// Modification : un lieu
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_lieu');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-places.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM LIEU WHERE id_lieu = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture LIEU : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "Le lieu demandé est introuvable.";
    redirect("manage-places.php");
}

$erreur = "";
$valeurs = [
    'nom_lieu' => (string) $ligne['nom_lieu'],
    'adresse_lieu' => (string) $ligne['adresse_lieu'],
    'cp_lieu' => (string) $ligne['cp_lieu'],
    'ville_lieu' => (string) $ligne['ville_lieu'],
];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-place.php?id_lieu=" . $id); // Protection CSRF

    $valeurs['nom_lieu'] = post_text('nom_lieu');
    $valeurs['adresse_lieu'] = post_text('adresse_lieu');
    $valeurs['cp_lieu'] = post_text('cp_lieu');
    $valeurs['ville_lieu'] = post_text('ville_lieu');

    // Validation
    if ($erreur === "" && $valeurs['nom_lieu'] === "") { $erreur = "Le champ « Nom du lieu » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_lieu']) > 255) { $erreur = "Le champ « Nom du lieu » ne doit pas dépasser 255 caractères."; }
    if ($erreur === "" && $valeurs['adresse_lieu'] === "") { $erreur = "Le champ « Adresse » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['adresse_lieu']) > 255) { $erreur = "Le champ « Adresse » ne doit pas dépasser 255 caractères."; }
    if ($erreur === "" && $valeurs['cp_lieu'] === "") { $erreur = "Le champ « Code postal » est obligatoire."; }
    if ($erreur === "" && !preg_match('/^[0-9]{5}$/', $valeurs['cp_lieu'])) { $erreur = "Le code postal doit contenir exactement 5 chiffres."; }
    if ($erreur === "" && $valeurs['ville_lieu'] === "") { $erreur = "Le champ « Ville » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['ville_lieu']) > 255) { $erreur = "Le champ « Ville » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM LIEU WHERE nom_lieu = :nom_lieu AND id_lieu <> :id_courant");
        $st->execute([':nom_lieu' => $valeurs['nom_lieu'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Un lieu portant ce nom existe déjà."; }
    }
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM LIEU WHERE adresse_lieu = :adresse_lieu AND cp_lieu = :cp_lieu AND ville_lieu = :ville_lieu AND id_lieu <> :id_courant");
        $st->execute([':adresse_lieu' => $valeurs['adresse_lieu'], ':cp_lieu' => $valeurs['cp_lieu'], ':ville_lieu' => $valeurs['ville_lieu'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Un lieu existe déjà à cette adresse."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE LIEU SET nom_lieu = :nom_lieu, adresse_lieu = :adresse_lieu, cp_lieu = :cp_lieu, ville_lieu = :ville_lieu WHERE id_lieu = :id_courant");
            $st->execute([':nom_lieu' => $valeurs['nom_lieu'], ':adresse_lieu' => $valeurs['adresse_lieu'], ':cp_lieu' => $valeurs['cp_lieu'], ':ville_lieu' => $valeurs['ville_lieu'], ':id_courant' => $id]);
            $_SESSION['success'] = "Le lieu a été modifié avec succès.";
            redirect("manage-places.php");
        } catch (PDOException $e) {
            error_log("Erreur modification LIEU : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un lieu", "../../../");
?>
        <h1>Modifier un lieu</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-place.php?id_lieu=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_lieu">Nom du lieu :</label>
            <input type="text" name="nom_lieu" id="nom_lieu" maxlength="255" value="<?php echo e($valeurs['nom_lieu']); ?>" required>
            <label for="adresse_lieu">Adresse :</label>
            <input type="text" name="adresse_lieu" id="adresse_lieu" maxlength="255" value="<?php echo e($valeurs['adresse_lieu']); ?>" required>
            <label for="cp_lieu">Code postal :</label>
            <input type="text" name="cp_lieu" id="cp_lieu" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" value="<?php echo e($valeurs['cp_lieu']); ?>" required>
            <span class="form-hint">5 chiffres</span>
            <label for="ville_lieu">Ville :</label>
            <input type="text" name="ville_lieu" id="ville_lieu" maxlength="255" value="<?php echo e($valeurs['ville_lieu']); ?>" required>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
