<?php
// Ajout : un administrateur (mot de passe haché avec Bcrypt)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$erreur = "";
$valeurs = ['nom_admin' => '', 'prenom_admin' => '', 'login' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("add-user.php"); // Protection CSRF

    $valeurs['nom_admin'] = post_text('nom_admin');
    $valeurs['prenom_admin'] = post_text('prenom_admin');
    $valeurs['login'] = post_text('login');
    // Les mots de passe ne sont jamais modifiés (ni "trimés") avant le hachage
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    // Validation
    if ($valeurs['nom_admin'] === "" || $valeurs['prenom_admin'] === "" || $valeurs['login'] === "" || $password === "") {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (mb_strlen($valeurs['nom_admin']) > 255 || mb_strlen($valeurs['prenom_admin']) > 255) {
        $erreur = "Le nom et le prénom ne doivent pas dépasser 255 caractères.";
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $valeurs['login'])) {
        $erreur = "Le login doit contenir 3 à 50 caractères (lettres, chiffres, _ . @ -).";
    } elseif (strlen($password) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (strlen($password) > 72) {
        $erreur = "Le mot de passe ne doit pas dépasser 72 caractères (limite de Bcrypt).";
    } elseif ($password !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    }

    // Anti-doublon sur le login
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = :login");
        $st->execute([':login' => $valeurs['login']]);
        if ($st->fetchColumn() > 0) {
            $erreur = "Ce login est déjà utilisé.";
        }
    }

    // Insertion
    if ($erreur === "") {
        try {
            // Hachage Bcrypt du mot de passe : seul le hash est stocké en base
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $st = $connexion->prepare("INSERT INTO ADMINISTRATEUR (nom_admin, prenom_admin, login, password) VALUES (:nom, :prenom, :login, :password)");
            $st->execute([
                ':nom' => $valeurs['nom_admin'],
                ':prenom' => $valeurs['prenom_admin'],
                ':login' => $valeurs['login'],
                ':password' => $hash,
            ]);
            $_SESSION['success'] = "L'administrateur a été ajouté avec succès.";
            redirect("manage-users.php");
        } catch (PDOException $e) {
            error_log("Erreur ajout ADMINISTRATEUR : " . $e->getMessage());
            $erreur = "Erreur de base de données : l'ajout a échoué.";
        }
    }
}

render_header("Ajouter un administrateur", "../../../");
?>
        <h1>Ajouter un administrateur</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="add-user.php" method="post" autocomplete="off">
            <?php echo csrf_field(); ?>
            <label for="nom_admin">Nom :</label>
            <input type="text" name="nom_admin" id="nom_admin" maxlength="255" value="<?php echo e($valeurs['nom_admin']); ?>" required>
            <label for="prenom_admin">Prénom :</label>
            <input type="text" name="prenom_admin" id="prenom_admin" maxlength="255" value="<?php echo e($valeurs['prenom_admin']); ?>" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50" value="<?php echo e($valeurs['login']); ?>" required>
            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            <span class="form-hint">8 caractères minimum</span>
            <label for="password_confirm">Confirmer le mot de passe :</label>
            <input type="password" name="password_confirm" id="password_confirm" minlength="8" maxlength="72" autocomplete="new-password" required>
            <input type="submit" value="Ajouter">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
