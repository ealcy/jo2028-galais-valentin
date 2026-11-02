<?php
// Modification : une épreuve
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

// Identifiant reçu dans l'URL (validé comme entier positif)
$id = lire_id('id_epreuve');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-events.php");
}

// Récupération de la ligne à modifier
try {
    $st = $connexion->prepare("SELECT * FROM EPREUVE WHERE id_epreuve = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture EPREUVE : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "L'épreuve demandée est introuvable.";
    redirect("manage-events.php");
}

$erreur = "";
$valeurs = [
    'id_sport' => (string) $ligne['id_sport'],
    'date_epreuve' => (string) $ligne['date_epreuve'],
    'heure_epreuve' => substr((string) $ligne['heure_epreuve'], 0, 5),
    'id_lieu' => (string) $ligne['id_lieu'],
];

// Listes déroulantes
$options_id_sport = $connexion->query("SELECT id_sport AS id, nom_sport AS label FROM SPORT ORDER BY nom_sport")->fetchAll();
$options_id_lieu = $connexion->query("SELECT id_lieu AS id, nom_lieu AS label FROM LIEU ORDER BY nom_lieu")->fetchAll();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-event.php?id_epreuve=" . $id); // Protection CSRF

    $valeurs['id_sport'] = post_text('id_sport');
    $valeurs['date_epreuve'] = post_text('date_epreuve');
    $valeurs['heure_epreuve'] = normaliser_heure(post_text('heure_epreuve'));
    $valeurs['id_lieu'] = post_text('id_lieu');

    // Validation
    if ($erreur === "" && $valeurs['id_sport'] === "") { $erreur = "Le champ « Sport » est obligatoire."; }
    if ($erreur === "" && !est_option_valide($valeurs['id_sport'], $options_id_sport)) { $erreur = "Le champ « Sport » contient une valeur invalide."; }
    if ($erreur === "" && $valeurs['date_epreuve'] === "") { $erreur = "Le champ « Date » est obligatoire."; }
    if ($erreur === "" && !est_date_valide($valeurs['date_epreuve'])) { $erreur = "La date est invalide."; }
    if ($erreur === "" && $valeurs['heure_epreuve'] === "") { $erreur = "Le champ « Heure » est obligatoire."; }
    if ($erreur === "" && $valeurs['id_lieu'] === "") { $erreur = "Le champ « Lieu » est obligatoire."; }
    if ($erreur === "" && !est_option_valide($valeurs['id_lieu'], $options_id_lieu)) { $erreur = "Le champ « Lieu » contient une valeur invalide."; }

    // Anti-doublon (on exclut la ligne en cours de modification)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE id_sport = :id_sport AND date_epreuve = :date_epreuve AND heure_epreuve = :heure_epreuve AND id_lieu = :id_lieu AND id_epreuve <> :id_courant");
        $st->execute([':id_sport' => $valeurs['id_sport'], ':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Cette épreuve existe déjà dans le calendrier."; }
    }
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE date_epreuve = :date_epreuve AND heure_epreuve = :heure_epreuve AND id_lieu = :id_lieu AND id_epreuve <> :id_courant");
        $st->execute([':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu'], ':id_courant' => $id]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce lieu accueille déjà une épreuve à cette date et à cette heure."; }
    }

    // Mise à jour (requête préparée)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE EPREUVE SET id_sport = :id_sport, date_epreuve = :date_epreuve, heure_epreuve = :heure_epreuve, id_lieu = :id_lieu WHERE id_epreuve = :id_courant");
            $st->execute([':id_sport' => $valeurs['id_sport'], ':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu'], ':id_courant' => $id]);
            $_SESSION['success'] = "L'épreuve a été modifiée avec succès.";
            redirect("manage-events.php");
        } catch (PDOException $e) {
            error_log("Erreur modification EPREUVE : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier une épreuve", "../../../");
?>
        <h1>Modifier une épreuve</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-event.php?id_epreuve=<?php echo (int) $id; ?>" method="post">
            <?php echo csrf_field(); ?>
            <label for="id_sport">Sport :</label>
            <select name="id_sport" id="id_sport" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_id_sport as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_sport'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="date_epreuve">Date :</label>
            <input type="date" name="date_epreuve" id="date_epreuve" value="<?php echo e($valeurs['date_epreuve']); ?>" required>
            <label for="heure_epreuve">Heure :</label>
            <input type="time" name="heure_epreuve" id="heure_epreuve" value="<?php echo e($valeurs['heure_epreuve']); ?>" required>
            <label for="id_lieu">Lieu :</label>
            <select name="id_lieu" id="id_lieu" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_id_lieu as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_lieu'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
