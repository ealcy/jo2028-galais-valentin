<?php
// Ajout : un lieu
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_lieu' => '', 'adresse_lieu' => '', 'cp_lieu' => '', 'ville_lieu' => ''];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-place.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
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

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM LIEU WHERE nom_lieu = :nom_lieu");
        $st->execute([':nom_lieu' => $valeurs['nom_lieu']]);
        if ($st->fetchColumn() > 0) { $erreur = "Un lieu portant ce nom existe déjà."; }
    }
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM LIEU WHERE adresse_lieu = :adresse_lieu AND cp_lieu = :cp_lieu AND ville_lieu = :ville_lieu");
        $st->execute([':adresse_lieu' => $valeurs['adresse_lieu'], ':cp_lieu' => $valeurs['cp_lieu'], ':ville_lieu' => $valeurs['ville_lieu']]);
        if ($st->fetchColumn() > 0) { $erreur = "Un lieu existe déjà à cette adresse."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO LIEU (nom_lieu, adresse_lieu, cp_lieu, ville_lieu) VALUES (:nom_lieu, :adresse_lieu, :cp_lieu, :ville_lieu)");
            $st->execute([':nom_lieu' => $valeurs['nom_lieu'], ':adresse_lieu' => $valeurs['adresse_lieu'], ':cp_lieu' => $valeurs['cp_lieu'], ':ville_lieu' => $valeurs['ville_lieu']]);
            $_SESSION['success'] = "Le lieu a été ajouté avec succès.";
            redirect("manage-places.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout LIEU : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un lieu", "../../../");
?>
        <h1>Ajouter un lieu</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-place.php" method="post">
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
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-places.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
