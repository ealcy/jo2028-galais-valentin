<?php
// Liste et gestion : Lieux
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT id_lieu, nom_lieu, adresse_lieu, cp_lieu, ville_lieu FROM LIEU ORDER BY nom_lieu")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste LIEU : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Lieux", "../../../");
?>
        <h1>Gestion : Lieux</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-place.php">Ajouter un lieu</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Lieu</th><th>Adresse</th><th>Code postal</th><th>Ville</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_lieu']); ?></td>
                        <td><?php echo e($ligne['adresse_lieu']); ?></td>
                        <td><?php echo e($ligne['cp_lieu']); ?></td>
                        <td><?php echo e($ligne['ville_lieu']); ?></td>
                        <td class="actions"><a class="btn" href="modify-place.php?id_lieu=<?php echo (int) $ligne['id_lieu']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-place.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce lieu ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_lieu" value="<?php echo (int) $ligne['id_lieu']; ?>">
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
