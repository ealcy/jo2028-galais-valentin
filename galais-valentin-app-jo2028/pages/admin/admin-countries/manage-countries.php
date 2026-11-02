<?php
// Liste et gestion : Pays
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT id_pays, nom_pays FROM PAYS ORDER BY nom_pays")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste PAYS : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Pays", "../../../");
?>
        <h1>Gestion : Pays</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-country.php">Ajouter un pays</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Pays</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_pays']); ?></td>
                        <td class="actions"><a class="btn" href="modify-country.php?id_pays=<?php echo (int) $ligne['id_pays']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-country.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce pays ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_pays" value="<?php echo (int) $ligne['id_pays']; ?>">
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
