<?php
// Liste et gestion : Athlètes
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT a.id_athlete, a.nom_athlete, a.prenom_athlete, p.nom_pays, g.nom_genre FROM ATHLETE a INNER JOIN PAYS p ON a.id_pays = p.id_pays INNER JOIN GENRE g ON a.id_genre = g.id_genre ORDER BY a.nom_athlete, a.prenom_athlete")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste ATHLETE : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Athlètes", "../../../");
?>
        <h1>Gestion : Athlètes</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-athlete.php">Ajouter un athlète</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Nom</th><th>Prénom</th><th>Pays</th><th>Genre</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_athlete']); ?></td>
                        <td><?php echo e($ligne['prenom_athlete']); ?></td>
                        <td><?php echo e($ligne['nom_pays']); ?></td>
                        <td><?php echo e($ligne['nom_genre']); ?></td>
                        <td class="actions"><a class="btn" href="modify-athlete.php?id_athlete=<?php echo (int) $ligne['id_athlete']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-athlete.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer cet athlète ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_athlete" value="<?php echo (int) $ligne['id_athlete']; ?>">
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
