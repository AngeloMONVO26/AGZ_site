<?php
require_once __DIR__ . "/../db.php";

// Récupérer le terme de recherche
$recherche = $_GET["q"] ?? "";

if (!is_string($recherche)) {
    $recherche = "";
} else {
    $recherche = trim($recherche);
}

// Récupérer les articles publiés et visibles
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
        WHERE a.statut = 'publie'
          AND a.date_publication IS NOT NULL
          AND a.date_publication <= NOW()";

$params = [];

if ($recherche !== "") {
    $sql .= " AND (
        a.titre LIKE :recherche_titre
        OR a.extrait LIKE :recherche_extrait
        OR a.contenu LIKE :recherche_contenu
    )";

    $terme = "%" . $recherche . "%";

    $params = [
        "recherche_titre" => $terme,
        "recherche_extrait" => $terme,
        "recherche_contenu" => $terme
    ];
}

$sql .= " ORDER BY a.date_publication DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e(string $valeur): string
{
    return htmlspecialchars($valeur, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tous les articles | AGZ Solutions</title>

    <meta name="description" content="Retrouvez toutes les publications et actualités d'AGZ Solutions.">

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

<main>
    <section class="page-hero">
        <div class="container">
            <div class="eyebrow" style="color: #93c5fd;">Archives</div>

            <h1>Toutes nos publications.</h1>

            <p>
                Retrouvez nos conseils, analyses et actualités
                autour de l'informatique et des solutions numériques.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">

            <form method="GET" action="tous-les-articles.php"
                  style="max-width:650px; margin:0 auto 40px;">

                <label for="recherche-articles"
                       style="display:block; margin-bottom:10px; font-weight:700;">
                    Rechercher un article
                </label>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input
                        type="search"
                        id="recherche-articles"
                        name="q"
                        value="<?= e($recherche) ?>"
                        placeholder="Titre, sujet ou mot-clé..."
                        style="flex:1; min-width:220px;"
                    >

                    <button type="submit" class="btn btn-primary">
                        Rechercher
                    </button>
                </div>

                <?php if ($recherche !== ""): ?>
                    <p style="margin-top:12px;">
                        Résultats pour :
                        <strong><?= e($recherche) ?></strong>
                        ·
                        <a href="tous-les-articles.php">Effacer la recherche</a>
                    </p>
                <?php endif; ?>

            </form>

                        <div class="blog-grid">

                <?php if (empty($articles)): ?>

                    <div style="grid-column:1 / -1; text-align:center; padding:40px 20px;">
                        <?php if ($recherche !== ""): ?>
                            <h3>Aucun article trouvé</h3>
                            <p>
                                Aucun article ne correspond à votre recherche.
                                Essayez avec d'autres mots-clés.
                            </p>
                            <a href="tous-les-articles.php" class="btn btn-primary">
                                Voir tous les articles
                            </a>
                        <?php else: ?>
                            <h3>Aucun article disponible</h3>
                            <p>
                                Nos publications seront bientôt disponibles.
                                Revenez nous voir !
                            </p>
                        <?php endif; ?>
                    </div>

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
    </section>
</main>

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

            </div>