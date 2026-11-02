<?php
// Liste et gestion : Genres
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    $lignes = $connexion->query("SELECT id_genre, nom_genre FROM GENRE ORDER BY nom_genre")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste GENRE : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Genres", "../../../");
?>
        <h1>Gestion : Genres</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-genre.php">Ajouter un genre</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Genre</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_genre']); ?></td>
                        <td class="actions"><a class="btn" href="modify-genre.php?id_genre=<?php echo (int) $ligne['id_genre']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-genre.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer ce genre ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_genre" value="<?php echo (int) $ligne['id_genre']; ?>">
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
