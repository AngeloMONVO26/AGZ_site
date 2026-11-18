
<?php
session_start();
require_once "db.php";

$message = "";

if (isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT id, username, password_hash
            FROM admins
            WHERE username = :username
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(["username" => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin["password_hash"])) {
        session_regenerate_id(true);

        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_username"] = $admin["username"];

        header("Location: index.php");
        exit;
    }

    $message = "Identifiant ou mot de passe incorrect.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion administrateur | AGZ Dashboard</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>


<body>

    <main class="login-container">
        <header class="login-header">
            <h1>AGZ Dashboard</h1>
            <p>Votre espace d’administration</p>
        </header>

        <h2>Connexion</h2>
        <p>Connectez-vous pour accéder à votre espace.</p>

        <?php if ($message !== ""): ?>
            <p class="login-error">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form class="login-form" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit">Se connecter</button>
        </form>

        <footer class="login-footer">
            Accès réservé aux administrateurs autorisés.
        </footer>
    </main>

</body>
</html>
