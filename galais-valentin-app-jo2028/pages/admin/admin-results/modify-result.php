<?php
// Modification : un résultat (clé composée id_athlete + id_epreuve reçue dans l'URL)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$ancien_athlete = lire_id('id_athlete');
$ancienne_epreuve = lire_id('id_epreuve');
if ($ancien_athlete === null || $ancienne_epreuve === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-results.php");
}

try {
    $st = $connexion->prepare("SELECT id_athlete, id_epreuve, resultat FROM PARTICIPER WHERE id_athlete = :a AND id_epreuve = :e");
    $st->execute([':a' => $ancien_athlete, ':e' => $ancienne_epreuve]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture PARTICIPER : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "Le résultat demandé est introuvable.";
    redirect("manage-results.php");
}

$erreur = "";
$valeurs = [
    'id_athlete' => (string) $ligne['id_athlete'],
    'id_epreuve' => (string) $ligne['id_epreuve'],
    'resultat' => (string) $ligne['resultat'],
];

// Listes déroulantes
$options_athlete = $connexion->query("SELECT a.id_athlete AS id, CONCAT(a.nom_athlete, ' ', a.prenom_athlete, ' (', p.nom_pays, ')') AS label FROM ATHLETE a INNER JOIN PAYS p ON a.id_pays = p.id_pays ORDER BY a.nom_athlete, a.prenom_athlete")->fetchAll();
$options_epreuve = $connexion->query("SELECT ep.id_epreuve AS id, CONCAT(s.nom_sport, ' - ', DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y'), ' ', DATE_FORMAT(ep.heure_epreuve, '%H:%i'), ' (', l.nom_lieu, ')') AS label FROM EPREUVE ep INNER JOIN SPORT s ON ep.id_sport = s.id_sport INNER JOIN LIEU l ON ep.id_lieu = l.id_lieu ORDER BY ep.date_epreuve, ep.heure_epreuve")->fetchAll();

$retour = "modify-result.php?id_athlete=" . $ancien_athlete . "&id_epreuve=" . $ancienne_epreuve;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf($retour); // Protection CSRF

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

    // Anti-doublon : si on change l'athlète ou l'épreuve, le couple ne doit pas déjà exister
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM PARTICIPER WHERE id_athlete = :id_athlete AND id_epreuve = :id_epreuve AND NOT (id_athlete = :ancien_a AND id_epreuve = :ancienne_e)");
        $st->execute([
            ':id_athlete' => $valeurs['id_athlete'],
            ':id_epreuve' => $valeurs['id_epreuve'],
            ':ancien_a' => $ancien_athlete,
            ':ancienne_e' => $ancienne_epreuve,
        ]);
        if ($st->fetchColumn() > 0) {
            $erreur = "Cet athlète a déjà un résultat pour cette épreuve.";
        }
    }

    if ($erreur === "") {
        try {
            $st = $connexion->prepare("UPDATE PARTICIPER SET id_athlete = :id_athlete, id_epreuve = :id_epreuve, resultat = :resultat WHERE id_athlete = :ancien_a AND id_epreuve = :ancienne_e");
            $st->execute([
                ':id_athlete' => $valeurs['id_athlete'],
                ':id_epreuve' => $valeurs['id_epreuve'],
                ':resultat' => $valeurs['resultat'],
                ':ancien_a' => $ancien_athlete,
                ':ancienne_e' => $ancienne_epreuve,
            ]);
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            redirect("manage-results.php");
        } catch (PDOException $e) {
            error_log("Erreur modification PARTICIPER : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un résultat", "../../../");
?>
        <h1>Modifier un résultat</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-result.php?id_athlete=<?php echo (int) $ancien_athlete; ?>&amp;id_epreuve=<?php echo (int) $ancienne_epreuve; ?>" method="post">
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
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
