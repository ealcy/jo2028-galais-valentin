<?php
// Ajout : un genre
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_genre' => ''];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-genre.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
    $valeurs['nom_genre'] = post_text('nom_genre');

    // Validation
    if ($erreur === "" && $valeurs['nom_genre'] === "") { $erreur = "Le champ « Nom du genre » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_genre']) > 255) { $erreur = "Le champ « Nom du genre » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM GENRE WHERE nom_genre = :nom_genre");
        $st->execute([':nom_genre' => $valeurs['nom_genre']]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce genre existe déjà."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO GENRE (nom_genre) VALUES (:nom_genre)");
            $st->execute([':nom_genre' => $valeurs['nom_genre']]);
            $_SESSION['success'] = "Le genre a été ajouté avec succès.";
            redirect("manage-genres.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout GENRE : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un genre", "../../../");
?>
        <h1>Ajouter un genre</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-genre.php" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_genre">Nom du genre :</label>
            <input type="text" name="nom_genre" id="nom_genre" maxlength="255" value="<?php echo e($valeurs['nom_genre']); ?>" required>
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-genres.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
