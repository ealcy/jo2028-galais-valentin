<?php
// Ajout : un sport
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_sport' => ''];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-sport.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
    $valeurs['nom_sport'] = post_text('nom_sport');

    // Validation
    if ($erreur === "" && $valeurs['nom_sport'] === "") { $erreur = "Le champ « Nom du sport » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_sport']) > 255) { $erreur = "Le champ « Nom du sport » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM SPORT WHERE nom_sport = :nom_sport");
        $st->execute([':nom_sport' => $valeurs['nom_sport']]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce sport existe déjà."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO SPORT (nom_sport) VALUES (:nom_sport)");
            $st->execute([':nom_sport' => $valeurs['nom_sport']]);
            $_SESSION['success'] = "Le sport a été ajouté avec succès.";
            redirect("manage-sports.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout SPORT : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un sport", "../../../");
?>
        <h1>Ajouter un sport</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-sport.php" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_sport">Nom du sport :</label>
            <input type="text" name="nom_sport" id="nom_sport" maxlength="255" value="<?php echo e($valeurs['nom_sport']); ?>" required>
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-sports.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
