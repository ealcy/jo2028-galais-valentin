<?php
// Modification : un sport
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_sport');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-sports.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM SPORT WHERE id_sport = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture SPORT : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "Le sport demandé est introuvable.";
    redirect("manage-sports.php");
}

$erreur = "";
$valeurs = [
    'nom_sport' => (string) $ligne['nom_sport'],
];

// Listes déroulantes


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-sport.php?id_sport=" . $id); // Protection CSRF

    $valeurs['nom_sport'] = post_text('nom_sport');

    // Validation
    if ($erreur === "" && $valeurs['nom_sport'] === "") { $erreur = "Le champ « Nom du sport » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_sport']) > 255) { $erreur = "Le champ « Nom du sport » ne doit pas dépasser 255 caractères."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM SPORT WHERE nom_sport = :nom_sport AND id_sport <> :id_courant");
        $st->execute([':nom_sport' => $valeurs['nom_sport'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce sport existe déjà."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE SPORT SET nom_sport = :nom_sport WHERE id_sport = :id_courant");
            $st->execute([':nom_sport' => $valeurs['nom_sport'], ':id_courant' => $id]);
            $_SESSION['success'] = "Le sport a été modifié avec succès.";
            redirect("manage-sports.php");
        } catch (PDOException $e) {
            error_log("Erreur modification SPORT : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un sport", "../../../");
?>
        <h1>Modifier un sport</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-sport.php?id_sport=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_sport">Nom du sport :</label>
            <input type="text" name="nom_sport" id="nom_sport" maxlength="255" value="<?php echo e($valeurs['nom_sport']); ?>" required>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-sports.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
