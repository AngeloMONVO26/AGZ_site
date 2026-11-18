
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

// Vérification de l'identifiant de l'article
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: ges_articles.php?erreur=article_invalide");
    exit;
}

// Récupération de l'article et de sa catégorie
$stmt = $pdo->prepare(
    "SELECT a.*, c.nom AS categorie_nom
     FROM articles a
     LEFT JOIN categories c ON c.id = a.categorie_id
     WHERE a.id = :id
     LIMIT 1"
);

$stmt->execute(["id" => $id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: ges_articles.php?erreur=article_introuvable");
    exit;
}

// Protection de l'affichage HTML
function e($valeur): string
{
    return htmlspecialchars(
        (string) ($valeur ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Préparation des données
$titre = $article["titre"];
$extrait = $article["extrait"] ?? "";
$contenu = $article["contenu"] ?? "";
$image = $article["image_couverture"] ?? "";
$categorie = $article["categorie_nom"] ?? "Sans catégorie";
$statut = $article["statut"] ?? "brouillon";
$datePublication = $article["date_publication"] ?? null;
$dateCreation = $article["created_at"] ?? null;
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu de l'article | AGZ Solutions</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">

    <style>
        .preview-page {
            max-width: 1000px;
            margin: 35px auto;
            padding: 24px;
        }

        .preview-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .preview-button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid #dbe2ea;
            color: #1e293b;
            background: #fff;
        }

        .preview-article {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 32px;
        }

        .preview-meta {
            color: #64748b;
            font-size: 14px;
            margin: 12px 0 22px;
        }

        .preview-cover {
            display: block;
            width: 100%;
            max-height: 460px;
            object-fit: cover;
            border-radius: 10px;
            margin: 24px 0;
        }

        .preview-excerpt {
            font-size: 18px;
            color: #475569;
            line-height: 1.7;
            margin: 20px 0;
        }

        .preview-content {
            line-height: 1.9;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .preview-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #f1f5f9;
            font-size: 13px;
        }

        @media (max-width: 600px) {
            .preview-page {
                margin: 12px auto;
                padding: 12px;
            }

            .preview-article {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="preview-page">
        <div class="preview-toolbar">
            <a class="preview-button" href="ges_articles.php">
                ← Retour aux articles
            </a>

            <a
                class="preview-button"
                href="modifier-article.php?id=<?= (int) $id ?>"
            >
                Modifier l'article
            </a>
        </div>

        <article class="preview-article">
            <span class="preview-status">
                Statut : <?= e($statut) ?>
            </span>

            <div class="preview-meta">
                Catégorie : <?= e($categorie) ?>

                <?php if ($dateCreation): ?>
                    · Créé le
                    <?= e(date("d/m/Y", strtotime($dateCreation))) ?>
                <?php endif; ?>

                <?php if ($datePublication): ?>
                    · Publication :
                    <?= e(date("d/m/Y", strtotime($datePublication))) ?>
                <?php endif; ?>
            </div>

            <h1><?= e($titre) ?></h1>

            <?php if ($image !== ""): ?>
                <img
                    class="preview-cover"
                    src="<?= e($image) ?>"
                    alt="<?= e($titre) ?>"
                >
            <?php endif; ?>

            <?php if ($extrait !== ""): ?>
                <p class="preview-excerpt"><?= e($extrait) ?></p>
            <?php endif; ?>

            <div class="preview-content"><?= e($contenu) ?></div>
        </article>
    </main>
</body>
</html>
