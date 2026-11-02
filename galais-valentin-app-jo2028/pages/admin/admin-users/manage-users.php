<?php
// Liste et gestion : Administrateurs
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

try {
    // Le mot de passe (hash) n'est volontairement jamais sélectionné ni affiché
    $lignes = $connexion->query("SELECT id_admin, nom_admin, prenom_admin, login FROM ADMINISTRATEUR ORDER BY nom_admin, prenom_admin")->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur liste ADMINISTRATEUR : " . $e->getMessage());
    $lignes = [];
    $_SESSION['error'] = "Erreur : impossible de récupérer les données.";
}

render_header("Administrateurs", "../../../");
?>
        <h1>Gestion : Administrateurs</h1>
        <?php show_flash(); ?>

        <p class="action-buttons">
            <a class="btn" href="add-user.php">Ajouter un administrateur</a>
        </p>

        <?php if (count($lignes) > 0): ?>
            <table>
                <thead>
                    <tr><th>Nom</th><th>Prénom</th><th>Login</th><th>Modifier</th><th>Supprimer</th></tr>
                </thead>
                <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <td><?php echo e($ligne['nom_admin']); ?></td>
                        <td><?php echo e($ligne['prenom_admin']); ?></td>
                        <td><?php echo e($ligne['login']); ?></td>
                        <td class="actions"><a class="btn" href="modify-user.php?id_admin=<?php echo (int) $ligne['id_admin']; ?>">Modifier</a></td>
                        <td class="actions">
                            <form class="inline-form" action="delete-user.php" method="post" onsubmit="return confirm('Voulez-vous vraiment supprimer cet administrateur ?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_admin" value="<?php echo (int) $ligne['id_admin']; ?>">
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
