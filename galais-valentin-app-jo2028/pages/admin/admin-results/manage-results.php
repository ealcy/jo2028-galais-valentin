<?php
// Liste et gestion : Résultats
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query(
        "SELECT pa.id_athlete, pa.id_epreuve, pa.resultat, a.nom_athlete, a.prenom_athlete, p.nom_pays,
                s.nom_sport, DATE_FORMAT(ep.date_epreuve, '%d/%m/%Y') AS date_fr
         FROM PARTICIPER pa
         INNER JOIN ATHLETE a ON pa.id_athlete = a.id_athlete
         INNER JOIN PAYS p ON a.id_pays = p.id_pays
         INNER JOIN EPREUVE ep ON pa.id_epreuve = ep.id_epreuve
         INNER JOIN SPORT s ON ep.id_sport = s.id_sport
         ORDER BY ep.date_epreuve, s.nom_sport, a.nom_athlete"
    )->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste PARTICIPER : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Résultats", "../../../");
?>
        <h1>Gestion : Résultats</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-result.php">Ajouter un résultat</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Sport</th><th>Date</th><th>Athlète</th><th>Pays</th><th>Résultat</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_sport']); ?></td>
                        <td><?php echo e($ligne['date_fr']); ?></td>
                        <td><?php echo e($ligne['nom_athlete'] . ' ' . $ligne['prenom_athlete']); ?></td>
                        <td><?php echo e($ligne['nom_pays']); ?></td>
                        <td><?php echo e($ligne['resultat']); ?></td>
                        <td class="actions"><a class="btn" href="modify-result.php?id_athlete=<?php echo (int) $ligne['id_athlete']; ?>&amp;id_epreuve=<?php echo (int) $ligne['id_epreuve']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-result.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce résultat ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_athlete" value="<?php echo (int) $ligne['id_athlete']; ?>">
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
