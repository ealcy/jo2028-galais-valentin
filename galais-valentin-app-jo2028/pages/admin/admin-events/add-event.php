<?php
// Ajout : une épreuve
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['id_sport' => '', 'date_epreuve' => '', 'heure_epreuve' => '', 'id_lieu' => ''];

// Listes déroulantes
$options_id_sport = $connexion->query("SELECT id_sport AS id, nom_sport AS label FROM SPORT ORDER BY nom_sport")->fetchAll();
$options_id_lieu = $connexion->query("SELECT id_lieu AS id, nom_lieu AS label FROM LIEU ORDER BY nom_lieu")->fetchAll();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-event.php"); // Protection CSRF

    // Récupération des champs (sans modifier leur contenu : l'échappement XSS se fait à l'affichage)
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

    // Anti-doublon
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE id_sport = :id_sport AND date_epreuve = :date_epreuve AND heure_epreuve = :heure_epreuve AND id_lieu = :id_lieu");
        $st->execute([':id_sport' => $valeurs['id_sport'], ':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu']]);
        if ($st->fetchColumn() > 0) { $erreur = "Cette épreuve existe déjà dans le calendrier."; }
    }
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM EPREUVE WHERE date_epreuve = :date_epreuve AND heure_epreuve = :heure_epreuve AND id_lieu = :id_lieu");
        $st->execute([':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu']]);
        if ($st->fetchColumn() > 0) { $erreur = "Ce lieu accueille déjà une épreuve à cette date et à cette heure."; }
    }

    // Insertion (requête préparée : protection contre les injections SQL)
    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO EPREUVE (id_sport, date_epreuve, heure_epreuve, id_lieu) VALUES (:id_sport, :date_epreuve, :heure_epreuve, :id_lieu)");
            $st->execute([':id_sport' => $valeurs['id_sport'], ':date_epreuve' => $valeurs['date_epreuve'], ':heure_epreuve' => $valeurs['heure_epreuve'], ':id_lieu' => $valeurs['id_lieu']]);
            $_SESSION['success'] = "L'épreuve a été ajoutée avec succès.";
            redirect("manage-events.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout EPREUVE : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter une épreuve", "../../../");
?>
        <h1>Ajouter une épreuve</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-event.php" method="post">
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
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
