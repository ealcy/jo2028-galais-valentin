<?php
// Ajout : un pays
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_pays' => ''];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-country.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
    $valeurs['nom_pays'] = post_text('nom_pays');

    // Validation
    if ($erreur === "" && $valeurs['nom_pays'] === "") { $erreur = "Le champ « Nom du pays » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_pays']) > 255) { $erreur = "Le champ « Nom du pays » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM PAYS WHERE nom_pays = :nom_pays");
        $st->execute([':nom_pays' => $valeurs['nom_pays']]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce pays existe déjà."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO PAYS (nom_pays) VALUES (:nom_pays)");
            $st->execute([':nom_pays' => $valeurs['nom_pays']]);
            $_SESSION['success'] = "Le pays a été ajouté avec succès.";
            redirect("manage-countries.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout PAYS : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un pays", "../../../");
?>
        <h1>Ajouter un pays</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-country.php" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_pays">Nom du pays :</label>
            <input type="text" name="nom_pays" id="nom_pays" maxlength="255" value="<?php echo e($valeurs['nom_pays']); ?>" required>
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-countries.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
