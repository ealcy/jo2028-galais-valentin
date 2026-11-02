<?php
// Modification : un administrateur (le mot de passe est optionnel)
require_once("../../../includes/admin.php");
require_admin("../../../"); // Page réservée aux administrateurs connectés

$id = lire_id('id_admin');
if ($id === null) {
    $_SESSION['error'] = "Identifiant invalide.";
    redirect("manage-users.php");
}

try {
    $st = $connexion->prepare("SELECT id_admin, nom_admin, prenom_admin, login FROM ADMINISTRATEUR WHERE id_admin = :id");
    $st->execute([':id' => $id]);
    $ligne = $st->fetch();
} catch (PDOException $e) {
    error_log("Erreur lecture ADMINISTRATEUR : " . $e->getMessage());
    $ligne = false;
}
if (!$ligne) {
    $_SESSION['error'] = "L'administrateur demandé est introuvable.";
    redirect("manage-users.php");
}

$erreur = "";
$valeurs = [
    'nom_admin' => $ligne['nom_admin'],
    'prenom_admin' => $ligne['prenom_admin'],
    'login' => $ligne['login'],
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf("modify-user.php?id_admin=" . $id); // Protection CSRF

    $valeurs['nom_admin'] = post_text('nom_admin');
    $valeurs['prenom_admin'] = post_text('prenom_admin');
    $valeurs['login'] = post_text('login');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    // Validation
    if ($valeurs['nom_admin'] === "" || $valeurs['prenom_admin'] === "" || $valeurs['login'] === "") {
        $erreur = "Le nom, le prénom et le login sont obligatoires.";
    } elseif (mb_strlen($valeurs['nom_admin']) > 255 || mb_strlen($valeurs['prenom_admin']) > 255) {
        $erreur = "Le nom et le prénom ne doivent pas dépasser 255 caractères.";
    } elseif (!preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $valeurs['login'])) {
        $erreur = "Le login doit contenir 3 à 50 caractères (lettres, chiffres, _ . @ -).";
    } elseif ($password !== "" && strlen($password) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (strlen($password) > 72) {
        $erreur = "Le mot de passe ne doit pas dépasser 72 caractères (limite de Bcrypt).";
    } elseif ($password !== $confirmation) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    }

    // Anti-doublon sur le login (on exclut l'administrateur modifié)
    if ($erreur === "") {
        $st = $connexion->prepare("SELECT COUNT(*) FROM ADMINISTRATEUR WHERE login = :login AND id_admin <> :id");
        $st->execute([':login' => $valeurs['login'], ':id' => $id]);
        if ($st->fetchColumn() > 0) {
            $erreur = "Ce login est déjà utilisé.";
        }
    }

    if ($erreur === "") {
        try {
            if ($password !== "") {
                // Nouveau mot de passe : on le hache avec Bcrypt
                $st = $connexion->prepare("UPDATE ADMINISTRATEUR SET nom_admin = :nom, prenom_admin = :prenom, login = :login, password = :password WHERE id_admin = :id");
                $st->execute([
                    ':nom' => $valeurs['nom_admin'],
                    ':prenom' => $valeurs['prenom_admin'],
                    ':login' => $valeurs['login'],
                    ':password' => password_hash($password, PASSWORD_BCRYPT),
                    ':id' => $id,
                ]);
            } else {
                // Mot de passe laissé vide : on conserve l'ancien
                $st = $connexion->prepare("UPDATE ADMINISTRATEUR SET nom_admin = :nom, prenom_admin = :prenom, login = :login WHERE id_admin = :id");
                $st->execute([
                    ':nom' => $valeurs['nom_admin'],
                    ':prenom' => $valeurs['prenom_admin'],
                    ':login' => $valeurs['login'],
                    ':id' => $id,
                ]);
            }

            // Si l'administrateur modifie son propre compte, on met à jour la session
            if ((int) $_SESSION['id_admin'] === $id) {
                $_SESSION['nom_admin'] = $valeurs['nom_admin'];
                $_SESSION['prenom_admin'] = $valeurs['prenom_admin'];
                $_SESSION['login'] = $valeurs['login'];
            }

            $_SESSION['success'] = "L'administrateur a été modifié avec succès.";
            redirect("manage-users.php");
        } catch (PDOException $e) {
            error_log("Erreur modification ADMINISTRATEUR : " . $e->getMessage());
            $erreur = "Erreur de base de données : la modification a échoué.";
        }
    }
}

render_header("Modifier un administrateur", "../../../");
?>
        <h1>Modifier un administrateur</h1>
        <?php if ($erreur !== ""): ?>
            <p class="message message-error"><?php echo e($erreur); ?></p>
        <?php endif; ?>

        <form action="modify-user.php?id_admin=<?php echo (int) $id; ?>" method="post" autocomplete="off">
            <?php echo csrf_field(); ?>
            <label for="nom_admin">Nom :</label>
            <input type="text" name="nom_admin" id="nom_admin" maxlength="255" value="<?php echo e($valeurs['nom_admin']); ?>" required>
            <label for="prenom_admin">Prénom :</label>
            <input type="text" name="prenom_admin" id="prenom_admin" maxlength="255" value="<?php echo e($valeurs['prenom_admin']); ?>" required>
            <label for="login">Login :</label>
            <input type="text" name="login" id="login" maxlength="50" value="<?php echo e($valeurs['login']); ?>" required>
            <label for="password">Nouveau mot de passe :</label>
            <input type="password" name="password" id="password" maxlength="72" autocomplete="new-password">
            <span class="form-hint">Laisser vide pour conserver le mot de passe actuel (8 caractères minimum sinon)</span>
            <label for="password_confirm">Confirmer le nouveau mot de passe :</label>
            <input type="password" name="password_confirm" id="password_confirm" maxlength="72" autocomplete="new-password">
            <input type="submit" value="Enregistrer">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion</a>
        </p>
<?php render_footer("../../../"); ?>
