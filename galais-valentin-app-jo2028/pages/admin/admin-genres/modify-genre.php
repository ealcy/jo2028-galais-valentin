<?php
// Modification : un genre
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_genre');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-genres.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM GENRE WHERE id_genre = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture GENRE : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "Le genre demandé est introuvable.";
    redirect("manage-genres.php");
}

$erreur = "";
$valeurs = [
    'nom_genre' => (string) $ligne['nom_genre'],
];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-genre.php?id_genre=" . $id); // Protection CSRF

    $valeurs['nom_genre'] = post_text('nom_genre');

    // Validation
    if ($erreur === "" && $valeurs['nom_genre'] === "") { $erreur = "Le champ « Nom du genre » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_genre']) > 255) { $erreur = "Le champ « Nom du genre » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM GENRE WHERE nom_genre = :nom_genre AND id_genre <> :id_courant");
        $st->execute([':nom_genre' => $valeurs['nom_genre'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce genre existe déjà."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE GENRE SET nom_genre = :nom_genre WHERE id_genre = :id_courant");
            $st->execute([':nom_genre' => $valeurs['nom_genre'], ':id_courant' => $id]);
            $_SESSION['success'] = "Le genre a été modifié avec succès.";
            redirect("manage-genres.php");
        } catch (PDOException $e) {
            error_log("Erreur modification GENRE : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un genre", "../../../");
?>
        <h1>Modifier un genre</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-genre.php?id_genre=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_genre">Nom du genre :</label>
            <input type="text" name="nom_genre" id="nom_genre" maxlength="255" value="<?php echo e($valeurs['nom_genre']); ?>" required>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-genres.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
