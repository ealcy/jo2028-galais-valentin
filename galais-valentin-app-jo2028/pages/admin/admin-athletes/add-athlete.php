<?php
// Ajout : un athlète
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_athlete' => '', 'prenom_athlete' => '', 'id_pays' => '', 'id_genre' => ''];

// Listes déroulantes
$options_id_pays = $connexion->query("SELECT id_pays AS id, nom_pays AS label FROM PAYS ORDER BY nom_pays")->fetchAll();
$options_id_genre = $connexion->query("SELECT id_genre AS id, nom_genre AS label FROM GENRE ORDER BY nom_genre")->fetchAll();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-athlete.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
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

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM ATHLETE WHERE nom_athlete = :nom_athlete AND prenom_athlete = :prenom_athlete AND id_pays = :id_pays");
        $st->execute([':nom_athlete' => $valeurs['nom_athlete'], ':prenom_athlete' => $valeurs['prenom_athlete'], ':id_pays' => $valeurs['id_pays']]);
        if ($st->fetchColumn() > 0) { $erreur = "Cet athlète existe déjà pour ce pays."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO ATHLETE (nom_athlete, prenom_athlete, id_pays, id_genre) VALUES (:nom_athlete, :prenom_athlete, :id_pays, :id_genre)");
            $st->execute([':nom_athlete' => $valeurs['nom_athlete'], ':prenom_athlete' => $valeurs['prenom_athlete'], ':id_pays' => $valeurs['id_pays'], ':id_genre' => $valeurs['id_genre']]);
            $_SESSION['success'] = "L'athlète a été ajouté avec succès.";
            redirect("manage-athletes.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout ATHLETE : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un athlète", "../../../");
?>
        <h1>Ajouter un athlète</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-athlete.php" method="post">
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
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
