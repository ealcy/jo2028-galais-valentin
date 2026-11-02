<?php
require_once("../../includes/admin.php");
require_admin("../../"); // Page réservée aux administrateurs connectés

// Informations de l'administrateur connecté
$prenom_utilisateur = $_SESSION['prenom_admin'];
$nom_utilisateur = $_SESSION['nom_admin'];

render_header("Administration", "../../");
?>
        <p class="info-login">
            Bonjour, <?php echo e($prenom_utilisateur . " " . $nom_utilisateur); ?>
        </p>
        <?php show_flash(); ?>

        <p class="category-site">
            <a class="link-category" href="./admin-users/manage-users.php">Gestion Administrateurs</a>
            <a class="link-category" href="./admin-sports/manage-sports.php">Gestion Sports</a>
            <a class="link-category" href="./admin-places/manage-places.php">Gestion Lieux</a>
            <a class="link-category" href="./admin-countries/manage-countries.php">Gestion Pays</a>
            <a class="link-category" href="./admin-genres/manage-genres.php">Gestion Genres</a>
            <a class="link-category" href="./admin-events/manage-events.php">Gestion Calendrier</a>
            <a class="link-category" href="./admin-athletes/manage-athletes.php">Gestion Athlètes</a>
            <a class="link-category" href="./admin-results/manage-results.php">Gestion Résultats</a>
        </p>
<?php render_footer("../../"); ?>
