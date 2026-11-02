<?php
// Modification : un athlète
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_athlete');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-athletes.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM ATHLETE WHERE id_athlete = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture ATHLETE : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "L'athlète demandé est introuvable.";
    redirect("manage-athletes.php");
}

$erreur = "";
$valeurs = [
    'nom_athlete' => (string) $ligne['nom_athlete'],
    'prenom_athlete' => (string) $ligne['prenom_athlete'],
    'id_pays' => (string) $ligne['id_pays'],
    'id_genre' => (string) $ligne['id_genre'],
];

// Listes déroulantes
$options_id_pays = $connexion->query("SELECT id_pays AS id, nom_pays AS label FROM PAYS ORDER BY nom_pays")->fetchAll();
$options_id_genre = $connexion->query("SELECT id_genre AS id, nom_genre AS label FROM GENRE ORDER BY nom_genre")->fetchAll();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-athlete.php?id_athlete=" . $id); // Protection CSRF

    $valeurs['nom_athlete'] = mb_strtoupper(post_text('nom_athlete'), 'UTF-8');
    $valeurs['prenom_athlete'] = post_text('prenom_athlete');
    $valeurs['id_pays'] = post_text('id_pays');
    $valeurs['id_genre'] = post_text('id_genre');

    // Validation
    if ($erreur === "" && $valeurs['nom_athlete'] === "") { $erreur = "Le champ « Nom » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['nom_athlete']) > 255) { $erreur = "Le champ « Nom » ne doit pas dépasser 255 caractères."; }
    if ($erreur === "" && $valeurs['prenom_athlete'] === "") { $erreur = "Le champ « Prénom » est obligatoire."; }
    if ($erreur === "" && mb_strlen($valeurs['prenom_athlete']) > 255) { $erreur = "Le champ « Prénom » ne doit pas dépasser 255 caractères."; }
    if ($erreur === "" && $valeurs['id_pays'] === "") { $erreur = "Le champ « Pays » est obligatoire."; }
    if ($erreur === "" && !est_option_valide($valeurs['id_pays'], $options_id_pays)) { $erreur = "Le champ « Pays » contient une valeur invalide."; }
    if ($erreur === "" && $valeurs['id_genre'] === "") { $erreur = "Le champ « Genre » est obligatoire."; }
    if ($erreur === "" && !est_option_valide($valeurs['id_genre'], $options_id_genre)) { $erreur = "Le champ « Genre » contient une valeur invalide."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM ATHLETE WHERE nom_athlete = :nom_athlete AND prenom_athlete = :prenom_athlete AND id_pays = :id_pays AND id_athlete <> :id_courant");
        $st->execute([':nom_athlete' => $valeurs['nom_athlete'], ':prenom_athlete' => $valeurs['prenom_athlete'], ':id_pays' => $valeurs['id_pays'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Cet athlète existe déjà pour ce pays."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE ATHLETE SET nom_athlete = :nom_athlete, prenom_athlete = :prenom_athlete, id_pays = :id_pays, id_genre = :id_genre WHERE id_athlete = :id_courant");
            $st->execute([':nom_athlete' => $valeurs['nom_athlete'], ':prenom_athlete' => $valeurs['prenom_athlete'], ':id_pays' => $valeurs['id_pays'], ':id_genre' => $valeurs['id_genre'], ':id_courant' => $id]);
            $_SESSION['success'] = "L'athlète a été modifié avec succès.";
            redirect("manage-athletes.php");
        } catch (PDOException $e) {
            error_log("Erreur modification ATHLETE : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un athlète", "../../../");
?>
        <h1>Modifier un athlète</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-athlete.php?id_athlete=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="nom_athlete">Nom :</label>
            <input type="text" name="nom_athlete" id="nom_athlete" maxlength="255" value="<?php echo e($valeurs['nom_athlete']); ?>" required>
            <label for="prenom_athlete">Prénom :</label>
            <input type="text" name="prenom_athlete" id="prenom_athlete" maxlength="255" value="<?php echo e($valeurs['prenom_athlete']); ?>" required>
            <label for="id_pays">Pays :</label>
            <select name="id_pays" id="id_pays" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_id_pays as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_pays'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="id_genre">Genre :</label>
            <select name="id_genre" id="id_genre" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_id_genre as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_genre'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
