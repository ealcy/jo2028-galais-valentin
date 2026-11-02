<?php
// Liste et gestion : Sports
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste SPORT : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Sports", "../../../");
?>
        <h1>Gestion : Sports</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-sport.php">Ajouter un sport</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Sport</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_sport']); ?></td>
                        <td class="actions"><a class="btn" href="modify-sport.php?id_sport=<?php echo (int) $ligne['id_sport']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-sport.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce sport ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_sport" value="<?php echo (int) $ligne['id_sport']; ?>">
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
