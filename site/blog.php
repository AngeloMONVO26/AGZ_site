
<?php
require_once __DIR__ . "/../db.php";

// Récupérer uniquement les articles publiés
$sql = "SELECT
            a.id,
            a.titre,
            a.slug,
            a.extrait,
            a.image_couverture,
            a.date_publication,
            c.nom AS categorie
        FROM articles a
        LEFT JOIN categories c ON c.id = a.categorie_id
        WHERE a.statut = 'publie'
          AND a.date_publication IS NOT NULL
          AND a.date_publication <= NOW()
        ORDER BY a.date_publication DESC
        LIMIT 6";

$sqlCount = "SELECT COUNT(*)
             FROM articles
             WHERE statut = 'publie'
               AND date_publication IS NOT NULL
               AND date_publication <= NOW()";

$totalArticles = (int) $pdo->query($sqlCount)->fetchColumn();
$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES, "UTF-8");
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog | AGZ Solutions</title>
    <meta name="description" content="Conseils, décryptages et idées autour de l'informatique, des logiciels de gestion et de l'automatisation.">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container nav-inner">
            <a href="index.html">
                <img class="logo" src="assets/logo-agz-solutions.png" alt="AGZ Solutions">
            </a>

            <button class="mobile-toggle" type="button" aria-label="Ouvrir le menu">☰</button>

            <nav class="nav-links">
                <a href="index.html">Accueil</a>
                <a href="services.html">Services</a>
                <a href="a-propos.html">À propos</a>
                <a class="active" href="blog.php">Blog</a>
                <a href="contact.html">Contact</a>
                <a class="cta-mini" href="contact.html">Parler à un expert</a>
            </nav>
        </div>
    </header>

    <section class="page-hero">
        <div class="container">
            <div class="eyebrow" style="color: #93c5fd;">Insights</div>
            <h1>Le journal AGZ Solutions.</h1>
            <p>
                Conseils, décryptages et idées autour de l'informatique,
                des logiciels de gestion et de l'automatisation.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="blog-grid">
                <?php if (empty($articles)): ?>
                    <p>Aucun article n'est disponible pour le moment.
                       Revenez bientôt découvrir nos publications.</p>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <article class="post reveal">
                            <div class="post-cover">
                                <?php if (!empty($article["image_couverture"])): ?>
                                    <img
                                        src="../<?= e($article["image_couverture"]) ?>"
                                        alt="<?= e($article["titre"]) ?>"
                                        loading="lazy"
                                        style="width:100%;height:100%;object-fit:cover;"
                                    >
                                <?php endif; ?>
                            </div>

                            <div class="post-body">
                                <div class="post-meta">
                                    <?= e($article["categorie"] ?? "Actualités") ?>
                                    ·
                                    <?= date(
                                        "d/m/Y",
                                        strtotime($article["date_publication"])
                                    ) ?>
                                </div>

                                <h3><?= e($article["titre"]) ?></h3>

                                <p><?= e($article["extrait"] ?? "") ?></p>

                                <a
                                    href="article.php?slug=<?= urlencode($article["slug"]) ?>"
                                    style="color:#2563eb;font-weight:700;"
                                >
                                    Lire l’article →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($totalArticles > 6): ?>
    <div style="text-align:center; margin-top:40px;">
        <a
            href="tous-les-articles.php"
            class="btn btn-primary"
            target="_blank"
            rel="noopener noreferrer"
        >
            Voir tous les articles →
        </a>
    </div>
<?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <img class="logo" src="assets/logo-agz-solutions.png" alt="AGZ Solutions">
                    <p>Votre problème, notre expertise, une solution.</p>
                </div>

                <div>
                    <h4>Navigation</h4>
                    <a href="index.html">Accueil</a>
                    <a href="services.html">Services</a>
                    <a href="a-propos.html">À propos</a>
                    <a href="blog.php">Blog</a>
                </div>

                <div>
                    <h4>Expertises</h4>
                    <a href="services.html#prestation">Prestations informatiques</a>
                    <a href="services.html#developpement">Conception & développement</a>
                    <a href="services.html#gestion">Logiciels de gestion</a>
                    <a href="services.html#automation">Automatisation</a>
                </div>

                <div>
                    <h4>Contact</h4>
                    <a href="tel:+2290197124905">+229 01 97 12 49 05</a>
                    <a href="mailto:agzsolutions.agz@gmail.com">agzsolutions.agz@gmail.com</a>
                    <a href="https://www.agzsolutions.com">www.agzsolutions.com</a>
                    <span>📍 Cotonou, Bénin</span>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© <span data-year></span> AGZ Solutions. Tous droits réservés.</span>
                <span>Informatique · Solutions · Automatisation</span>
            </div>
        </div>
    </footer>

    <script src="assets/script.js"></script>
</body>
</html>
