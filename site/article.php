
<?php
require_once __DIR__ . "/../db.php";


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];



function e(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES, "UTF-8");
}

$slug = $_GET["slug"] ?? "";

if (!is_string($slug) || trim($slug) === "") {
    http_response_code(404);
    exit("Article introuvable.");
}

$sql = "SELECT
            a.id,
            a.titre,
            a.slug,
            a.extrait,
            a.contenu,
            a.image_couverture,
            a.date_publication,
            c.nom AS categorie
        FROM articles a
        LEFT JOIN categories c ON c.id = a.categorie_id
        WHERE a.slug = :slug
          AND a.statut = 'publie'
          AND a.date_publication IS NOT NULL
          AND a.date_publication <= NOW()
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(["slug" => $slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    http_response_code(404);
    exit("Article introuvable ou non publié.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfPost = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrfPost)
        || !hash_equals($_SESSION["csrf_token"], $csrfPost)
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $nom = trim($_POST["nom"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contenu = trim($_POST["contenu"] ?? "");

    if (
        !is_string($nom)
        || !is_string($email)
        || !is_string($contenu)
        || $nom === ""
        || mb_strlen($nom) > 150
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($email) > 255
        || $contenu === ""
        || mb_strlen($contenu) > 3000
    ) {
        http_response_code(400);
        exit("Veuillez vérifier les informations saisies.");
    }

    $stmtInsertion = $pdo->prepare(
        "INSERT INTO commentaires
            (article_id, nom, email, contenu, statut)
         VALUES
            (:article_id, :nom, :email, :contenu, 'approuve')"
    );

    $stmtInsertion->execute([
        "article_id" => (int) $article["id"],
        "nom" => $nom,
        "email" => $email,
        "contenu" => $contenu
    ]);

    header(
        "Location: article.php?slug="
        . rawurlencode($article["slug"])
        . "#comments-content",
        true,
        303
    );
    exit;
}

$datePublication = date(
    "d/m/Y à H:i",
    strtotime($article["date_publication"])
);
?>

<?php
// Les commentaires approuvés sont chargés depuis la base de données.
$stmtCommentaires = $pdo->prepare(
    "SELECT nom, contenu, date_commentaire
     FROM commentaires
     WHERE article_id = :article_id
       AND statut = 'approuve'
     ORDER BY date_commentaire DESC"
);

$stmtCommentaires->execute([
    "article_id" => (int) $article["id"]
]);

$commentaires = $stmtCommentaires->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($article["extrait"] ?: $article["titre"]) ?>">
    <title><?= e($article["titre"]) ?> | AGZ Solutions</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="navbar">
    <div class="container nav-inner">
        <a href="index.html" class="logo">AGZ Solutions</a>

        <nav class="nav-links" aria-label="Navigation principale">
            <a href="index.html">Accueil</a>
            <a href="services.html">Services</a>
            <a href="a-propos.html">À propos</a>
            <a href="blog.php" class="active">Blog</a>
            <a href="contact.html" class="cta-mini">Contact</a>
        </nav>
    </div>
</header>

<main>
    <section class="article-hero">
        <div class="container article-hero-inner">

            <a href="blog.php" class="article-back">
                &larr; Retour au blog
            </a>

            <?php if (!empty($article["categorie"])): ?>
                <span class="article-category">
                    <?= e($article["categorie"]) ?>
                </span>
            <?php endif; ?>

            <h1><?= e($article["titre"]) ?></h1>

            <p class="article-publication">
                <span aria-hidden="true">◷</span>
                Publié le <?= e($datePublication) ?>
            </p>

        </div>
    </section>

    <section class="article-section">
        <div class="container article-container">

            <?php if (!empty($article["image_couverture"])): ?>
                <figure class="article-cover">
                    <img
                        src="../<?= e($article["image_couverture"]) ?>"
                        alt="<?= e($article["titre"]) ?>"
                    >
                </figure>
            <?php endif; ?>

            <div class="article-body">

                <?php if (!empty($article["extrait"])): ?>
                    <p class="article-excerpt">
                        <?= e($article["extrait"]) ?>
                    </p>
                <?php endif; ?>

                <div class="article-content">
                    <?= nl2br(e($article["contenu"])) ?>
                </div>


<div class="article-end">
    <span>AGZ Solutions</span>
    <p>Votre problème, notre expertise, une solution.</p>
</div>

<div class="article-actions">
    <a href="blog.php" class="btn btn-primary">
        Lire d'autres articles
    </a>

    <button
        type="button"
        class="btn btn-primary"
        id="open-comment-modal"
    >
        Ajouter un commentaire
    </button>

    <button
        type="button"
        class="btn btn-comments"
        id="toggle-comments"
        aria-expanded="false"
        aria-controls="comments-content"
    >
        Lire les commentaires (<?= count($commentaires) ?>)
    </button>
</div>



<section class="comments-section" id="comments-content" hidden>
    <h2>Commentaires (<?= count($commentaires) ?>)</h2>

    <p class="comments-intro">
        Découvrez les avis et expériences partagés par notre communauté.
    </p>

    <?php if (!empty($commentaires)): ?>
        <div class="comments-list">
            <?php foreach ($commentaires as $commentaire): ?>
                <article class="comment-card">
                    <div class="comment-header">
                        <strong><?= e($commentaire["nom"]) ?></strong>
                        <time>
                            <?= e(date(
                                "d/m/Y à H:i",
                                strtotime($commentaire["date_commentaire"])
                            )) ?>
                        </time>
                    </div>

                    <p><?= nl2br(e($commentaire["contenu"])) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="comments-empty">
            Aucun commentaire pour le moment. Soyez le premier à réagir !
        </p>
    <?php endif; ?>
</section>

<div class="comment-modal" id="comment-modal" hidden>
    <div
        class="comment-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="comment-modal-title"
    >
        <button
            type="button"
            class="comment-modal-close"
            id="close-comment-modal"
            aria-label="Fermer"
        >
            &times;
        </button>

        <h2 id="comment-modal-title">Ajouter un commentaire</h2>

        <form class="comment-form" id="comment-form" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"
>
            <div class="comment-form-field">
                <label for="comment-nom">Votre nom</label>
                <input
                    type="text"
                    id="comment-nom"
                    name="nom"
                    maxlength="100"
                    required
                >
            </div>

            <div class="comment-form-field">
                <label for="comment-email">Votre adresse e-mail</label>
                <input
                    type="email"
                    id="comment-email"
                    name="email"
                    maxlength="190"
                    required
                >
            </div>

            <div class="comment-form-field">
                <label for="comment-contenu">Votre commentaire</label>
                <textarea
                    id="comment-contenu"
                    name="contenu"
                    rows="5"
                    maxlength="3000"
                    required
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Envoyer mon commentaire
            </button>

            <p class="comment-notice">
                Votre commentaire sera publié immédiatement.
            </p>
        </form>
    </div>
</div>


                </div>

            </div>
        </div>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <div class="footer-bottom">
            <p>&copy; <?= date("Y") ?> AGZ Solutions. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script src="assets/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("comment-modal");
    const openModal = document.getElementById("open-comment-modal");
    const closeModal = document.getElementById("close-comment-modal");
    const form = document.getElementById("comment-form");
    const commentsButton = document.getElementById("toggle-comments");
    const commentsContent = document.getElementById("comments-content");

    if (modal && openModal && closeModal) {
        openModal.addEventListener("click", function () {
            modal.hidden = false;
            document.body.style.overflow = "hidden";
            document.getElementById("comment-nom").focus();
        });

        function closeCommentModal() {
            modal.hidden = true;
            document.body.style.overflow = "";
            openModal.focus();
        }

        closeModal.addEventListener("click", closeCommentModal);

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeCommentModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && !modal.hidden) {
                closeCommentModal();
            }
        });
    }

    if (commentsButton && commentsContent) {
        commentsButton.addEventListener("click", function () {
            const isOpen = !commentsContent.hidden;

            commentsContent.hidden = isOpen;
            commentsButton.setAttribute("aria-expanded", String(!isOpen));
            commentsButton.textContent = isOpen
                ? "Lire les commentaires (" + <?= count($commentaires) ?> + ")"
                : "Masquer les commentaires (" + <?= count($commentaires) ?> + ")";

            if (!isOpen) {
                commentsContent.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }
        });
    }
});
</script>

</body>
</html>
