<?php
// Ajout : un résultat (table PARTICIPER : un athlète participe à une épreuve)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['id_athlete' => '', 'id_epreuve' => '', 'resultat' => ''];

// Listes déroulantes
$options_athlete = $connexion->query("SELECT a.id_athlete AS id, CONCAT(a.nom_athlete, ' ', a.prenom_athlete, ' (', p.nom_pays, ')') AS label FROM ATHLETE a INNER JOIN PAYS p ON a.id_pays = p.id_pays ORDER BY a.nom_athlete, a.prenom_athlete")->fetchAll();
$options_epreuve = $connexion->query("SELECT ep.id_epreuve AS id, CONCAT(s.nom_sport, ' - ', DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y'), ' ', DATE_FORMAT(ep.heure_epreuve, '%H:%i'), ' (', l.nom_lieu, ')') AS label FROM EPREUVE ep INNER JOIN SPORT s ON ep.id_sport = s.id_sport INNER JOIN LIEU l ON ep.id_lieu = l.id_lieu ORDER BY ep.date_epreuve, ep.heure_epreuve")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-result.php"); // Protection CSRF

    $valeurs['id_athlete'] = post_text('id_athlete');
    $valeurs['id_epreuve'] = post_text('id_epreuve');
    $valeurs['resultat'] = post_text('resultat');

    // Validation
    if ($valeurs['id_athlete'] === "" || $valeurs['id_epreuve'] === "" || $valeurs['resultat'] === "") {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!est_option_valide($valeurs['id_athlete'], $options_athlete)) {
        $erreur = "L'athlète choisi est invalide.";
    } elseif (!est_option_valide($valeurs['id_epreuve'], $options_epreuve)) {
        $erreur = "L'épreuve choisie est invalide.";
    } elseif (mb_strlen($valeurs['resultat']) > 100) {
        $erreur = "Le résultat ne doit pas dépasser 100 caractères.";
    }

    // Anti-doublon : un athlète ne peut avoir qu'un seul résultat par épreuve (clé primaire composée)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve");
        $st->execute([':id_athlete' => $valeurs['id_athlete'], ':id_epreuve' => $valeurs['id_epreuve']]);
        if ($st->fetchColumn() > 0) {
            $erreur = "Cet athlète a déjà un résultat pour cette épreuve (utilisez Modifier).";
        }
    }

    if ($erreur === "") {
        try {
            $st = $connexion->prepare("INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:id_athlete, :id_epreuve, :resultat)");
            $st->execute([':id_athlete' => $valeurs['id_athlete'], ':id_epreuve' => $valeurs['id_epreuve'], ':resultat' => $valeurs['resultat']]);
            $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
            redirect("manage-results.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout PARTICIPER : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un résultat", "../../../");
?>
        <h1>Ajouter un résultat</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-result.php" method="post">
            <?php echo csrf_field(); ?>
            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_athlete as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_athlete'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($options_epreuve as $option): ?>
                    <option value="<?php echo e($option['id']); ?>"<?php echo ((string) $valeurs['id_epreuve'] === (string) $option['id']) ? ' selected' : ''; ?>><?php echo e($option['label']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" maxlength="100" value="<?php echo e($valeurs['resultat']); ?>" required>
            <span class="form-hint">Ex. : 10.5, 2:05.3, Vainqueur...</span>
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
