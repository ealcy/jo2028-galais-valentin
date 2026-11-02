<?php
// Liste et gestion : Calendrier des épreuves
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT ep.id_epreuve, sp.nom_sport, DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y') AS date_fr, DATE_FORMAT(ep.heure_epreuve, '%H:%i') AS heure_fr, li.nom_lieu FROM EPREUVE ep INNER JOIN SPORT sp ON ep.id_sport = sp.id_sport INNER JOIN LIEU li ON ep.id_lieu = li.id_lieu ORDER BY ep.date_epreuve, ep.heure_epreuve")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste EPREUVE : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Calendrier des épreuves", "../../../");
?>
        <h1>Gestion : Calendrier des épreuves</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-event.php">Ajouter une épreuve</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Sport</th><th>Date</th><th>Heure</th><th>Lieu</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_sport']); ?></td>
                        <td><?php echo e($ligne['date_fr']); ?></td>
                        <td><?php echo e($ligne['heure_fr']); ?></td>
                        <td><?php echo e($ligne['nom_lieu']); ?></td>
                        <td class="actions"><a class="btn" href="modify-event.php?id_epreuve=<?php echo (int) $ligne['id_epreuve']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-event.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer cette épreuve ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_epreuve" value="<?php echo (int) $ligne['id_epreuve']; ?>">
                                <button type="submit" class="btn btn-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucune donnée trouvée.</p>
        <?php endif; ?>

        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
<?php render_footer("../../../"); ?>
